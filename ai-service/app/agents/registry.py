from collections.abc import Iterable
from typing import Any

from fastapi import HTTPException
from pydantic import BaseModel

from app.agents.autocomplete_agent import AutocompleteAgent
from app.agents.base import BaseAgent
from app.agents.chat_agent import ChatAgent
from app.agents.image_agent import ImageAgent
from app.agents.orchestrator_agent import OrchestratorAgent
from app.agents.quiz_agent import QuizAgent
from app.agents.report_agent import CompetencyReportAgent
from app.agents.slide_agent import SlideAgent


class AgentRegistry:
    def __init__(self, agents: Iterable[BaseAgent]):
        self._agents = {agent.name: agent for agent in agents}

    def list_agents(self) -> list[BaseAgent]:
        return list(self._agents.values())

    def register(self, agent: BaseAgent) -> None:
        self._agents[agent.name] = agent

    def get(self, name: str) -> BaseAgent:
        agent = self._agents.get(name)
        if agent is None:
            raise HTTPException(status_code=404, detail=f"Unknown agent '{name}'")
        return agent


agent_registry = AgentRegistry([
    ChatAgent(),
    SlideAgent(),
    ImageAgent(),
    QuizAgent(),
    CompetencyReportAgent(),
    AutocompleteAgent(),
])


async def execute_agent(name: str, payload: dict[str, Any]) -> BaseModel:
    agent = agent_registry.get(name)
    validated_payload = agent.validate_payload(payload)
    return await agent.run(validated_payload)


agent_registry.register(OrchestratorAgent(execute_agent))
