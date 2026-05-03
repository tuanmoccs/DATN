import logging

from fastapi import APIRouter, HTTPException

from app.agents import execute_agent
from app.schemas.report import (
    CompetencyReportGenerateRequest,
    CompetencyReportGenerateResponse,
)

logger = logging.getLogger(__name__)
router = APIRouter()


@router.post("/competency/generate", response_model=CompetencyReportGenerateResponse)
async def generate_competency_report_endpoint(request: CompetencyReportGenerateRequest):
    """Generate an advisory student competency report from structured learning evidence."""
    try:
        return await execute_agent("competency_report", request.model_dump(by_alias=True))
    except Exception as e:
        logger.error("Competency report generation failed: %s", e)
        raise HTTPException(status_code=500, detail=f"Competency report generation failed: {str(e)}")
