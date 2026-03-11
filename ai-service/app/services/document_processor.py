import fitz  # PyMuPDF
from docx import Document as DocxDocument
from pathlib import Path


def extract_text_from_file(file_path: str, content_type: str) -> str:
    """Extract text content from uploaded files."""
    path = Path(file_path)

    if content_type == "pdf":
        return _extract_pdf(path)
    elif content_type == "docx":
        return _extract_docx(path)
    elif content_type == "txt":
        return _extract_txt(path)
    else:
        raise ValueError(f"Unsupported content type: {content_type}")


def extract_text_from_bytes(file_bytes: bytes, content_type: str) -> str:
    """Extract text from file bytes."""
    if content_type == "pdf":
        return _extract_pdf_bytes(file_bytes)
    elif content_type == "docx":
        return _extract_docx_bytes(file_bytes)
    elif content_type == "txt":
        return file_bytes.decode("utf-8")
    else:
        raise ValueError(f"Unsupported content type: {content_type}")


def _extract_pdf(path: Path) -> str:
    doc = fitz.open(str(path))
    text_parts = []
    for page in doc:
        text_parts.append(page.get_text())
    doc.close()
    return "\n".join(text_parts)


def _extract_pdf_bytes(file_bytes: bytes) -> str:
    doc = fitz.open(stream=file_bytes, filetype="pdf")
    text_parts = []
    for page in doc:
        text_parts.append(page.get_text())
    doc.close()
    return "\n".join(text_parts)


def _extract_docx(path: Path) -> str:
    doc = DocxDocument(str(path))
    return "\n".join([p.text for p in doc.paragraphs if p.text.strip()])


def _extract_docx_bytes(file_bytes: bytes) -> str:
    import io
    doc = DocxDocument(io.BytesIO(file_bytes))
    return "\n".join([p.text for p in doc.paragraphs if p.text.strip()])


def _extract_txt(path: Path) -> str:
    return path.read_text(encoding="utf-8")
