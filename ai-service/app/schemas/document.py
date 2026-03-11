from pydantic import BaseModel, Field


class DocumentProcessRequest(BaseModel):
    lesson_id: int
    file_name: str
    content_type: str = Field(description="pdf, docx, or txt")


class DocumentTextRequest(BaseModel):
    lesson_id: int
    text: str = Field(description="Pre-extracted text content from the document")


class DocumentProcessResponse(BaseModel):
    success: bool
    lesson_id: int
    chunks_count: int
    message: str


class DocumentDeleteRequest(BaseModel):
    lesson_id: int
