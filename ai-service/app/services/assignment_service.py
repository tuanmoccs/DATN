import base64
import json
import logging

from fastapi import UploadFile
from langchain_core.messages import HumanMessage
from langchain_core.prompts import ChatPromptTemplate

from app.core.dependencies import get_llm
from app.schemas.assignment import AssignmentGradeRequest, AssignmentGradeResponse
from app.services.document_processor import extract_text_from_bytes_with_fallback

logger = logging.getLogger(__name__)

ASSIGNMENT_GRADING_SYSTEM_PROMPT = (
    "You are an experienced and fair teacher who grades student assignments. "
    "Provide constructive, detailed feedback. Respond only with valid JSON. "
    "Use the same language as the assignment or student answer."
)

ASSIGNMENT_GRADING_USER_PROMPT = """Grade this student assignment submission.

ASSIGNMENT INFORMATION
Title: {assignment_title}
Description: {assignment_description}
Instructions: {assignment_instructions}
Maximum Score: {max_score}

STUDENT ANSWER
{student_answer}

GRADING CRITERIA
1. Understanding of the topic (25%)
2. Completeness of the answer (25%)
3. Accuracy of information (25%)
4. Clarity and organization (15%)
5. Creativity and critical thinking (10%)

Return only a valid JSON object with this exact structure:
{{
  "suggested_score": <number between 0 and {max_score}>,
  "feedback": "Detailed overall feedback about the submission",
  "strengths": ["Strength 1", "Strength 2"],
  "weaknesses": ["Weakness 1", "Weakness 2"],
  "suggestions": ["Improvement suggestion 1", "Improvement suggestion 2"],
  "grade_letter": "A/B/C/D/F"
}}
"""


async def grade_assignment(request: AssignmentGradeRequest) -> AssignmentGradeResponse:
    if not request.student_answer.strip():
        return AssignmentGradeResponse(
            success=False,
            suggested_score=0,
            max_score=request.max_score,
            percentage=0,
            extracted_text="",
            message="No readable submission content found.",
        )

    llm = get_llm()
    prompt = ChatPromptTemplate.from_messages([
        ("system", ASSIGNMENT_GRADING_SYSTEM_PROMPT),
        ("user", ASSIGNMENT_GRADING_USER_PROMPT),
    ])

    chain = prompt | llm
    response = await chain.ainvoke(request.model_dump())
    parsed = _parse_json(response.content)

    suggested_score = _clamp_score(parsed.get("suggested_score", 0), request.max_score)
    percentage = round((suggested_score / request.max_score) * 100, 2) if request.max_score > 0 else 0

    return AssignmentGradeResponse(
        success=True,
        suggested_score=suggested_score,
        max_score=request.max_score,
        percentage=percentage,
        feedback=str(parsed.get("feedback", "")).strip(),
        strengths=_normalize_string_list(parsed.get("strengths", [])),
        weaknesses=_normalize_string_list(parsed.get("weaknesses", [])),
        suggestions=_normalize_string_list(parsed.get("suggestions", [])),
        grade_letter=str(parsed.get("grade_letter") or _grade_letter(percentage)).strip(),
        extracted_text=request.student_answer,
        message="Assignment graded successfully",
    )


async def extract_submission_text(files: list[UploadFile]) -> str:
    content_parts: list[str] = []

    for file in files:
        filename = file.filename or "submission"
        file_bytes = await file.read()

        try:
            content_type = _detect_content_type(filename, file.content_type or "")

            if content_type == "image":
                text = await _extract_text_from_image(file_bytes, file.content_type or "image/png")
            else:
                text = await extract_text_from_bytes_with_fallback(file_bytes, content_type)

            if text.strip():
                content_parts.append(f"File: {filename}\n{text.strip()}")
        except Exception as exc:
            logger.warning("Failed to extract assignment attachment %s: %s", filename, exc)

    return "\n\n".join(content_parts)


async def _extract_text_from_image(file_bytes: bytes, mime_type: str) -> str:
    llm = get_llm()
    image_data = base64.b64encode(file_bytes).decode("utf-8")
    data_url = f"data:{mime_type};base64,{image_data}"

    response = await llm.ainvoke([
        HumanMessage(content=[
            {
                "type": "text",
                "text": (
                    "Extract all text content from this assignment image. "
                    "Return only the transcription, preserving the original language and structure."
                ),
            },
            {"type": "image_url", "image_url": {"url": data_url}},
        ])
    ])

    return str(response.content or "").strip()


def _detect_content_type(filename: str, mime_type: str) -> str:
    lower_name = filename.lower()
    lower_mime = mime_type.lower()

    if lower_name.endswith(".pdf") or "pdf" in lower_mime:
        return "pdf"
    if lower_name.endswith(".docx") or "wordprocessingml" in lower_mime:
        return "docx"
    if lower_name.endswith(".txt") or "text/plain" in lower_mime:
        return "txt"
    if lower_name.endswith((".jpg", ".jpeg", ".png", ".gif", ".webp")) or lower_mime.startswith("image/"):
        return "image"

    raise ValueError(f"Unsupported assignment attachment type: {filename}")


def _parse_json(content: str) -> dict:
    cleaned = content.strip()

    if cleaned.startswith("```"):
        cleaned = cleaned.split("\n", 1)[1] if "\n" in cleaned else cleaned[3:]
    if cleaned.endswith("```"):
        cleaned = cleaned[:-3]
    cleaned = cleaned.strip()

    try:
        data = json.loads(cleaned)
    except json.JSONDecodeError:
        logger.error("Failed to parse assignment grading JSON: %s", cleaned[:500])
        return {}

    return data if isinstance(data, dict) else {}


def _clamp_score(value, max_score: float) -> float:
    try:
        score = float(value)
    except (TypeError, ValueError):
        score = 0

    return round(min(max_score, max(0, score)), 2)


def _normalize_string_list(value) -> list[str]:
    if not isinstance(value, list):
        return []

    return [str(item).strip() for item in value if str(item).strip()]


def _grade_letter(percentage: float) -> str:
    if percentage >= 90:
        return "A"
    if percentage >= 80:
        return "B"
    if percentage >= 70:
        return "C"
    if percentage >= 60:
        return "D"
    return "F"
