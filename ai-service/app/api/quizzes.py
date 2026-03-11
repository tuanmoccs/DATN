import logging

from fastapi import APIRouter, HTTPException

from app.schemas.quiz import QuizGenerateRequest, QuizGenerateResponse
from app.services.quiz_service import generate_quiz

logger = logging.getLogger(__name__)
router = APIRouter()


@router.post("/generate", response_model=QuizGenerateResponse)
async def generate_quiz_endpoint(request: QuizGenerateRequest):
    """Generate quiz questions from lesson content using RAG + LLM."""
    try:
        result = await generate_quiz(request)
        if not result.success:
            raise HTTPException(status_code=404, detail=result.message)
        return result
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Quiz generation failed: {e}")
        raise HTTPException(status_code=500, detail=f"Quiz generation failed: {str(e)}")
