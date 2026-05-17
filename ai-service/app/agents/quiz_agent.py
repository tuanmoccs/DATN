from app.agents.base import BaseAgent
from app.schemas.quiz import QuizGenerateRequest, QuizGenerateResponse
from app.services.quiz_service import generate_quiz


class QuizAgent(BaseAgent):
    name = "quiz"
    description = "Generate quiz questions from indexed lesson content."
    request_model = QuizGenerateRequest

    async def run(self, payload: QuizGenerateRequest) -> QuizGenerateResponse:
        return await generate_quiz(payload)
