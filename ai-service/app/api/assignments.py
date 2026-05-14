import logging

from fastapi import APIRouter, File, Form, HTTPException, UploadFile

from app.schemas.assignment import AssignmentGradeRequest, AssignmentGradeResponse
from app.services.assignment_service import extract_reference_text, extract_submission_text, grade_assignment

logger = logging.getLogger(__name__)
router = APIRouter()


@router.post("/grade", response_model=AssignmentGradeResponse)
async def grade_assignment_endpoint(
    assignment_title: str = Form(...),
    assignment_description: str = Form(default=""),
    assignment_instructions: str = Form(default=""),
    max_score: float = Form(default=100),
    reference_files: list[UploadFile] = File(default=[]),
    files: list[UploadFile] = File(default=[]),
):
    """Extract teacher reference files and student files, then grade the submission with the LLM."""
    try:
        assignment_reference_text = await extract_reference_text(reference_files)
        student_answer = await extract_submission_text(files)
        result = await grade_assignment(AssignmentGradeRequest(
            assignment_title=assignment_title,
            assignment_description=assignment_description,
            assignment_instructions=assignment_instructions,
            assignment_reference_text=assignment_reference_text,
            max_score=max_score,
            student_answer=student_answer,
        ))

        if not result.success:
            raise HTTPException(status_code=400, detail=result.message)

        return result
    except HTTPException:
        raise
    except Exception as exc:
        logger.error("Assignment grading failed: %s", exc)
        raise HTTPException(status_code=500, detail=f"Assignment grading failed: {str(exc)}")
