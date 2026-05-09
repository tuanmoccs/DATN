from pydantic import BaseModel, Field


class ImageGenerateRequest(BaseModel):
    prompt: str = Field(min_length=1, description="Prompt used to generate the image")
    size: str = Field(default="", description="Optional image size override")
    quality: str = Field(default="", description="Optional image quality override")


class ImageGenerateResponse(BaseModel):
    success: bool
    prompt: str
    image_url: str = ""
    revised_prompt: str = ""
    message: str = ""
