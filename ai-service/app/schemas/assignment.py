from pydantic import BaseModel, Field


class AssignmentCriterionScore(BaseModel):
    criterion: str
    max_score: float = Field(ge=0)
    suggested_score: float = Field(ge=0)
    reason: str = Field(default="")


class AssignmentAnnotation(BaseModel):
    id: str
    source_id: str
    file_name: str
    line_start: int | None = Field(default=None, ge=1)
    line_end: int | None = Field(default=None, ge=1)
    quote: str = Field(default="")
    verdict: str
    explanation: str
    correction: str = Field(default="")
    criterion: str = Field(default="")
    score_impact: float = Field(default=0)
    confidence: float = Field(default=0, ge=0, le=1)


class AssignmentGradeRequest(BaseModel):
    assignment_title: str
    assignment_description: str = Field(default="")
    assignment_instructions: str = Field(default="")
    assignment_reference_text: str = Field(default="")
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
    criteria: list[AssignmentCriterionScore] = Field(default_factory=list)
    annotations: list[AssignmentAnnotation] = Field(default_factory=list)
    missing_requirements: list[str] = Field(default_factory=list)
    confidence: float = Field(default=0, ge=0, le=1)
    grade_letter: str = Field(default="")
    extracted_text: str = Field(default="")
    reference_extracted_text: str = Field(default="")
    message: str
