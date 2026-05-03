from app.agents.base import BaseAgent
from app.schemas.report import (
    CompetencyReportGenerateRequest,
    CompetencyReportGenerateResponse,
)
from app.services.report_service import generate_competency_report


class CompetencyReportAgent(BaseAgent):
    name = "competency_report"
    description = "Generate competency reports from quiz and assignment evidence."
    request_model = CompetencyReportGenerateRequest

    async def run(
        self,
        payload: CompetencyReportGenerateRequest,
    ) -> CompetencyReportGenerateResponse:
        return await generate_competency_report(payload)
