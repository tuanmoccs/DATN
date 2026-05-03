from app.agents.base import BaseAgent
from app.schemas.autocomplete import AutocompleteRequest, AutocompleteResponse
from app.services.autocomplete_service import generate_autocomplete


class AutocompleteAgent(BaseAgent):
    name = "autocomplete"
    description = "Suggest the next text continuation for lesson-plan authoring."
    request_model = AutocompleteRequest

    async def run(self, payload: AutocompleteRequest) -> AutocompleteResponse:
        return await generate_autocomplete(payload)
