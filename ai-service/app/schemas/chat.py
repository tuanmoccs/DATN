from pydantic import BaseModel, Field


class ChatMessage(BaseModel):
    role: str = Field(description="user or assistant")
    content: str


class ChatRequest(BaseModel):
    lesson_id: int
    student_id: int
    message: str
    quiz_context: str = ""
    conversation_history: list[ChatMessage] = Field(default_factory=list)


class ChatResponse(BaseModel):
    success: bool
    answer: str
    sources: list[str] = Field(default_factory=list, description="Source chunks used")
    message: str
