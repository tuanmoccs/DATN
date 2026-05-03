from pydantic import BaseModel, Field


class AssignmentGradeRequest(BaseModel):
    assignment_title: str
    assignment_description: str = Field(default="")
    assignment_instructions: str = Field(default="")
    max_score: float = Field(default=100, gt=0)
    student_answer: str = Field(default="")


class AssignmentGradeResponse(BaseModel):
    success: bool
    suggested_score: float
    max_score: float
    percentage: float
    feedback: str = Field(default="")
    strengths: list[str] = Field(default_factory=list)
    weaknesses: list[str] = Field(default_factory=list)
    suggestions: list[str] = Field(default_factory=list)
    grade_letter: str = Field(default="")
    extracted_text: str = Field(default="")
    message: str
