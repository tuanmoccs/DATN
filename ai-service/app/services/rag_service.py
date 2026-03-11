from langchain.text_splitter import RecursiveCharacterTextSplitter
from langchain_core.documents import Document

from app.core.config import get_settings
from app.core.dependencies import get_vector_store

settings = get_settings()


def chunk_text(text: str, lesson_id: int) -> list[Document]:
    """Split text into chunks with metadata."""
    splitter = RecursiveCharacterTextSplitter(
        chunk_size=settings.chunk_size,
        chunk_overlap=settings.chunk_overlap,
        length_function=len,
        separators=["\n\n", "\n", ". ", " ", ""],
    )

    chunks = splitter.create_documents(
        texts=[text],
        metadatas=[{"lesson_id": str(lesson_id)}],
    )

    return chunks


def store_chunks(chunks: list[Document]) -> int:
    """Store document chunks in ChromaDB vector store."""
    vector_store = get_vector_store()
    vector_store.add_documents(chunks)
    return len(chunks)


def retrieve_context(lesson_id: int, query: str = "", top_k: int = 8) -> str:
    """Retrieve relevant chunks from vector store for a lesson."""
    vector_store = get_vector_store()

    search_query = query if query else "lesson content summary"

    results = vector_store.similarity_search(
        query=search_query,
        k=top_k,
        filter={"lesson_id": str(lesson_id)},
    )

    if not results:
        return ""

    return "\n\n---\n\n".join([doc.page_content for doc in results])


def retrieve_context_with_sources(lesson_id: int, query: str, top_k: int = 5) -> tuple[str, list[str]]:
    """Retrieve context and return source excerpts."""
    vector_store = get_vector_store()

    results = vector_store.similarity_search(
        query=query,
        k=top_k,
        filter={"lesson_id": str(lesson_id)},
    )

    if not results:
        return "", []

    context = "\n\n---\n\n".join([doc.page_content for doc in results])
    sources = [doc.page_content[:100] + "..." for doc in results]

    return context, sources


def delete_lesson_chunks(lesson_id: int) -> bool:
    """Delete all chunks for a lesson from vector store."""
    vector_store = get_vector_store()

    results = vector_store.get(where={"lesson_id": str(lesson_id)})

    if results and results["ids"]:
        vector_store.delete(ids=results["ids"])

    return True
