from app.agents.base import BaseAgent
from app.schemas.image import ImageGenerateRequest, ImageGenerateResponse
from app.services.image_service import generate_image


class ImageAgent(BaseAgent):
    name = "image"
    description = "Generate slide images from prompts through the centralized AI service."
    request_model = ImageGenerateRequest

    async def run(self, payload: ImageGenerateRequest) -> ImageGenerateResponse:
        return await generate_image(payload)
