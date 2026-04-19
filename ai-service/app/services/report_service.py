import json
import logging

from langchain_core.prompts import ChatPromptTemplate

from app.core.config import get_settings
from app.core.dependencies import get_llm
from app.prompts.report_prompts import (
    COMPETENCY_REPORT_SYSTEM_PROMPT,
    COMPETENCY_REPORT_USER_PROMPT,
)
from app.schemas.report import (
    CompetencyReportGenerateRequest,
    CompetencyReportGenerateResponse,
)

logger = logging.getLogger(__name__)
settings = get_settings()


async def generate_competency_report(
    request: CompetencyReportGenerateRequest,
) -> CompetencyReportGenerateResponse:
    """Generate an advisory competency report from quiz and assignment evidence."""
    payload = json.dumps(request.model_dump(by_alias=True), ensure_ascii=False, indent=2)

    llm = get_llm()
    prompt = ChatPromptTemplate.from_messages([
        ("system", COMPETENCY_REPORT_SYSTEM_PROMPT),
        ("user", COMPETENCY_REPORT_USER_PROMPT),
    ])

    chain = prompt | llm
    response = await chain.ainvoke({"payload": payload})
    parsed = _parse_report(response.content)

    return CompetencyReportGenerateResponse(
        success=True,
        overall_summary=parsed.get("overall_summary", ""),
        strengths=_normalize_string_list(parsed.get("strengths", [])),
        weaknesses=_normalize_string_list(parsed.get("weaknesses", [])),
        recommendations=_normalize_string_list(parsed.get("recommendations", [])),
        model_used=settings.openai_model,
        message="Generated competency report successfully",
    )


def _parse_report(content: str) -> dict:
    cleaned = content.strip()

    if cleaned.startswith("```"):
        cleaned = cleaned.split("\n", 1)[1] if "\n" in cleaned else cleaned[3:]
    if cleaned.endswith("```"):
        cleaned = cleaned[:-3]
    cleaned = cleaned.strip()

    try:
        data = json.loads(cleaned)
    except json.JSONDecodeError:
        logger.error("Failed to parse competency report JSON: %s", cleaned[:500])
        return {}

    return data if isinstance(data, dict) else {}


def _normalize_string_list(value) -> list[str]:
    if not isinstance(value, list):
        return []

    return [str(item).strip() for item in value if str(item).strip()]
