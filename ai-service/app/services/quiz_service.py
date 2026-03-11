import json
import logging

from langchain_core.prompts import ChatPromptTemplate

from app.core.dependencies import get_llm
from app.prompts.quiz_prompts import QUIZ_SYSTEM_PROMPT, QUIZ_USER_PROMPT
from app.schemas.quiz import (
    QuizGenerateRequest,
    QuizGenerateResponse,
    QuizOption,
    QuizQuestion,
)
from app.services.rag_service import retrieve_context

logger = logging.getLogger(__name__)


async def generate_quiz(request: QuizGenerateRequest) -> QuizGenerateResponse:
    """Generate quiz questions using RAG + LLM."""
    context = retrieve_context(lesson_id=request.lesson_id)

    if not context:
        return QuizGenerateResponse(
            success=False,
            lesson_id=request.lesson_id,
            questions=[],
            total_questions=0,
            message="No lesson content found. Please upload and process a document first.",
        )

    llm = get_llm()

    prompt = ChatPromptTemplate.from_messages([
        ("system", QUIZ_SYSTEM_PROMPT),
        ("user", QUIZ_USER_PROMPT),
    ])

    additional = f"Additional instructions: {request.additional_instructions}" if request.additional_instructions else ""

    chain = prompt | llm

    response = await chain.ainvoke({
        "language": request.language,
        "difficulty": request.difficulty,
        "num_questions": request.num_questions,
        "context": context,
        "additional_instructions": additional,
    })

    questions = _parse_questions(response.content)

    return QuizGenerateResponse(
        success=True,
        lesson_id=request.lesson_id,
        questions=questions,
        total_questions=len(questions),
        message=f"Generated {len(questions)} questions successfully",
    )


def _parse_questions(content: str) -> list[QuizQuestion]:
    """Parse LLM output into structured quiz question objects."""
    cleaned = content.strip()

    if cleaned.startswith("```"):
        cleaned = cleaned.split("\n", 1)[1] if "\n" in cleaned else cleaned[3:]
    if cleaned.endswith("```"):
        cleaned = cleaned[:-3]
    cleaned = cleaned.strip()

    try:
        data = json.loads(cleaned)
    except json.JSONDecodeError:
        logger.error(f"Failed to parse quiz JSON: {cleaned[:200]}")
        return []

    if isinstance(data, dict) and "questions" in data:
        data = data["questions"]

    questions = []
    for item in data:
        options = []
        for opt in item.get("options", []):
            options.append(QuizOption(
                option_text=opt.get("option_text", ""),
                is_correct=opt.get("is_correct", False),
                explanation=opt.get("explanation", ""),
            ))

        questions.append(QuizQuestion(
            question_number=item.get("question_number", len(questions) + 1),
            content=item.get("content", ""),
            question_type=item.get("question_type", "multiple_choice"),
            options=options,
            explanation=item.get("explanation", ""),
            points=item.get("points", 1),
        ))

    return questions
