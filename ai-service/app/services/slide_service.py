import json
import logging
import re

from langchain_core.prompts import ChatPromptTemplate

from app.core.dependencies import get_llm
from app.prompts.slide_prompts import SLIDE_SYSTEM_PROMPT, SLIDE_USER_PROMPT
from app.schemas.slide import SlideContent, SlideGenerateRequest, SlideGenerateResponse
from app.services.rag_service import retrieve_context

logger = logging.getLogger(__name__)


async def generate_slides(request: SlideGenerateRequest) -> SlideGenerateResponse:
    """Generate presentation slides using RAG + LLM."""
    context = retrieve_context(
        lesson_id=request.lesson_id,
        query=(
            "lesson knowledge, student learning content, concepts, definitions, formulas, "
            "examples, explanations, procedures, key ideas students need to understand"
        ),
        top_k=12,
    )
    context = _filter_student_facing_context(context)

    if not context:
        return SlideGenerateResponse(
            success=False,
            lesson_id=request.lesson_id,
            slides=[],
            total_slides=0,
            message="No lesson content found. Please upload and process a document first.",
        )

    llm = get_llm()

    prompt = ChatPromptTemplate.from_messages([
        ("system", SLIDE_SYSTEM_PROMPT),
        ("user", SLIDE_USER_PROMPT),
    ])

    additional = f"Additional instructions: {request.additional_instructions}" if request.additional_instructions else ""

    chain = prompt | llm

    response = await chain.ainvoke({
        "language": request.language,
        "num_slides": request.num_slides,
        "context": context,
        "additional_instructions": additional,
    })

    slides = _parse_slides(response.content)
    slides = _filter_teacher_oriented_slides(slides)

    return SlideGenerateResponse(
        success=True,
        lesson_id=request.lesson_id,
        slides=slides,
        total_slides=len(slides),
        message=f"Generated {len(slides)} slides successfully",
    )


def _parse_slides(content: str) -> list[SlideContent]:
    """Parse LLM output into structured slide objects."""
    cleaned = content.strip()

    if cleaned.startswith("```"):
        cleaned = cleaned.split("\n", 1)[1] if "\n" in cleaned else cleaned[3:]
    if cleaned.endswith("```"):
        cleaned = cleaned[:-3]
    cleaned = cleaned.strip()

    try:
        data = json.loads(cleaned)
    except json.JSONDecodeError:
        logger.error(f"Failed to parse slides JSON: {cleaned[:200]}")
        return []

    if isinstance(data, dict) and "slides" in data:
        data = data["slides"]

    slides = []
    for item in data:
        slides.append(SlideContent(
            slide_number=item.get("slide_number", len(slides) + 1),
            title=item.get("title", ""),
            bullet_points=item.get("bullet_points", []),
            speaker_notes=item.get("speaker_notes", ""),
            image_suggestion=item.get("image_suggestion", ""),
        ))

    return slides


TEACHER_CONTEXT_PATTERNS = [
    r"\bgiao vien\b",
    r"\bteacher\b",
    r"\bgiang vien\b",
    r"\bhoat dong day hoc\b",
    r"\bto chuc lop\b",
    r"\bquan ly lop\b",
    r"\bsu pham\b",
    r"\bphuong phap day hoc\b",
    r"\bky thuat day hoc\b",
    r"\bteacher should\b",
    r"\bthe teacher\b",
    r"\bclassroom management\b",
    r"\blesson procedure\b",
]

STUDENT_CONTENT_HINTS = [
    r"\bhoc sinh\b",
    r"\bstudent\b",
    r"\bkhai niem\b",
    r"\bdinh nghia\b",
    r"\bvi du\b",
    r"\bbai tap\b",
    r"\bcau hoi\b",
    r"\bcong thuc\b",
    r"\bquy tac\b",
    r"\bnoi dung\b",
    r"\bknowledge\b",
    r"\bconcept\b",
    r"\bdefinition\b",
    r"\bformula\b",
    r"\bexample\b",
    r"\bexercise\b",
]

TEACHER_SLIDE_PATTERNS = [
    r"\bgiao vien\b",
    r"\bteacher\b",
    r"\bgiang day\b",
    r"\bday hoc\b",
    r"\bto chuc\b",
    r"\bhuong dan\b",
    r"\bphuong phap\b",
    r"\bclassroom\b",
    r"\bfacilitate\b",
    r"\bgroup activity\b",
]


def _filter_student_facing_context(context: str) -> str:
    sections = [section.strip() for section in context.split("\n\n---\n\n") if section.strip()]
    if not sections:
        return ""

    kept_sections: list[str] = []
    fallback_sections: list[str] = []

    for section in sections:
        normalized = _normalize_text(section)
        teacher_hits = sum(1 for pattern in TEACHER_CONTEXT_PATTERNS if re.search(pattern, normalized))
        student_hits = sum(1 for pattern in STUDENT_CONTENT_HINTS if re.search(pattern, normalized))

        if teacher_hits == 0 or student_hits > teacher_hits:
            kept_sections.append(section)
            continue

        fallback_sections.append(section)

    selected = kept_sections or fallback_sections[:3]
    return "\n\n---\n\n".join(selected)


def _filter_teacher_oriented_slides(slides: list[SlideContent]) -> list[SlideContent]:
    filtered: list[SlideContent] = []

    for slide in slides:
        combined = " ".join([
            slide.title,
            slide.speaker_notes,
            slide.image_suggestion,
            *slide.bullet_points,
        ])

        normalized = _normalize_text(combined)
        if any(re.search(pattern, normalized) for pattern in TEACHER_SLIDE_PATTERNS):
            continue

        filtered.append(slide)

    for index, slide in enumerate(filtered, start=1):
        slide.slide_number = index

    return filtered


def _normalize_text(value: str) -> str:
    return re.sub(r"\s+", " ", value).strip().lower()
