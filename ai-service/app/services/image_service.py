import logging

from openai import AsyncOpenAI

from app.core.config import get_settings
from app.schemas.image import ImageGenerateRequest, ImageGenerateResponse

logger = logging.getLogger(__name__)
settings = get_settings()


def _normalize_image_payload(image) -> str:
    image_url = getattr(image, "url", "") if image else ""
    if image_url:
        return image_url

    b64_json = getattr(image, "b64_json", "") if image else ""
    if b64_json:
        return f"data:image/png;base64,{b64_json}"

    return ""


def _as_string(value) -> str:
    return value if isinstance(value, str) else ""


async def generate_image(request: ImageGenerateRequest) -> ImageGenerateResponse:
    """Generate a slide image through the centralized AI service."""
    client = AsyncOpenAI(api_key=settings.openai_api_key)

    try:
        response = await client.images.generate(
            model=settings.openai_image_model,
            prompt=request.prompt,
            n=1,
            size=request.size or settings.openai_image_size,
            quality=request.quality or settings.openai_image_quality,
        )

        image = response.data[0] if response.data else None
        image_url = _normalize_image_payload(image)
        revised_prompt = _as_string(getattr(image, "revised_prompt", "") if image else "")

        return ImageGenerateResponse(
            success=bool(image_url),
            prompt=request.prompt,
            image_url=image_url,
            revised_prompt=revised_prompt,
            message="Image generated successfully" if image_url else "Image generation returned no URL",
        )
    except Exception as exc:
        logger.warning("Image generation failed: %s", exc)
        return ImageGenerateResponse(
            success=False,
            prompt=request.prompt,
            image_url="",
            revised_prompt="",
            message=str(exc),
        )
