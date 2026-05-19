from typing import Any, Literal

from pydantic import BaseModel, Field

from app.schemas.chat import ChatMessage


class OrchestratorTask(BaseModel):
    task_id: str = Field(default="", description="Optional client-side task identifier")
    agent: str = Field(description="Specialist agent to execute")
    description: str = Field(default="", description="Human-readable reason for this step")
    payload: dict[str, Any] = Field(default_factory=dict)


class OrchestratorRequest(BaseModel):
    goal: str = Field(min_length=1, description="User goal for the orchestrator")
    lesson_id: int | None = Field(default=None, description="Shared lesson ID for lesson-based agents")
    student_id: int | None = Field(default=None, description="Shared student ID for chat workflows")
    language: str = Field(default="Vietnamese")
    difficulty: str = Field(default="medium")
    num_questions: int = Field(default=5, ge=1, le=50)
    num_slides: int = Field(default=6, ge=3, le=30)
    image_prompt: str | None = Field(default=None)
    conversation_history: list[ChatMessage] = Field(default_factory=list)
    tasks: list[OrchestratorTask] = Field(
        default_factory=list,
        description="Explicit execution plan. If omitted, the orchestrator infers a plan from goal.",
    )
    enabled_agents: list[str] = Field(
        default_factory=list,
        description="Optional allow-list of specialist agents the orchestrator can use.",
    )
    context: dict[str, Any] = Field(
        default_factory=dict,
        description="Extra structured data for specialist agents, such as report payloads.",
    )
    parallel: bool = Field(default=True, description="Run independent planned tasks concurrently")
    continue_on_error: bool = Field(default=True)


class OrchestratorStepResult(BaseModel):
    task_id: str
    agent: str
    description: str = ""
    status: Literal["success", "failed", "skipped"]
    payload: dict[str, Any] = Field(default_factory=dict)
    result: dict[str, Any] = Field(default_factory=dict)
    error: str = ""


class OrchestratorResponse(BaseModel):
    success: bool
    goal: str
    plan: list[OrchestratorTask]
    steps: list[OrchestratorStepResult]
    final_answer: str = ""
    message: str
