import logging

from fastapi import APIRouter, File, Form, HTTPException, UploadFile
from langchain_core.messages import HumanMessage

from app.core.dependencies import get_llm
from app.core.dependencies import get_vector_store
from app.schemas.document import (
    ChromaDocumentItem,
    ChromaInspectResponse,
    DocumentAnalyzeResponse,
    DocumentChunkPreview,
    DocumentDeleteRequest,
    DocumentProcessResponse,
    DocumentTextRequest,
)
from app.services.document_processor import extract_text_from_bytes_with_fallback
from app.services.rag_service import chunk_text, delete_lesson_chunks, store_chunks

logger = logging.getLogger(__name__)
router = APIRouter()


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


def _build_document_analysis_prompt(text: str) -> str:
    return (
        "You are a document analysis agent for an AI education system.\n"
        "Analyze the extracted lesson document and return a concise Vietnamese report with:\n"
        "1. Tom tat noi dung chinh\n"
        "2. Cac khai niem/kien thuc trong tam\n"
        "3. Goi y co the tao slide/quiz/chat tu tai lieu nay nhu the nao\n"
        "4. Luu y neu tai lieu thieu thong tin hoac co noi dung khong phu hop\n\n"
        "EXTRACTED DOCUMENT TEXT:\n"
        f"{text[:6000]}"
    )


@router.post("/process", response_model=DocumentProcessResponse)
async def process_document(
    file: UploadFile = File(...),
    lesson_id: int = Form(...),
):
    """Upload and process a document: extract text → chunk → embed → store in vector DB."""
    file_bytes = await file.read()
    filename = file.filename or ""
    content_type = _detect_content_type(filename)

    try:
        text = await extract_text_from_bytes_with_fallback(file_bytes, content_type)
    except Exception as e:
        logger.error(f"Text extraction failed: {e}")
        raise HTTPException(status_code=400, detail=f"Failed to extract text: {str(e)}")

    if not text.strip():
        if content_type == "pdf":
            raise HTTPException(
                status_code=400,
                detail="No selectable text found in the PDF. The file may be a scanned image and requires OCR.",
            )
        raise HTTPException(status_code=400, detail="No text content found in the document.")

    # Delete old chunks for this lesson before re-processing
    delete_lesson_chunks(lesson_id)

    chunks = chunk_text(text, lesson_id)
    chunks_count = store_chunks(chunks)

    return DocumentProcessResponse(
        success=True,
        lesson_id=lesson_id,
        chunks_count=chunks_count,
        message=f"Document processed successfully. {chunks_count} chunks stored.",
    )


@router.post("/analyze-demo", response_model=DocumentAnalyzeResponse)
async def analyze_document_demo(
    file: UploadFile = File(...),
    lesson_id: int = Form(...),
    store_to_chroma: bool = Form(default=True),
    include_llm_analysis: bool = Form(default=True),
):
    """Demo endpoint: upload a document and return extraction, chunking, prompt, and analysis details."""
    file_bytes = await file.read()
    filename = file.filename or ""
    content_type = _detect_content_type(filename)

    try:
        text = await extract_text_from_bytes_with_fallback(file_bytes, content_type)
    except Exception as e:
        logger.error("Text extraction failed: %s", e)
        raise HTTPException(status_code=400, detail=f"Failed to extract text: {str(e)}")

    if not text.strip():
        raise HTTPException(status_code=400, detail="No text content found in the document.")

    chunks = chunk_text(text, lesson_id)
    if store_to_chroma:
        delete_lesson_chunks(lesson_id)
        chunks_count = store_chunks(chunks)
    else:
        chunks_count = len(chunks)

    prompt_preview = _build_document_analysis_prompt(text)
    analysis = (
        "LLM analysis was skipped. The response still shows extracted text, chunks, metadata, "
        "and the prompt that would be sent to the model."
    )

    if include_llm_analysis:
        llm = get_llm()
        response = await llm.ainvoke([HumanMessage(content=prompt_preview)])
        analysis = response.content if isinstance(response.content, str) else str(response.content)

    chunk_previews = [
        DocumentChunkPreview(
            chunk_number=index,
            characters=len(chunk.page_content),
            metadata={key: str(value) for key, value in chunk.metadata.items()},
            preview=_preview_text(chunk.page_content, limit=500),
        )
        for index, chunk in enumerate(chunks[:8], start=1)
    ]

    return DocumentAnalyzeResponse(
        success=True,
        lesson_id=lesson_id,
        file_name=filename,
        content_type=content_type,
        extracted_characters=len(text),
        extracted_text_preview=_preview_text(text),
        chunks_count=chunks_count,
        chunks=chunk_previews,
        prompt_preview=prompt_preview,
        analysis=analysis.strip(),
        message=(
            "Document analyzed and stored in ChromaDB."
            if store_to_chroma
            else "Document analyzed without storing to ChromaDB."
        ),
    )


