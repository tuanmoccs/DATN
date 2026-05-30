import logging
import uuid

from fastapi import APIRouter, File, Form, HTTPException, UploadFile
from langchain.text_splitter import RecursiveCharacterTextSplitter
from langchain_core.documents import Document
from langchain_core.prompts import ChatPromptTemplate
from pydantic import BaseModel, Field

from app.core.dependencies import get_llm, get_vector_store
from app.prompts.quiz_prompts import QUIZ_SYSTEM_PROMPT, QUIZ_USER_PROMPT
from app.prompts.slide_prompts import SLIDE_SYSTEM_PROMPT, SLIDE_USER_PROMPT
from app.schemas.quiz import QuizGenerateResponse
from app.schemas.slide import SlideGenerateResponse
from app.services.document_processor import extract_text_from_bytes_with_fallback
from app.services.quiz_service import _parse_questions
from app.services.rag_service import (
    CONTEXT_SEPARATOR,
    RetrievedChunk,
    _distance_to_relevance,
    _lexical_overlap_score,
    _normalize_score,
    _rerank_chunks,
)
from app.services.slide_service import _parse_slides

logger = logging.getLogger(__name__)
router = APIRouter()


class SandboxSettings(BaseModel):
    chunk_size: int = Field(default=1000, ge=300, le=3000)
    chunk_overlap: int = Field(default=200, ge=0, le=1000)
    top_k: int = Field(default=5, ge=1, le=12)
    score_threshold: float = Field(default=0.45, ge=0, le=1)
    max_context_chars: int = Field(default=12000, ge=1000, le=30000)
    low_confidence_fallback: bool = True

    def normalized_overlap(self) -> int:
        return min(self.chunk_overlap, self.chunk_size - 1)


class SandboxChunkPreview(BaseModel):
    index: int
    characters: int
    overlap_characters: int = 0
    previous_tail_preview: str = ""
    overlap_preview: str = ""
    body_preview: str = ""
    preview: str


class SandboxProcessResponse(BaseModel):
    success: bool
    sandbox_id: str
    file_name: str
    content_type: str
    extracted_characters: int
    extracted_preview: str
    chunks_count: int
    chunks: list[SandboxChunkPreview]


class SandboxRetrieveRequest(BaseModel):
    sandbox_id: str
    query: str = ""
    settings: SandboxSettings = Field(default_factory=SandboxSettings)


class SandboxRetrievedChunk(BaseModel):
    index: int
    chunk_index: str
    relevance_score: float
    lexical_score: float
    combined_score: float
    passed_threshold: bool
    characters: int
    preview: str


class SandboxRetrieveResponse(BaseModel):
    success: bool
    sandbox_id: str
    query: str
    settings: dict
    chunks_returned: int
    context_characters: int
    context_preview: str
    chunks: list[SandboxRetrievedChunk]


class SandboxGenerateRequest(BaseModel):
    sandbox_id: str
    query: str = ""
    settings: SandboxSettings = Field(default_factory=SandboxSettings)
    language: str = "Vietnamese"
    count: int = Field(default=5, ge=1, le=30)
    difficulty: str = "medium"


def _detect_content_type(filename: str) -> str:
    filename = filename.lower()
    if filename.endswith(".pdf"):
        return "pdf"
    if filename.endswith(".docx"):
        return "docx"
    if filename.endswith(".txt"):
        return "txt"
    raise HTTPException(status_code=400, detail="Unsupported file type. Use PDF, DOCX, or TXT.")


def _preview_text(text: str, limit: int = 1200) -> str:
    normalized = " ".join(text.split())
    if len(normalized) <= limit:
        return normalized
    return normalized[:limit].rstrip() + "..."


def _settings_from_form(
    chunk_size: int,
    chunk_overlap: int,
    top_k: int,
    score_threshold: float,
    max_context_chars: int,
    low_confidence_fallback: bool,
) -> SandboxSettings:
    return SandboxSettings(
        chunk_size=chunk_size,
        chunk_overlap=chunk_overlap,
        top_k=top_k,
        score_threshold=score_threshold,
        max_context_chars=max_context_chars,
        low_confidence_fallback=low_confidence_fallback,
    )


