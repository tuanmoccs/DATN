import logging

from fastapi import APIRouter, HTTPException

from app.agents import execute_agent
from app.schemas.quiz import QuizGenerateRequest, QuizGenerateResponse

logger = logging.getLogger(__name__)
router = APIRouter()


@router.post("/generate", response_model=QuizGenerateResponse)
async def generate_quiz_endpoint(request: QuizGenerateRequest):
    """Generate quiz questions from lesson content using RAG + LLM."""
    try:
        result = await execute_agent("quiz", request.model_dump())
        if not result.success:
            raise HTTPException(status_code=404, detail=result.message)
        return result
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Quiz generation failed: {e}")
        raise HTTPException(status_code=500, detail=f"Quiz generation failed: {str(e)}")
