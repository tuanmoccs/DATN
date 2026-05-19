import asyncio
import re
import unicodedata
from collections.abc import Awaitable, Callable
from typing import Any

from pydantic import BaseModel

from app.agents.base import BaseAgent
from app.schemas.orchestrator import (
    OrchestratorRequest,
    OrchestratorResponse,
    OrchestratorStepResult,
    OrchestratorTask,
)


AgentRunner = Callable[[str, dict[str, Any]], Awaitable[BaseModel]]


class OrchestratorAgent(BaseAgent):
    name = "orchestrator"
    description = "Coordinate specialist agents to complete multi-step education workflows."
    request_model = OrchestratorRequest

    _specialist_agents = {
        "chat",
        "slides",
        "image",
        "quiz",
        "competency_report",
        "autocomplete",
    }

    _agent_descriptions = {
        "chat": "Answer or summarize lesson content for the learner.",
        "slides": "Generate lesson presentation slides.",
        "image": "Generate a visual asset for the requested learning material.",
        "quiz": "Generate assessment questions for the lesson.",
        "competency_report": "Generate a competency report from learning evidence.",
        "autocomplete": "Suggest continuation text for editor content.",
    }

    def __init__(self, run_agent: AgentRunner):
        self._run_agent = run_agent

    async def run(self, payload: OrchestratorRequest) -> OrchestratorResponse:
        plan = self._build_plan(payload)

        if not plan:
            return OrchestratorResponse(
                success=False,
                goal=payload.goal,
                plan=[],
                steps=[],
                final_answer="",
                message="No executable specialist agent could be inferred for this goal.",
            )

        if payload.parallel and payload.continue_on_error:
            steps = await asyncio.gather(*[self._execute_task(task) for task in plan])
        else:
            steps = []
            for task in plan:
                step = await self._execute_task(task)
                steps.append(step)
                if step.status != "success" and not payload.continue_on_error:
                    break

        success = any(step.status == "success" for step in steps)

        return OrchestratorResponse(
            success=success,
            goal=payload.goal,
            plan=plan,
            steps=steps,
            final_answer=self._compose_final_answer(payload.goal, steps),
            message=self._compose_message(steps),
        )

    def _build_plan(self, request: OrchestratorRequest) -> list[OrchestratorTask]:
        tasks = request.tasks or self._infer_tasks(request)
        enabled_agents = set(request.enabled_agents or self._specialist_agents)
        plan: list[OrchestratorTask] = []

        for index, task in enumerate(tasks, start=1):
            if task.agent not in self._specialist_agents:
                continue
            if task.agent not in enabled_agents:
                continue

            payload = self._apply_payload_defaults(task.agent, task.payload, request)
            plan.append(OrchestratorTask(
                task_id=task.task_id or f"{task.agent}_{index}",
                agent=task.agent,
                description=task.description or self._agent_descriptions.get(task.agent, ""),
                payload=payload,
            ))

        return plan

    def _infer_tasks(self, request: OrchestratorRequest) -> list[OrchestratorTask]:
        goal = self._normalize_text(request.goal)
        tasks: list[OrchestratorTask] = []

        wants_full_material = self._has_any(goal, [
            "lesson package",
            "learning package",
            "full lesson",
            "complete lesson",
            "bo hoc lieu",
            "hoc lieu",
            "tao bai hoc",
            "tron bo",
        ])

        if wants_full_material or self._has_any(goal, [
            "slide",
            "slides",
            "presentation",
            "powerpoint",
            "bai giang",
            "trinh chieu",
        ]):
            tasks.append(self._task("slides"))

        if wants_full_material or self._has_any(goal, [
            "quiz",
            "question",
            "questions",
            "assessment",
            "cau hoi",
            "trac nghiem",
            "kiem tra",
            "bai tap",
        ]):
            tasks.append(self._task("quiz"))

        if self._has_any(goal, [
            "image",
            "illustration",
            "visual",
            "anh",
            "hinh",
            "minh hoa",
        ]):
            tasks.append(self._task("image"))

        if self._has_any(goal, [
            "report",
            "competency",
            "progress",
            "bao cao",
            "nang luc",
            "danh gia",
        ]):
            tasks.append(self._task("competency_report"))

        if self._has_any(goal, [
            "autocomplete",
            "suggest continuation",
            "goi y viet",
            "hoan thanh cau",
        ]):
            tasks.append(self._task("autocomplete"))

        if not tasks and request.lesson_id is not None and request.student_id is not None:
            tasks.append(self._task("chat"))

        return tasks

    def _apply_payload_defaults(
        self,
        agent: str,
        payload: dict[str, Any],
        request: OrchestratorRequest,
    ) -> dict[str, Any]:
        agent_payload = dict(payload)

        if agent == "chat":
            agent_payload.setdefault("lesson_id", request.lesson_id)
            agent_payload.setdefault("student_id", request.student_id)
            agent_payload.setdefault("message", request.goal)
            agent_payload.setdefault(
                "conversation_history",
                [item.model_dump() for item in request.conversation_history],
            )
        elif agent == "slides":
            agent_payload.setdefault("lesson_id", request.lesson_id)
            agent_payload.setdefault("num_slides", request.num_slides)
            agent_payload.setdefault("language", request.language)
            agent_payload.setdefault("additional_instructions", request.goal)
        elif agent == "quiz":
            agent_payload.setdefault("lesson_id", request.lesson_id)
            agent_payload.setdefault("num_questions", request.num_questions)
            agent_payload.setdefault("language", request.language)
            agent_payload.setdefault("difficulty", request.difficulty)
            agent_payload.setdefault("additional_instructions", request.goal)
        elif agent == "image":
            agent_payload.setdefault("prompt", request.image_prompt or request.goal)
            agent_payload.setdefault("size", request.context.get("image_size", ""))
            agent_payload.setdefault("quality", request.context.get("image_quality", ""))
        elif agent == "autocomplete":
            agent_payload.setdefault("text", request.context.get("text", request.goal))
            agent_payload.setdefault("lesson_id", request.lesson_id)
        elif agent == "competency_report":
            report_payload = self._extract_report_payload(request.context)
            for key, value in report_payload.items():
                agent_payload.setdefault(key, value)

        return {key: value for key, value in agent_payload.items() if value is not None}

    async def _execute_task(self, task: OrchestratorTask) -> OrchestratorStepResult:
        try:
            result = await self._run_agent(task.agent, task.payload)
            result_data = result.model_dump(by_alias=True)
            success = bool(result_data.get("success", True))

            return OrchestratorStepResult(
                task_id=task.task_id,
                agent=task.agent,
                description=task.description,
                status="success" if success else "failed",
                payload=task.payload,
                result=result_data,
                error="" if success else str(result_data.get("message", "Agent returned success=false")),
            )
        except Exception as exc:
            return OrchestratorStepResult(
                task_id=task.task_id,
                agent=task.agent,
                description=task.description,
                status="failed",
                payload=task.payload,
                result={},
                error=str(exc),
            )

    def _compose_final_answer(self, goal: str, steps: list[OrchestratorStepResult]) -> str:
        successful_steps = [step for step in steps if step.status == "success"]
        if not successful_steps:
            return ""

        parts = [f"Goal: {goal}", "Completed specialist work:"]
        for step in successful_steps:
            parts.append(f"- {step.agent}: {self._summarize_agent_result(step)}")

        return "\n".join(parts)

    def _compose_message(self, steps: list[OrchestratorStepResult]) -> str:
        success_count = sum(1 for step in steps if step.status == "success")
        failure_count = sum(1 for step in steps if step.status == "failed")

        if success_count and not failure_count:
            return f"Orchestration completed successfully with {success_count} specialist agent(s)."
        if success_count and failure_count:
            return f"Orchestration completed with {success_count} successful and {failure_count} failed step(s)."
        return "Orchestration failed. No specialist agent completed successfully."

    def _summarize_agent_result(self, step: OrchestratorStepResult) -> str:
        result = step.result

        if step.agent == "chat":
            return result.get("answer") or result.get("message", "Answer generated.")
        if step.agent == "slides":
            total = result.get("total_slides", len(result.get("slides", [])))
            return f"generated {total} slide(s)."
        if step.agent == "quiz":
            total = result.get("total_questions", len(result.get("questions", [])))
            return f"generated {total} question(s)."
        if step.agent == "image":
            return "generated an image." if result.get("image_url") else result.get("message", "Image step finished.")
        if step.agent == "competency_report":
            return result.get("overall_summary") or result.get("message", "Report generated.")
        if step.agent == "autocomplete":
            return result.get("suggestion") or "Suggestion generated."

        return result.get("message", "Step finished.")

    def _extract_report_payload(self, context: dict[str, Any]) -> dict[str, Any]:
        report_payload = context.get("competency_report") or context.get("report")
        if isinstance(report_payload, dict):
            return report_payload

        if "student" in context and ("class" in context or "class_info" in context):
            return context

        return {}

    def _task(self, agent: str) -> OrchestratorTask:
        return OrchestratorTask(
            agent=agent,
            description=self._agent_descriptions.get(agent, ""),
        )

    def _has_any(self, value: str, keywords: list[str]) -> bool:
        return any(re.search(rf"\b{re.escape(keyword)}\b", value) for keyword in keywords)

    def _normalize_text(self, value: str) -> str:
        normalized = unicodedata.normalize("NFD", value.replace("\u0111", "d").replace("\u0110", "D"))
        without_accents = normalized.encode("ascii", "ignore").decode("ascii")
        return without_accents.lower()