@router.post("/process", response_model=SandboxProcessResponse)
async def process_sandbox_document(
    file: UploadFile = File(...),
    chunk_size: int = Form(default=1000),
    chunk_overlap: int = Form(default=200),
    top_k: int = Form(default=5),
    score_threshold: float = Form(default=0.45),
    max_context_chars: int = Form(default=12000),
    low_confidence_fallback: bool = Form(default=True),
):
    settings = _settings_from_form(
        chunk_size,
        chunk_overlap,
        top_k,
        score_threshold,
        max_context_chars,
        low_confidence_fallback,
    )
    filename = file.filename or ""
    content_type = _detect_content_type(filename)
    file_bytes = await file.read()

    try:
        text = await extract_text_from_bytes_with_fallback(file_bytes, content_type)
    except Exception as e:
        logger.error("Sandbox extraction failed: %s", e)
        raise HTTPException(status_code=400, detail=f"Failed to extract text: {str(e)}")

    if not text.strip():
        raise HTTPException(status_code=400, detail="No text content found in the document.")

    sandbox_id = f"sandbox-{uuid.uuid4()}"
    splitter = RecursiveCharacterTextSplitter(
        chunk_size=settings.chunk_size,
        chunk_overlap=settings.normalized_overlap(),
        length_function=len,
        separators=["\n\n", "\n", ". ", " ", ""],
    )
    documents = splitter.create_documents(
        texts=[text],
        metadatas=[{
            "sandbox_id": sandbox_id,
            "source_name": filename,
            "source_type": content_type,
            "chunk_size": str(settings.chunk_size),
            "chunk_overlap": str(settings.normalized_overlap()),
        }],
    )

    for index, document in enumerate(documents):
        document.metadata.update({
            "chunk_index": index,
            "chunk_chars": len(document.page_content),
        })

    get_vector_store().add_documents(documents)
    chunk_previews = _build_chunk_previews(documents, settings.normalized_overlap())

    return SandboxProcessResponse(
        success=True,
        sandbox_id=sandbox_id,
        file_name=filename,
        content_type=content_type,
        extracted_characters=len(text),
        extracted_preview=_preview_text(text),
        chunks_count=len(documents),
        chunks=chunk_previews,
    )


@router.post("/retrieve", response_model=SandboxRetrieveResponse)
async def retrieve_sandbox(request: SandboxRetrieveRequest):
    return _retrieve_sandbox_response(request.sandbox_id, request.query, request.settings)


@router.post("/slides", response_model=SlideGenerateResponse)
async def generate_sandbox_slides(request: SandboxGenerateRequest):
    retrieval = _retrieve_sandbox(request.sandbox_id, request.query, request.settings)
    context = retrieval["context"]
    if not context:
        return SlideGenerateResponse(
            success=False,
            lesson_id=0,
            slides=[],
            total_slides=0,
            message="No sandbox context found.",
        )

    prompt = ChatPromptTemplate.from_messages([
        ("system", SLIDE_SYSTEM_PROMPT),
        ("user", SLIDE_USER_PROMPT),
    ])
    response = await (prompt | get_llm()).ainvoke({
        "language": request.language,
        "num_slides": request.count,
        "context": context,
        "additional_instructions": "Return JSON only for sandbox preview.",
    })
    slides = _parse_slides(response.content)
    return SlideGenerateResponse(
        success=True,
        lesson_id=0,
        slides=slides,
        total_slides=len(slides),
        message=f"Generated {len(slides)} sandbox slides.",
    )


@router.post("/quiz", response_model=QuizGenerateResponse)
async def generate_sandbox_quiz(request: SandboxGenerateRequest):
    retrieval = _retrieve_sandbox(request.sandbox_id, request.query, request.settings)
    context = retrieval["context"]
    if not context:
        return QuizGenerateResponse(
            success=False,
            lesson_id=0,
            questions=[],
            total_questions=0,
            message="No sandbox context found.",
        )

    prompt = ChatPromptTemplate.from_messages([
        ("system", QUIZ_SYSTEM_PROMPT),
        ("user", QUIZ_USER_PROMPT),
    ])
    response = await (prompt | get_llm().bind(response_format={"type": "json_object"})).ainvoke({
        "language": request.language,
        "difficulty": request.difficulty,
        "num_questions": request.count,
        "context": context,
        "additional_instructions": "Return JSON only for sandbox preview.",
    })
    questions = _parse_questions(response.content)
    return QuizGenerateResponse(
        success=True,
        lesson_id=0,
        questions=questions,
        total_questions=len(questions),
        message=f"Generated {len(questions)} sandbox questions.",
    )


