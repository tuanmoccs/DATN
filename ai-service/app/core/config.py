from pydantic_settings import BaseSettings
from functools import lru_cache


class Settings(BaseSettings):
    # OpenAI
    openai_api_key: str = ""
    openai_model: str = "gpt-4o-mini"
    openai_embedding_model: str = "text-embedding-3-small"
    openai_image_model: str = "gpt-image-1"
    openai_image_size: str = "1536x1024"
    openai_image_quality: str = "medium"

    # Server
    host: str = "0.0.0.0"
    port: int = 8001
    debug: bool = True

    # ChromaDB
    chroma_persist_dir: str = "./chroma_data"

    # Laravel
    laravel_api_url: str = "http://localhost:8000/api"
    laravel_api_secret: str = ""
    api_secret: str = ""  # Secret key that Laravel sends via X-API-Secret header

    # Chunking
    chunk_size: int = 1000
    chunk_overlap: int = 200

    class Config:
        env_file = ".env"
        env_file_encoding = "utf-8"


@lru_cache()
def get_settings() -> Settings:
    return Settings()
