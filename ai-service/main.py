from contextlib import asynccontextmanager

from fastapi import Depends, FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.core.config import get_settings
from app.core.dependencies import init_services, shutdown_services
from app.core.security import verify_api_secret
from app.api.documents import router as documents_router
from app.api.slides import router as slides_router
from app.api.quizzes import router as quizzes_router
from app.api.chat import router as chat_router
from app.api.health import router as health_router


@asynccontextmanager
async def lifespan(app: FastAPI):
    init_services()
    yield
    shutdown_services()


settings = get_settings()

app = FastAPI(
    title="AI Education Service",
    description="RAG-based AI service for generating slides, quizzes, and chat",
    version="1.0.0",
    lifespan=lifespan,
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:8000"],
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(health_router, tags=["Health"])
app.include_router(
    documents_router,
    prefix="/api/documents",
    tags=["Documents"],
    dependencies=[Depends(verify_api_secret)],
)
app.include_router(
    slides_router,
    prefix="/api/slides",
    tags=["Slides"],
    dependencies=[Depends(verify_api_secret)],
)
app.include_router(
    quizzes_router,
    prefix="/api/quizzes",
    tags=["Quizzes"],
    dependencies=[Depends(verify_api_secret)],
)
app.include_router(
    chat_router,
    prefix="/api/chat",
    tags=["Chat"],
    dependencies=[Depends(verify_api_secret)],
)


if __name__ == "__main__":
    import uvicorn

    uvicorn.run("main:app", host=settings.host, port=settings.port, reload=settings.debug)