@router.delete("/{sandbox_id}")
async def delete_sandbox(sandbox_id: str):
    vector_store = get_vector_store()
    results = vector_store.get(where={"sandbox_id": sandbox_id})
    if results and results["ids"]:
        vector_store.delete(ids=results["ids"])
    return {"success": True, "sandbox_id": sandbox_id}


def _retrieve_sandbox_response(sandbox_id: str, query: str, settings: SandboxSettings) -> SandboxRetrieveResponse:
    result = _retrieve_sandbox(sandbox_id, query, settings)
    chunks = result["chunks"]
    context = result["context"]
    return SandboxRetrieveResponse(
        success=True,
        sandbox_id=sandbox_id,
        query=query,
        settings=settings.model_dump(),
        chunks_returned=len(chunks),
        context_characters=len(context),
        context_preview=_preview_text(context, limit=3000),
        chunks=[
            SandboxRetrievedChunk(
                index=index,
                chunk_index=str(chunk.document.metadata.get("chunk_index", "")),
                relevance_score=round(chunk.relevance_score, 4),
                lexical_score=round(chunk.lexical_score, 4),
                combined_score=round(chunk.combined_score, 4),
                passed_threshold=chunk.passed_threshold,
                characters=len(chunk.document.page_content),
                preview=_preview_text(chunk.document.page_content, limit=700),
            )
            for index, chunk in enumerate(chunks, start=1)
        ],
    )


def _retrieve_sandbox(sandbox_id: str, query: str, settings: SandboxSettings) -> dict:
    vector_store = get_vector_store()
    search_query = query if query.strip() else "lesson content summary"
    candidate_k = max(settings.top_k, settings.top_k * 4)
    candidates = _similarity_search_with_scores(
        vector_store=vector_store,
        query=search_query,
        k=candidate_k,
        sandbox_id=sandbox_id,
    )

    if not candidates:
        return {"context": "", "chunks": []}

    reranked = _rerank_chunks(candidates, search_query, settings.score_threshold)
    passing = [chunk for chunk in reranked if chunk.passed_threshold]
    chunks = passing[:settings.top_k]
    if not chunks and settings.low_confidence_fallback:
        chunks = reranked[:settings.top_k]

    return {
        "context": _build_context(chunks, settings.max_context_chars),
        "chunks": chunks,
    }


def _similarity_search_with_scores(vector_store, query: str, k: int, sandbox_id: str) -> list[tuple[Document, float]]:
    try:
        results = vector_store.similarity_search_with_score(
            query=query,
            k=k,
            filter={"sandbox_id": sandbox_id},
        )
        return [(document, _distance_to_relevance(distance)) for document, distance in results]
    except AttributeError:
        documents = vector_store.similarity_search(
            query=query,
            k=k,
            filter={"sandbox_id": sandbox_id},
        )
        return [(document, 1.0) for document in documents]


def _build_context(chunks: list[RetrievedChunk], max_context_chars: int) -> str:
    context_parts: list[str] = []
    total_chars = 0

    for chunk in chunks:
        content = chunk.document.page_content.strip()
        if not content:
            continue

        if total_chars + len(content) > max_context_chars:
            remaining = max_context_chars - total_chars
            if remaining <= 0:
                break
            content = content[:remaining].rstrip()

        context_parts.append(content)
        total_chars += len(content)

    return CONTEXT_SEPARATOR.join(context_parts)


def _build_chunk_previews(documents: list[Document], max_overlap_chars: int) -> list[SandboxChunkPreview]:
    previews: list[SandboxChunkPreview] = []
    previous_content = ""

    for index, document in enumerate(documents[:10], start=1):
        content = document.page_content
        overlap_text = _find_prefix_overlap(previous_content, content, max_overlap_chars) if previous_content else ""
        body_text = content[len(overlap_text):]

        previews.append(SandboxChunkPreview(
            index=index,
            characters=len(content),
            overlap_characters=len(overlap_text),
            previous_tail_preview=_preview_text(previous_content[-len(overlap_text):], limit=260) if overlap_text else "",
            overlap_preview=_preview_text(overlap_text, limit=260) if overlap_text else "",
            body_preview=_preview_text(body_text or content, limit=500),
            preview=_preview_text(content, limit=500),
        ))
        previous_content = content

    return previews


def _find_prefix_overlap(previous: str, current: str, max_overlap_chars: int) -> str:
    max_size = min(max_overlap_chars, len(previous), len(current))
    for size in range(max_size, 19, -1):
        candidate = previous[-size:]
        if current.startswith(candidate):
            return candidate
    return ""
