import logging

from langchain_core.prompts import ChatPromptTemplate

from app.core.dependencies import get_llm
from app.prompts.chat_prompts import CHAT_SYSTEM_PROMPT, CHAT_USER_PROMPT
from app.schemas.chat import ChatRequest, ChatResponse
from app.services.rag_service import retrieve_context_with_sources

logger = logging.getLogger(__name__)


async def chat_with_lesson(request: ChatRequest) -> ChatResponse:
    """Answer student questions using RAG + LLM."""
    context, sources = retrieve_context_with_sources(
        lesson_id=request.lesson_id,
        query=request.message,
        top_k=5,
    )

    quiz_context = request.quiz_context.strip()

    if not context and not quiz_context:
        return ChatResponse(
            success=False,
            answer="",
            sources=[],
            message="No lesson content found for this lesson.",
        )

    llm = get_llm()

    history_text = ""
    if request.conversation_history:
        history_lines = []
        for msg in request.conversation_history[-6:]:
            history_lines.append(f"{msg.role}: {msg.content}")
        history_text = "\n".join(history_lines)

    prompt = ChatPromptTemplate.from_messages([
        ("system", CHAT_SYSTEM_PROMPT),
        ("user", CHAT_USER_PROMPT),
    ])

    chain = prompt | llm

    response = await chain.ainvoke({
        "context": context or "No retrieved lesson context.",
        "quiz_context": quiz_context or "No quiz context provided.",
        "conversation_history": history_text or "No previous conversation.",
        "message": request.message,
    })

    return ChatResponse(
        success=True,
        answer=response.content,
        sources=sources,
        message="Response generated successfully",
    )
