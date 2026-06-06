import json
import logging
import re

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
    context = retrieve_context(
        lesson_id=request.lesson_id,
        query=(
            "student knowledge, concepts, definitions, formulas, worked examples, "
            "exercises, facts, principles, content students need to learn"
        ),
        top_k=12,
    )
    context = _filter_student_facing_context(context)

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

    json_llm = llm.bind(response_format={"type": "json_object"})
    chain = prompt | json_llm

    response = await chain.ainvoke({
        "language": request.language,
        "difficulty": request.difficulty,
        "num_questions": request.num_questions,
        "context": context,
        "additional_instructions": additional,
    })

    questions = _parse_questions(response.content)
    questions = _filter_teacher_oriented_questions(questions)

    return QuizGenerateResponse(
        success=True,
        lesson_id=request.lesson_id,
        questions=questions,
        total_questions=len(questions),
        message=f"Generated {len(questions)} questions successfully",
    )


def _parse_questions(content: str) -> list[QuizQuestion]:
    """Parse LLM output into structured quiz question objects."""
    cleaned = _extract_json_payload(content)

    try:
        data = json.loads(cleaned)
    except json.JSONDecodeError as e:
        logger.error("Failed to parse quiz JSON: %s | preview=%s", e, cleaned[:500])
        return []

    if isinstance(data, dict) and "questions" in data:
        data = data["questions"]
    elif isinstance(data, dict):
        logger.error("Quiz JSON object does not contain questions key: %s", list(data.keys()))
        return []
    elif not isinstance(data, list):
        logger.error("Quiz JSON root must be an object or array, got %s", type(data).__name__)
        return []

    questions = []
    for item in data:
        if not isinstance(item, dict):
            continue

        options = []
        for opt in item.get("options", []):
            if not isinstance(opt, dict):
                continue
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


def _extract_json_payload(content: str) -> str:
    cleaned = content.strip()

    if cleaned.startswith("```"):
        cleaned = cleaned.split("\n", 1)[1] if "\n" in cleaned else cleaned[3:]
        if cleaned.lower().startswith("json\n"):
            cleaned = cleaned.split("\n", 1)[1]
    if cleaned.endswith("```"):
        cleaned = cleaned[:-3]

    cleaned = cleaned.strip()
    first_object = cleaned.find("{")
    first_array = cleaned.find("[")

    if first_object == -1 and first_array == -1:
        return cleaned

    if first_array == -1 or (first_object != -1 and first_object < first_array):
        start = first_object
        end = cleaned.rfind("}")
    else:
        start = first_array
        end = cleaned.rfind("]")

    if start == -1 or end == -1 or end <= start:
        return cleaned

    return cleaned[start:end + 1]


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
    r"\blearning objective for teacher\b",
    r"\binstruction for teacher\b",
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

TEACHER_QUESTION_PATTERNS = [
    r"\bgiao vien\b",
    r"\bteacher\b",
    r"\bgiang day\b",
    r"\bday hoc\b",
    r"\bto chuc\b",
    r"\bhuong dan\b",
    r"\bphuong phap\b",
    r"\bclassroom\b",
    r"\blesson delivery\b",
    r"\bfacilitate\b",
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

        if teacher_hits == 0:
            kept_sections.append(section)
            continue

        if student_hits > teacher_hits:
            kept_sections.append(section)
            continue

        fallback_sections.append(section)

    selected = kept_sections or fallback_sections[:3]
    return "\n\n---\n\n".join(selected)


def _filter_teacher_oriented_questions(questions: list[QuizQuestion]) -> list[QuizQuestion]:
    filtered: list[QuizQuestion] = []

    for question in questions:
        combined = " ".join([
            question.content,
            question.explanation,
            *[option.option_text for option in question.options],
            *[option.explanation for option in question.options],
        ])

        normalized = _normalize_text(combined)
        if any(re.search(pattern, normalized) for pattern in TEACHER_QUESTION_PATTERNS):
            continue

        filtered.append(question)

    for index, question in enumerate(filtered, start=1):
        question.question_number = index

    return filtered


def _normalize_text(value: str) -> str:
    return re.sub(r"\s+", " ", value).strip().lower()
