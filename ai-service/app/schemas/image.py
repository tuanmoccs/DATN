from pydantic import BaseModel, Field, field_validator


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

    @field_validator("prompt", "image_url", "revised_prompt", "message", mode="before")
    @classmethod
    def none_to_empty_string(cls, value):
        return "" if value is None else value
