from pydantic import BaseModel, Field


class ReportStudent(BaseModel):
    id: int
    name: str
    email: str | None = None


class ReportClass(BaseModel):
    id: int
    name: str
    code: str | None = None


class QuizResultItem(BaseModel):
    lesson_title: str | None = None
    quiz_title: str | None = None
    attempt_number: int | None = None
    score: float | None = None
    percentage: float | None = None
    submitted_at: str | None = None


class AssignmentResultItem(BaseModel):
    assignment_title: str | None = None
    score: float | None = None
    score_source: str | None = None
    max_score: int | None = None
    percentage: float | None = None
    is_late: bool = False
    teacher_feedback: str | None = None
    ai_feedback: str | None = None
    submitted_at: str | None = None


class CompetencyReportGenerateRequest(BaseModel):
    student: ReportStudent
    class_info: ReportClass = Field(alias="class")
    report_type: str = Field(default="class")
    average_score: float | None = None
    quiz_results: list[QuizResultItem] = Field(default_factory=list)
    assignment_results: list[AssignmentResultItem] = Field(default_factory=list)

    class Config:
        populate_by_name = True


class CompetencyReportGenerateResponse(BaseModel):
    success: bool
    overall_summary: str
    strengths: list[str]
    weaknesses: list[str]
    recommendations: list[str]
    model_used: str
    message: str
