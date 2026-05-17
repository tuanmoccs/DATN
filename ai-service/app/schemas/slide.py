from pydantic import BaseModel, Field


class SlideContent(BaseModel):
    slide_number: int
    title: str
    bullet_points: list[str] = Field(description="Main content bullet points")
    speaker_notes: str = Field(default="", description="Notes for the teacher")
    image_suggestion: str = Field(default="", description="Suggested image description")


class SlideGenerateRequest(BaseModel):
    lesson_id: int
    num_slides: int = Field(default=10, ge=3, le=30)
    language: str = Field(default="English")
    additional_instructions: str = Field(default="")


class SlideGenerateResponse(BaseModel):
    success: bool
    lesson_id: int
    slides: list[SlideContent]
    total_slides: int
    message: str
