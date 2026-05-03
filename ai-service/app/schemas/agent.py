from typing import Any

from pydantic import BaseModel, Field


class AgentExecuteRequest(BaseModel):
    agent: str = Field(description="Registered agent name to execute")
    payload: dict[str, Any] = Field(default_factory=dict)


class AgentExecuteResponse(BaseModel):
    agent: str
    success: bool
    result: dict[str, Any]


class AgentInfo(BaseModel):
    name: str
    description: str
    request_schema: dict[str, Any]
