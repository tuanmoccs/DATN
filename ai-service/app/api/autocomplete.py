import logging

from fastapi import APIRouter, HTTPException

from app.schemas.autocomplete import AutocompleteRequest, AutocompleteResponse
from app.services.autocomplete_service import generate_autocomplete

logger = logging.getLogger(__name__)
router = APIRouter()


@router.post("/suggest", response_model=AutocompleteResponse)
async def autocomplete_endpoint(request: AutocompleteRequest):
    """Generate AI autocomplete suggestion for lesson plan editor."""
    try:
        result = await generate_autocomplete(request)
        return result
    except Exception as e:
        logger.error(f"Autocomplete failed: {e}")
        # Return empty suggestion instead of 500 to not disrupt editor UX
        return AutocompleteResponse(suggestion="")
