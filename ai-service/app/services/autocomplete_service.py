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
    text = request.text.strip()

    if not text:
        return AutocompleteResponse(suggestion="")

    # Take the tail of the text as the query for vector search
    query_text = text[-QUERY_TAIL_LENGTH:]

    # Retrieve RAG context if lesson_id is provided
    rag_context = ""
    if request.lesson_id:
        rag_context = retrieve_context(
            lesson_id=request.lesson_id,
            query=query_text,
            top_k=3,
        )

    # Limit current text sent to LLM to avoid token waste
    current_text = text[-MAX_CONTEXT_LENGTH:]

    llm = get_llm()

    prompt = ChatPromptTemplate.from_messages([
        ("system", AUTOCOMPLETE_SYSTEM_PROMPT),
        ("user", AUTOCOMPLETE_USER_PROMPT),
    ])

    chain = prompt | llm

    response = await chain.ainvoke({
        "context": rag_context or "Không có tài liệu tham khảo.",
        "current_text": current_text,
    })

    suggestion = response.content.strip()

    return AutocompleteResponse(suggestion=suggestion)
