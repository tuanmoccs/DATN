from app.agents.base import BaseAgent
from app.schemas.chat import ChatRequest, ChatResponse
from app.services.chat_service import chat_with_lesson


class ChatAgent(BaseAgent):
    name = "chat"
    description = "Answer student questions using lesson-aware RAG."
    request_model = ChatRequest

    async def run(self, payload: ChatRequest) -> ChatResponse:
        return await chat_with_lesson(payload)
