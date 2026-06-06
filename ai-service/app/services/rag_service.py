import hashlib
import logging
import re
from dataclasses import dataclass

from langchain.text_splitter import RecursiveCharacterTextSplitter
from langchain_core.documents import Document

from app.core.config import get_settings
from app.core.dependencies import get_vector_store

logger = logging.getLogger(__name__)
settings = get_settings()

CONTEXT_SEPARATOR = "\n\n---\n\n"


@dataclass
class RetrievedChunk:
    document: Document
    relevance_score: float
    lexical_score: float
    combined_score: float
    passed_threshold: bool


def chunk_text(
    text: str,
    lesson_id: int,
    source_type: str = "text",
    source_name: str = "",
) -> list[Document]:
    """Split text into chunks with metadata."""
    splitter = RecursiveCharacterTextSplitter(
        chunk_size=settings.chunk_size,
        chunk_overlap=settings.chunk_overlap,
        length_function=len,
        separators=["\n\n", "\n", ". ", " ", ""],
    )

    chunks = splitter.create_documents(
        texts=[text],
        metadatas=[{
            "lesson_id": str(lesson_id),
            "source_type": source_type,
            "source_name": source_name,
        }],
    )

    for index, chunk in enumerate(chunks):
        chunk.metadata.update({
            "chunk_index": index,
            "chunk_chars": len(chunk.page_content),
            "content_hash": hashlib.sha1(chunk.page_content.encode("utf-8")).hexdigest()[:12],
        })

    return chunks


def store_chunks(chunks: list[Document]) -> int:
    """Store document chunks in ChromaDB vector store."""
    vector_store = get_vector_store()
    vector_store.add_documents(chunks)
    return len(chunks)


def retrieve_relevant_chunks(
    lesson_id: int,
    query: str = "",
    top_k: int = 8,
    score_threshold: float | None = None,
    allow_low_confidence_fallback: bool | None = None,
) -> list[RetrievedChunk]:
    """Retrieve, threshold, and rerank chunks for a lesson."""
    vector_store = get_vector_store()

    search_query = query if query.strip() else "lesson content summary"
    threshold = settings.rag_score_threshold if score_threshold is None else score_threshold
    fallback_enabled = (
        settings.rag_low_confidence_fallback
        if allow_low_confidence_fallback is None
        else allow_low_confidence_fallback
    )
    candidate_k = max(top_k, top_k * max(1, settings.rag_candidate_multiplier))

    candidates = _similarity_search_with_scores(
        vector_store=vector_store,
        query=search_query,
        k=candidate_k,
        lesson_id=lesson_id,
    )

    if not candidates:
        return []

    reranked = _rerank_chunks(candidates, search_query, threshold)
    passing = [chunk for chunk in reranked if chunk.passed_threshold]

    if passing:
        return passing[:top_k]

    if not fallback_enabled:
        logger.info(
            "RAG retrieval rejected all chunks for lesson_id=%s threshold=%.2f best_score=%.3f",
            lesson_id,
            threshold,
            reranked[0].relevance_score if reranked else 0,
        )
        return []

    logger.info(
        "RAG retrieval using low-confidence fallback for lesson_id=%s threshold=%.2f best_score=%.3f",
        lesson_id,
        threshold,
        reranked[0].relevance_score if reranked else 0,
    )
    return reranked[:top_k]


def retrieve_context(lesson_id: int, query: str = "", top_k: int = 8) -> str:
    """Retrieve relevant chunks from vector store for a lesson."""
    chunks = retrieve_relevant_chunks(lesson_id=lesson_id, query=query, top_k=top_k)
    if not chunks:
        return ""

    return _build_context(chunks)


def retrieve_context_with_sources(lesson_id: int, query: str, top_k: int = 5) -> tuple[str, list[str]]:
    """Retrieve context and return source excerpts."""
    chunks = retrieve_relevant_chunks(lesson_id=lesson_id, query=query, top_k=top_k)
    if not chunks:
        return "", []

    context = _build_context(chunks)
    sources = [_format_source(chunk) for chunk in chunks]

    return context, sources


def delete_lesson_chunks(lesson_id: int) -> bool:
    """Delete all chunks for a lesson from vector store."""
    vector_store = get_vector_store()

    results = vector_store.get(where={"lesson_id": str(lesson_id)})

    if results and results["ids"]:
        vector_store.delete(ids=results["ids"])

    return True


def _similarity_search_with_scores(vector_store, query: str, k: int, lesson_id: int) -> list[tuple[Document, float]]:
    try:
        results = vector_store.similarity_search_with_score(
            query=query,
            k=k,
            filter={"lesson_id": str(lesson_id)},
        )
        return [(document, _distance_to_relevance(distance)) for document, distance in results]
    except AttributeError:
        logger.warning("Vector store does not support scores; falling back to similarity_search")
        documents = vector_store.similarity_search(
            query=query,
            k=k,
            filter={"lesson_id": str(lesson_id)},
        )
        return [(document, 1.0) for document in documents]


def _rerank_chunks(
    candidates: list[tuple[Document, float]],
    query: str,
    threshold: float,
) -> list[RetrievedChunk]:
    query_terms = _tokenize(query)
    reranked: list[RetrievedChunk] = []

    for document, raw_score in candidates:
        relevance_score = _normalize_score(raw_score)
        lexical_score = _lexical_overlap_score(query_terms, document.page_content)
        combined_score = (relevance_score * 0.85) + (lexical_score * 0.15)

        reranked.append(RetrievedChunk(
            document=document,
            relevance_score=relevance_score,
            lexical_score=lexical_score,
            combined_score=combined_score,
            passed_threshold=relevance_score >= threshold,
        ))

    return sorted(reranked, key=lambda item: item.combined_score, reverse=True)


def _build_context(chunks: list[RetrievedChunk]) -> str:
    context_parts: list[str] = []
    total_chars = 0

    for chunk in chunks:
        content = chunk.document.page_content.strip()
        if not content:
            continue

        if total_chars + len(content) > settings.rag_max_context_chars:
            remaining = settings.rag_max_context_chars - total_chars
            if remaining <= 0:
                break
            content = content[:remaining].rstrip()

        context_parts.append(content)
        total_chars += len(content)

    return CONTEXT_SEPARATOR.join(context_parts)


def _format_source(chunk: RetrievedChunk) -> str:
    metadata = chunk.document.metadata
    chunk_index = metadata.get("chunk_index", "?")
    source_name = metadata.get("source_name") or metadata.get("source_type") or "lesson"
    confidence = "matched" if chunk.passed_threshold else "fallback"
    excerpt = " ".join(chunk.document.page_content.split())[:120]
    return (
        f"{source_name} | chunk {chunk_index} | score {chunk.relevance_score:.2f} "
        f"| {confidence}: {excerpt}..."
    )


def _tokenize(value: str) -> set[str]:
    return {
        token
        for token in re.findall(r"[\w]+", value.lower(), flags=re.UNICODE)
        if len(token) >= 3
    }


def _lexical_overlap_score(query_terms: set[str], content: str) -> float:
    if not query_terms:
        return 0.0

    content_terms = _tokenize(content)
    if not content_terms:
        return 0.0

    return len(query_terms.intersection(content_terms)) / len(query_terms)


def _normalize_score(score: float) -> float:
    if score < 0:
        return 0.0
    if score > 1:
        return 1.0
    return score


def _distance_to_relevance(distance: float) -> float:
    if distance < 0:
        return 0.0
    return 1 / (1 + distance)
