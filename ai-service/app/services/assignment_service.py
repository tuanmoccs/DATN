import base64
import json
import logging
import re

from fastapi import UploadFile
from langchain_core.messages import HumanMessage, SystemMessage

from app.core.dependencies import get_llm
from app.schemas.assignment import (
    AssignmentAnnotation,
    AssignmentCriterionScore,
    AssignmentGradeRequest,
    AssignmentGradeResponse,
)
from app.services.document_processor import extract_text_from_bytes_with_fallback

logger = logging.getLogger(__name__)

ASSIGNMENT_GRADING_SYSTEM_PROMPT = (
    "You are an experienced and fair teacher who grades student assignments. "
    "Every judgment must be grounded in the student's actual submission. "
    "Provide constructive, detailed feedback. Respond only with valid JSON. "
    "Use the same language as the assignment or student answer."
)

ASSIGNMENT_GRADING_USER_PROMPT = """Grade this student assignment submission.

ASSIGNMENT INFORMATION
Title: {assignment_title}
Description: {assignment_description}
Instructions: {assignment_instructions}
Maximum Score: {max_score}

ASSIGNMENT REFERENCE MATERIALS / TEACHER ATTACHMENTS
{assignment_reference_text}

STUDENT ANSWER
{student_answer}

GRADING CRITERIA
1. Correctly answers the assignment requirements and follows instructions (30%)
2. Accuracy compared with the teacher reference materials and expected concepts (30%)
3. Completeness of the answer (20%)
4. Clarity and organization (10%)
5. Reasoning, creativity, and critical thinking (10%)

Important:
- If teacher attachments are provided, use them as the primary grading source of truth.
- If teacher attachments conflict with the title, description, or instructions, prioritize the teacher attachments.
- Treat title, description, and instructions as supplementary context when teacher attachments are present.
- Do not grade only by general knowledge. Compare the student answer against the assignment requirements.
- If reference materials are empty, grade from the title, description, and instructions only.
- Mention important missing requirements in weaknesses and suggestions.
- The student answer contains source markers and numbered OCR lines such as [L001].
- For every correct, incorrect, or partially correct claim that affects the score, create an annotation.
- Copy annotation.quote exactly from the OCR text, without the [Lxxx] prefix.
- source_id and file_name must match the source marker containing the quote.
- line_start and line_end must cover the quoted text.
- Use verdict only from: correct, incorrect, partial, unclear.
- For incorrect or partial work, explain the error and put the corrected answer in correction.
- If the image or OCR is too unclear to verify, use verdict "unclear", an empty quote, and do not deduct points.
- The attached original image is the source of truth. OCR text is only an index for quoting and locating evidence.
- If the OCR text conflicts with what is visibly written in the image, do not guess. Use verdict "unclear",
  score_impact 0, and explain what the teacher needs to inspect.
- score_impact is the signed point contribution already included in suggested_score:
  positive for awarded credit and negative for a deduction.
- Only use non-zero score_impact when confidence is at least 0.65.
- Never invent a quote or a location. Missing work belongs in missing_requirements, not in annotations.
- The sum of criteria.max_score must equal the assignment Maximum Score.
- The sum of criteria.suggested_score must equal suggested_score.

Return only a valid JSON object with this exact structure:
{{
  "suggested_score": <number between 0 and {max_score}>,
  "feedback": "Detailed overall feedback about the submission",
  "strengths": ["Strength 1", "Strength 2"],
  "weaknesses": ["Weakness 1", "Weakness 2"],
  "suggestions": ["Improvement suggestion 1", "Improvement suggestion 2"],
  "criteria": [
    {{
      "criterion": "Criterion name",
      "max_score": 30,
      "suggested_score": 20,
      "reason": "Reason based on the submission"
    }}
  ],
  "annotations": [
    {{
      "id": "ann-1",
      "source_id": "IMG001",
      "file_name": "student-work.jpg",
      "line_start": 1,
      "line_end": 2,
      "quote": "Exact text copied from the numbered OCR lines",
      "verdict": "correct/incorrect/partial/unclear",
      "explanation": "Why this exact part is correct or incorrect",
      "correction": "Correct answer or improved version; empty when not needed",
      "criterion": "Related grading criterion",
      "score_impact": -1,
      "confidence": 0.9
    }}
  ],
  "missing_requirements": ["Required item that the student did not include"],
  "confidence": 0.85,
  "grade_letter": "A/B/C/D/F"
}}
"""


