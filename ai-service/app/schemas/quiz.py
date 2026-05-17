from pydantic import BaseModel, Field


class QuizOption(BaseModel):
    option_text: str
    is_correct: bool
    explanation: str = Field(default="")


class QuizQuestion(BaseModel):
    question_number: int
    content: str
    question_type: str = Field(default="multiple_choice")
    options: list[QuizOption]
    explanation: str = Field(default="", description="Explanation for the correct answer")
    points: int = Field(default=1)


class QuizGenerateRequest(BaseModel):
    lesson_id: int
    num_questions: int = Field(default=10, ge=1, le=50)
    language: str = Field(default="English")
    difficulty: str = Field(default="medium", description="easy, medium, hard")
    additional_instructions: str = Field(default="")


class QuizGenerateResponse(BaseModel):
    success: bool
    lesson_id: int
    questions: list[QuizQuestion]
    total_questions: int
    message: str
