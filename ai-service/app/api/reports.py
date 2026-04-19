import logging

from fastapi import APIRouter, HTTPException

from app.schemas.report import (
    CompetencyReportGenerateRequest,
    CompetencyReportGenerateResponse,
)
from app.services.report_service import generate_competency_report

logger = logging.getLogger(__name__)
router = APIRouter()


@router.post("/competency/generate", response_model=CompetencyReportGenerateResponse)
async def generate_competency_report_endpoint(request: CompetencyReportGenerateRequest):
    """Generate an advisory student competency report from structured learning evidence."""
    try:
        return await generate_competency_report(request)
    except Exception as e:
        logger.error("Competency report generation failed: %s", e)
        raise HTTPException(status_code=500, detail=f"Competency report generation failed: {str(e)}")