async def grade_assignment(request: AssignmentGradeRequest, student_images: list[str] = None) -> AssignmentGradeResponse:
    if not request.student_answer.strip() and not student_images:
        return AssignmentGradeResponse(
            success=False,
            suggested_score=0,
            max_score=request.max_score,
            percentage=0,
            extracted_text="",
            reference_extracted_text=request.assignment_reference_text,
            message="No readable submission content found.",
        )

    llm = get_llm().bind(temperature=0)
    
    # Format the prompt text
    formatted_user_prompt = ASSIGNMENT_GRADING_USER_PROMPT.format(
        assignment_title=request.assignment_title,
        assignment_description=request.assignment_description,
        assignment_instructions=request.assignment_instructions,
        max_score=request.max_score,
        assignment_reference_text=request.assignment_reference_text,
        student_answer=request.student_answer,
    )
    
    # Add a notice about the images if present
    if student_images:
        formatted_user_prompt += (
            "\nNote: The actual images of the student's submission are also attached below. "
            "They are attached in the same order as the image STUDENT_SOURCE blocks. "
            "Inspect them carefully to grade handwriting, formulas, steps, layout, and drawings. "
            "Use the numbered OCR text for grounded quotes and use the image to verify visual details."
        )

    # Construct messages for LangChain
    user_content = [
        {
            "type": "text",
            "text": formatted_user_prompt
        }
    ]
    
    if student_images:
        for img_url in student_images:
            user_content.append({
                "type": "image_url",
                "image_url": {"url": img_url, "detail": "high"}
            })

    messages = [
        SystemMessage(content=ASSIGNMENT_GRADING_SYSTEM_PROMPT),
        HumanMessage(content=user_content)
    ]

    response = await llm.ainvoke(messages)
    parsed = _parse_json(_response_text(response.content))

    criteria = _normalize_criteria(parsed.get("criteria", []), request.max_score)
    suggested_score = (
        _clamp_score(sum(item.suggested_score for item in criteria), request.max_score)
        if criteria
        else _clamp_score(parsed.get("suggested_score", 0), request.max_score)
    )
    percentage = round((suggested_score / request.max_score) * 100, 2) if request.max_score > 0 else 0
    annotations = _validate_annotations(parsed.get("annotations", []), request.student_answer)

    return AssignmentGradeResponse(
        success=True,
        suggested_score=suggested_score,
        max_score=request.max_score,
        percentage=percentage,
        feedback=str(parsed.get("feedback", "")).strip(),
        strengths=_normalize_string_list(parsed.get("strengths", [])),
        weaknesses=_normalize_string_list(parsed.get("weaknesses", [])),
        suggestions=_normalize_string_list(parsed.get("suggestions", [])),
        criteria=criteria,
        annotations=annotations,
        missing_requirements=_normalize_string_list(parsed.get("missing_requirements", [])),
        confidence=_clamp_confidence(parsed.get("confidence", 0)),
        grade_letter=str(parsed.get("grade_letter") or _grade_letter(percentage)).strip(),
        extracted_text=request.student_answer,
        reference_extracted_text=request.assignment_reference_text,
        message="Assignment graded successfully",
    )


async def extract_submission_data(files: list[UploadFile]) -> tuple[str, list[str]]:
    content_parts: list[str] = []
    images: list[str] = []

    for file_index, file in enumerate(files, start=1):
        filename = file.filename or "submission"
        file_bytes = await file.read()

        try:
            content_type = _detect_content_type(filename, file.content_type or "")

            if content_type == "image":
                mime_type = _image_mime_type(filename, file.content_type or "")
                text = await _extract_text_from_image(file_bytes, mime_type)
                source_id = f"IMG{file_index:03d}"

                # Base64 encode for vision input
                image_data = base64.b64encode(file_bytes).decode("utf-8")
                data_url = f"data:{mime_type};base64,{image_data}"
                images.append(data_url)
            else:
                text = await extract_text_from_bytes_with_fallback(file_bytes, content_type)
                source_id = f"DOC{file_index:03d}"

            content_parts.append(_format_submission_source(source_id, filename, content_type, text))
        except Exception as exc:
            logger.warning("Failed to extract assignment file %s: %s", filename, exc)

    return "\n\n".join(content_parts), images


