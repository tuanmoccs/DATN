import logging

from fastapi import APIRouter, HTTPException

from app.schemas.slide import SlideGenerateRequest, SlideGenerateResponse
from app.services.slide_service import generate_slides

logger = logging.getLogger(__name__)
router = APIRouter()


@router.post("/generate", response_model=SlideGenerateResponse)
async def generate_slides_endpoint(request: SlideGenerateRequest):
    """Generate presentation slides from lesson content using RAG + LLM."""
    try:
        result = await generate_slides(request)
        if not result.success:
            raise HTTPException(status_code=404, detail=result.message)
        return result
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Slide generation failed: {e}")
        raise HTTPException(status_code=500, detail=f"Slide generation failed: {str(e)}")
