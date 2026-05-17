from pydantic import BaseModel, Field


class AutocompleteRequest(BaseModel):
    text: str = Field(description="Current editor content")
    lesson_id: int | None = Field(default=None, description="Optional lesson ID for RAG context filtering")


class AutocompleteResponse(BaseModel):
    suggestion: str = Field(default="", description="AI-generated continuation text")