async def extract_reference_text(files: list[UploadFile]) -> str:
    return await extract_files_text(files, default_filename="assignment_reference")


async def extract_files_text(files: list[UploadFile], default_filename: str) -> str:
    content_parts: list[str] = []

    for file in files:
        filename = file.filename or default_filename
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
            logger.warning("Failed to extract assignment file %s: %s", filename, exc)

    return "\n\n".join(content_parts)


async def _extract_text_from_image(file_bytes: bytes, mime_type: str) -> str:
    llm = get_llm().bind(temperature=0)
    image_data = base64.b64encode(file_bytes).decode("utf-8")
    data_url = f"data:{mime_type};base64,{image_data}"

    response = await llm.ainvoke([
        HumanMessage(content=[
            {
                "type": "text",
                "text": (
                    "Transcribe every readable part of this student assignment image. "
                    "Preserve the original language, line breaks, formulas, calculation steps, "
                    "question numbers, crossed-out text when still readable, and answer order. "
                    "Read mathematical operators, superscripts, subscripts, decimals, and minus signs carefully. "
                    "When a word, number, or symbol is genuinely unreadable, write [UNCLEAR] instead of guessing. "
                    "Return only the transcription. Do not correct, explain, summarize, or grade it."
                ),
            },
            {"type": "image_url", "image_url": {"url": data_url, "detail": "high"}},
        ])
    ])

    return _response_text(response.content).strip()


def _format_submission_source(source_id: str, filename: str, content_type: str, text: str) -> str:
    lines = [line.strip() for line in text.splitlines() if line.strip()]
    numbered_text = "\n".join(f"[L{index:03d}] {line}" for index, line in enumerate(lines, start=1))
    if not numbered_text:
        numbered_text = "[OCR_UNREADABLE] No readable text was extracted. Inspect the attached image directly."

    return (
        f'=== STUDENT_SOURCE source_id="{source_id}" file_name={json.dumps(filename, ensure_ascii=False)} '
        f'type="{content_type}" ===\n'
        f"{numbered_text}\n"
        "=== END_STUDENT_SOURCE ==="
    )


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


def _image_mime_type(filename: str, declared_mime_type: str) -> str:
    if declared_mime_type.lower().startswith("image/"):
        return declared_mime_type.lower()

    extension_map = {
        ".jpg": "image/jpeg",
        ".jpeg": "image/jpeg",
        ".png": "image/png",
        ".gif": "image/gif",
        ".webp": "image/webp",
    }
    lower_name = filename.lower()
    for extension, mime_type in extension_map.items():
        if lower_name.endswith(extension):
            return mime_type
    return "image/png"


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


def _response_text(content) -> str:
    if isinstance(content, str):
        return content
    if isinstance(content, list):
        parts = []
        for item in content:
            if isinstance(item, dict) and item.get("type") == "text":
                parts.append(str(item.get("text", "")))
        return "\n".join(part for part in parts if part)
    return str(content or "")


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


def _normalize_criteria(value, max_score: float) -> list[AssignmentCriterionScore]:
    if not isinstance(value, list):
        return []

    criteria = []
    for item in value:
        if not isinstance(item, dict):
            continue
        criterion = str(item.get("criterion", "")).strip()
        if not criterion:
            continue
        criterion_max = max(0, _safe_float(item.get("max_score", 0)))
        criterion_score = min(criterion_max, max(0, _safe_float(item.get("suggested_score", 0))))
        criteria.append(AssignmentCriterionScore(
            criterion=criterion,
            max_score=criterion_max,
            suggested_score=criterion_score,
            reason=str(item.get("reason", "")).strip(),
        ))
    total_max = sum(item.max_score for item in criteria)
    if not criteria or total_max <= 0:
        return []

    scale = max_score / total_max
    if abs(scale - 1) > 0.0001:
        criteria = [
            AssignmentCriterionScore(
                criterion=item.criterion,
                max_score=round(item.max_score * scale, 2),
                suggested_score=round(item.suggested_score * scale, 2),
                reason=item.reason,
            )
            for item in criteria
        ]

    return criteria


