from langchain_openai import ChatOpenAI, OpenAIEmbeddings
from langchain_chroma import Chroma

from app.core.config import get_settings

settings = get_settings()

# Singletons
_llm: ChatOpenAI | None = None
_embeddings: OpenAIEmbeddings | None = None
_vector_store: Chroma | None = None


def init_services():
    global _llm, _embeddings, _vector_store

    _llm = ChatOpenAI(
        model=settings.openai_model,
        api_key=settings.openai_api_key,
        temperature=0.7,
    )

    _embeddings = OpenAIEmbeddings(
        model=settings.openai_embedding_model,
        api_key=settings.openai_api_key,
    )

    _vector_store = Chroma(
        collection_name="lesson_documents",
        embedding_function=_embeddings,
        persist_directory=settings.chroma_persist_dir,
    )


def shutdown_services():
    pass


def get_llm() -> ChatOpenAI:
    if _llm is None:
        raise RuntimeError("LLM not initialized")
    return _llm


def get_embeddings() -> OpenAIEmbeddings:
    if _embeddings is None:
        raise RuntimeError("Embeddings not initialized")
    return _embeddings


def get_vector_store() -> Chroma:
    if _vector_store is None:
        raise RuntimeError("Vector store not initialized")
    return _vector_store
