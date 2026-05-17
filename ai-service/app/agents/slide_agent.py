from app.agents.base import BaseAgent
from app.schemas.slide import SlideGenerateRequest, SlideGenerateResponse
from app.services.slide_service import generate_slides


class SlideAgent(BaseAgent):
    name = "slides"
    description = "Generate lesson presentation slides from indexed lesson content."
    request_model = SlideGenerateRequest

    async def run(self, payload: SlideGenerateRequest) -> SlideGenerateResponse:
        return await generate_slides(payload)
