import json
import logging

from langchain_core.prompts import ChatPromptTemplate

from app.core.dependencies import get_llm
from app.prompts.slide_prompts import SLIDE_SYSTEM_PROMPT, SLIDE_USER_PROMPT
from app.schemas.slide import SlideContent, SlideGenerateRequest, SlideGenerateResponse
from app.services.rag_service import retrieve_context

logger = logging.getLogger(__name__)


async def generate_slides(request: SlideGenerateRequest) -> SlideGenerateResponse:
    """Generate presentation slides using RAG + LLM."""
    context = retrieve_context(lesson_id=request.lesson_id)

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
