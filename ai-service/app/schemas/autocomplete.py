from pydantic import BaseModel, Field


class AutocompleteRequest(BaseModel):
    text: str = Field(description="Current editor content")
    text_before_cursor: str = Field(default="", description="Content immediately before the cursor")
    text_after_cursor: str = Field(default="", description="Content immediately after the cursor")
    current_section: str = Field(default="", description="Nearest heading before the cursor")
    next_section: str = Field(default="", description="Nearest heading after the cursor")
    lesson_id: int | None = Field(default=None, description="Optional lesson ID for RAG context filtering")


class AutocompleteResponse(BaseModel):
    suggestion: str = Field(default="", description="AI-generated continuation text")
