import logging

from fastapi import APIRouter, File, Form, HTTPException, UploadFile

from app.schemas.document import DocumentDeleteRequest, DocumentProcessResponse, DocumentTextRequest
from app.services.document_processor import extract_text_from_bytes
from app.services.rag_service import chunk_text, delete_lesson_chunks, store_chunks

logger = logging.getLogger(__name__)
router = APIRouter()


@router.post("/process", response_model=DocumentProcessResponse)
async def process_document(
    file: UploadFile = File(...),
    lesson_id: int = Form(...),
):
    """Upload and process a document: extract text → chunk → embed → store in vector DB."""
    file_bytes = await file.read()
    filename = file.filename or ""

    if filename.endswith(".pdf"):
        content_type = "pdf"
    elif filename.endswith(".docx"):
        content_type = "docx"
    elif filename.endswith(".txt"):
        content_type = "txt"
    else:
        raise HTTPException(status_code=400, detail="Unsupported file type. Use PDF, DOCX, or TXT.")

    try:
        text = extract_text_from_bytes(file_bytes, content_type)
    except Exception as e:
        logger.error(f"Text extraction failed: {e}")
        raise HTTPException(status_code=400, detail=f"Failed to extract text: {str(e)}")

    if not text.strip():
        raise HTTPException(status_code=400, detail="No text content found in the document.")

    # Delete old chunks for this lesson before re-processing
    delete_lesson_chunks(lesson_id)

    chunks = chunk_text(text, lesson_id)
    chunks_count = store_chunks(chunks)

    return DocumentProcessResponse(
        success=True,
        lesson_id=lesson_id,
        chunks_count=chunks_count,
        message=f"Document processed successfully. {chunks_count} chunks stored.",
    )


@router.post("/process-text", response_model=DocumentProcessResponse)
async def process_document_text(request: DocumentTextRequest):
    """Process pre-extracted text: chunk → embed → store in vector DB.
    Used by Laravel backend which already handles text extraction.
    """
    if not request.text.strip():
        raise HTTPException(status_code=400, detail="No text content provided.")

    # Delete old chunks for this lesson before re-processing
    delete_lesson_chunks(request.lesson_id)

    chunks = chunk_text(request.text, request.lesson_id)
    chunks_count = store_chunks(chunks)

    return DocumentProcessResponse(
        success=True,
        lesson_id=request.lesson_id,
        chunks_count=chunks_count,
        message=f"Text processed successfully. {chunks_count} chunks stored.",
    )


@router.post("/extract")
async def extract_text(
    file: UploadFile = File(...),
):
    """Extract text from an uploaded file and return it. Supports PDF, DOCX, TXT."""
    file_bytes = await file.read()
    filename = file.filename or ""

    if filename.lower().endswith(".pdf"):
        content_type = "pdf"
    elif filename.lower().endswith(".docx"):
        content_type = "docx"
    elif filename.lower().endswith(".txt"):
        content_type = "txt"
    else:
        raise HTTPException(status_code=400, detail="Unsupported file type. Use PDF, DOCX, or TXT.")

    try:
        text = extract_text_from_bytes(file_bytes, content_type)
    except Exception as e:
        logger.error(f"Text extraction failed: {e}")
        raise HTTPException(status_code=400, detail=f"Failed to extract text: {str(e)}")

    if not text.strip():
        raise HTTPException(status_code=400, detail="No text content found in the document.")

    return {"success": True, "text": text}


@router.delete("/delete", response_model=DocumentProcessResponse)
async def delete_document(request: DocumentDeleteRequest):
    """Delete all processed chunks for a lesson."""
    delete_lesson_chunks(request.lesson_id)

    return DocumentProcessResponse(
        success=True,
        lesson_id=request.lesson_id,
        chunks_count=0,
        message="All document chunks deleted.",
    )
