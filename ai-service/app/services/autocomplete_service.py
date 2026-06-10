import logging

from langchain_core.prompts import ChatPromptTemplate

from app.core.dependencies import get_llm
from app.prompts.autocomplete_prompts import AUTOCOMPLETE_SYSTEM_PROMPT, AUTOCOMPLETE_USER_PROMPT
from app.schemas.autocomplete import AutocompleteRequest, AutocompleteResponse
from app.services.rag_service import retrieve_context

logger = logging.getLogger(__name__)

# Max characters to send as query for embedding
QUERY_TAIL_LENGTH = 500
# Max characters of current text to include in prompt
MAX_CONTEXT_LENGTH = 1500


async def generate_autocomplete(request: AutocompleteRequest) -> AutocompleteResponse:
    """Generate autocomplete suggestion using RAG + LLM."""
    text_before_cursor = (request.text_before_cursor or request.text).strip()
    text_after_cursor = request.text_after_cursor.strip()

    if not text_before_cursor:
        return AutocompleteResponse(suggestion="")

    query_text = " ".join(filter(None, [
        request.current_section,
        text_before_cursor[-QUERY_TAIL_LENGTH:],
    ]))

    # Retrieve RAG context if lesson_id is provided
    rag_context = ""
    if request.lesson_id:
        rag_context = retrieve_context(
            lesson_id=request.lesson_id,
            query=query_text,
            top_k=3,
        )

    current_text = text_before_cursor[-MAX_CONTEXT_LENGTH:]
    following_text = text_after_cursor[:1000]

    llm = get_llm().bind(max_tokens=120)

    prompt = ChatPromptTemplate.from_messages([
        ("system", AUTOCOMPLETE_SYSTEM_PROMPT),
        ("user", AUTOCOMPLETE_USER_PROMPT),
    ])

    chain = prompt | llm

    response = await chain.ainvoke({
        "context": rag_context or "Không có tài liệu tham khảo.",
        "text_before_cursor": current_text,
        "text_after_cursor": following_text or "Không có nội dung phía sau con trỏ.",
        "current_section": request.current_section or "Không xác định",
        "next_section": request.next_section or "Không có mục tiếp theo",
    })

    suggestion = response.content.strip()

    return AutocompleteResponse(suggestion=suggestion)