def _validate_annotations(value, student_answer: str) -> list[AssignmentAnnotation]:
    if not isinstance(value, list):
        return []

    sources = _parse_submission_sources(student_answer)
    annotations = []
    used_ids = set()

    for index, item in enumerate(value, start=1):
        if not isinstance(item, dict):
            continue

        verdict = str(item.get("verdict", "")).strip().lower()
        if verdict not in {"correct", "incorrect", "partial", "unclear"}:
            continue

        source_id = str(item.get("source_id", "")).strip()
        source = sources.get(source_id)
        quote = str(item.get("quote", "")).strip()
        confidence = _clamp_confidence(item.get("confidence", 0))
        if confidence < 0.65:
            verdict = "unclear"

        if verdict == "unclear" and not quote:
            if not source:
                continue
            line_start = None
            line_end = None
        else:
            if not source or not quote:
                continue
            matched_lines = _find_quote_lines(source["lines"], quote)
            if not matched_lines:
                logger.warning("Discarding ungrounded assignment annotation: %s", quote[:120])
                continue
            line_start, line_end = matched_lines

        annotation_id = str(item.get("id") or f"ann-{index}").strip()
        if not annotation_id or annotation_id in used_ids:
            annotation_id = f"ann-{index}"
        used_ids.add(annotation_id)

        score_impact = _safe_float(item.get("score_impact", 0))
        if confidence < 0.65:
            score_impact = 0

        annotations.append(AssignmentAnnotation(
            id=annotation_id,
            source_id=source_id,
            file_name=source["file_name"],
            line_start=line_start,
            line_end=line_end,
            quote=quote,
            verdict=verdict,
            explanation=str(item.get("explanation", "")).strip(),
            correction=str(item.get("correction", "")).strip(),
            criterion=str(item.get("criterion", "")).strip(),
            score_impact=score_impact,
            confidence=confidence,
        ))

    return annotations


def _parse_submission_sources(student_answer: str) -> dict[str, dict]:
    source_pattern = re.compile(
        r'=== STUDENT_SOURCE source_id="(?P<source_id>[^"]+)" '
        r'file_name=(?P<file_name>"(?:\\.|[^"])*") type="(?P<type>[^"]+)" ===\n'
        r"(?P<body>.*?)\n=== END_STUDENT_SOURCE ===",
        re.DOTALL,
    )
    line_pattern = re.compile(r"^\[L(?P<number>\d+)\]\s*(?P<text>.*)$")
    sources = {}

    for match in source_pattern.finditer(student_answer):
        lines = []
        for raw_line in match.group("body").splitlines():
            line_match = line_pattern.match(raw_line)
            if line_match:
                lines.append((int(line_match.group("number")), line_match.group("text").strip()))
        sources[match.group("source_id")] = {
            "file_name": json.loads(match.group("file_name")),
            "type": match.group("type"),
            "lines": lines,
        }

    return sources


def _find_quote_lines(lines: list[tuple[int, str]], quote: str) -> tuple[int, int] | None:
    normalized_quote = _normalize_evidence_text(quote)
    if not normalized_quote:
        return None

    for start_index in range(len(lines)):
        combined_parts = []
        for end_index in range(start_index, len(lines)):
            combined_parts.append(lines[end_index][1])
            combined = _normalize_evidence_text(" ".join(combined_parts))
            if normalized_quote in combined:
                return lines[start_index][0], lines[end_index][0]
            if len(combined) > len(normalized_quote) * 2 + 40:
                break
    return None


def _normalize_evidence_text(value: str) -> str:
    return re.sub(r"\s+", " ", value).strip().casefold()


def _safe_float(value) -> float:
    try:
        return round(float(value), 2)
    except (TypeError, ValueError):
        return 0


def _clamp_confidence(value) -> float:
    return min(1, max(0, _safe_float(value)))


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
