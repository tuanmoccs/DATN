import logging

from fastapi import APIRouter, HTTPException

from app.agents import agent_registry, execute_agent
from app.schemas.agent import AgentExecuteRequest, AgentExecuteResponse, AgentInfo

logger = logging.getLogger(__name__)
router = APIRouter()


@router.get("", response_model=list[AgentInfo])
async def list_agents():
    return [
        AgentInfo(
            name=agent.name,
            description=agent.description,
            request_schema=agent.request_model.model_json_schema(),
        )
        for agent in agent_registry.list_agents()
    ]


@router.post("/execute", response_model=AgentExecuteResponse)
async def execute_agent_endpoint(request: AgentExecuteRequest):
    try:
        result = await execute_agent(request.agent, request.payload)
        result_data = result.model_dump(by_alias=True)
        success = bool(result_data.get("success", True))
        return AgentExecuteResponse(
            agent=request.agent,
            success=success,
            result=result_data,
        )
    except HTTPException:
        raise
    except Exception as e:
        logger.error("Agent execution failed for %s: %s", request.agent, e)
        raise HTTPException(status_code=500, detail=f"Agent execution failed: {str(e)}")
