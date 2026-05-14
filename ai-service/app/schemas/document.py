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


class DocumentChunkPreview(BaseModel):
    chunk_number: int
    characters: int
    metadata: dict[str, str]
    preview: str


class DocumentAnalyzeResponse(BaseModel):
    success: bool
    lesson_id: int
    file_name: str
    content_type: str
    extracted_characters: int
    extracted_text_preview: str
    chunks_count: int
    chunks: list[DocumentChunkPreview]
    prompt_preview: str
    analysis: str
    message: str


class ChromaDocumentItem(BaseModel):
    id: str
    metadata: dict
    document: str
    document_preview: str


class ChromaInspectResponse(BaseModel):
    success: bool
    collection: str
    lesson_id: int | None = None
    total_returned: int
    items: list[ChromaDocumentItem]
