from abc import ABC, abstractmethod
from typing import Any

from pydantic import BaseModel


class BaseAgent(ABC):
    name: str
    description: str
    request_model: type[BaseModel]

    @abstractmethod
    async def run(self, payload: BaseModel) -> BaseModel:
        raise NotImplementedError

    def validate_payload(self, payload: dict[str, Any]) -> BaseModel:
        return self.request_model.model_validate(payload)
