import logging

from fastapi import APIRouter, HTTPException

from app.agents import execute_agent
from app.schemas.autocomplete import AutocompleteRequest, AutocompleteResponse

logger = logging.getLogger(__name__)
router = APIRouter()


@router.post("/suggest", response_model=AutocompleteResponse)
async def autocomplete_endpoint(request: AutocompleteRequest):
    """Generate AI autocomplete suggestion for lesson plan editor."""
    try:
        result = await execute_agent("autocomplete", request.model_dump())
        return result
    except Exception as e:
        logger.error(f"Autocomplete failed: {e}")
        # Return empty suggestion instead of 500 to not disrupt editor UX
        return AutocompleteResponse(suggestion="")