@router.post("/process-text", response_model=DocumentProcessResponse)
async def process_document_text(request: DocumentTextRequest):
    """Process pre-extracted text: chunk → embed → store in vector DB.
    Used by Laravel backend which already handles text extraction.
    """
    if not request.text.strip():
        raise HTTPException(status_code=400, detail="No text content provided.")

    # Delete old chunks for this lesson before re-processing
    delete_lesson_chunks(request.lesson_id)

    chunks = chunk_text(request.text, request.lesson_id)
    chunks_count = store_chunks(chunks)

    return DocumentProcessResponse(
        success=True,
        lesson_id=request.lesson_id,
        chunks_count=chunks_count,
        message=f"Text processed successfully. {chunks_count} chunks stored.",
    )


@router.post("/extract")
async def extract_text(
    file: UploadFile = File(...),
):
    """Extract text from an uploaded file and return it. Supports PDF, DOCX, TXT."""
    file_bytes = await file.read()
    filename = file.filename or ""
    content_type = _detect_content_type(filename)

    try:
        text = await extract_text_from_bytes_with_fallback(file_bytes, content_type)
    except Exception as e:
        logger.error(f"Text extraction failed: {e}")
        raise HTTPException(status_code=400, detail=f"Failed to extract text: {str(e)}")

    if not text.strip():
        raise HTTPException(status_code=400, detail="No text content found in the document.")

    return {"success": True, "text": text}


@router.get("/chroma/inspect", response_model=ChromaInspectResponse)
async def inspect_chroma(
    lesson_id: int | None = None,
    limit: int = 20,
    include_full_document: bool = False,
):
    """Inspect stored ChromaDB chunks for demo/debugging."""
    vector_store = get_vector_store()
    where = {"lesson_id": str(lesson_id)} if lesson_id is not None else None

    try:
        results = vector_store.get(
            where=where,
            limit=max(1, min(limit, 100)),
            include=["metadatas", "documents"],
        )
    except Exception as e:
        logger.error("Chroma inspect failed: %s", e)
        raise HTTPException(status_code=500, detail=f"Chroma inspect failed: {str(e)}")

    ids = results.get("ids") or []
    documents = results.get("documents") or []
    metadatas = results.get("metadatas") or []

    items = [
        ChromaDocumentItem(
            id=str(doc_id),
            metadata=metadata or {},
            document=document if include_full_document else "",
            document_preview=_preview_text(document or "", limit=800),
        )
        for doc_id, document, metadata in zip(ids, documents, metadatas)
    ]

    return ChromaInspectResponse(
        success=True,
        collection="lesson_documents",
        lesson_id=lesson_id,
        total_returned=len(items),
        items=items,
    )


@router.delete("/delete", response_model=DocumentProcessResponse)
async def delete_document(request: DocumentDeleteRequest):
    """Delete all processed chunks for a lesson."""
    delete_lesson_chunks(request.lesson_id)

    return DocumentProcessResponse(
        success=True,
        lesson_id=request.lesson_id,
        chunks_count=0,
        message="All document chunks deleted.",
    )
