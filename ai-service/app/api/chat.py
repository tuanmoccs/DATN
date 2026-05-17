import logging

from fastapi import APIRouter, HTTPException

from app.agents import execute_agent
from app.schemas.chat import ChatRequest, ChatResponse

logger = logging.getLogger(__name__)
router = APIRouter()


@router.post("/message", response_model=ChatResponse)
async def chat_endpoint(request: ChatRequest):
    """Student asks a question about a lesson, answered using RAG + LLM."""
    try:
        result = await execute_agent("chat", request.model_dump())
        if not result.success:
            raise HTTPException(status_code=404, detail=result.message)
        return result
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Chat failed: {e}")
        raise HTTPException(status_code=500, detail=f"Chat failed: {str(e)}")
