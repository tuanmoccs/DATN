import logging

from fastapi import APIRouter, File, Form, HTTPException, UploadFile

from app.schemas.assignment import AssignmentGradeRequest, AssignmentGradeResponse
from app.services.assignment_service import extract_submission_text, grade_assignment

logger = logging.getLogger(__name__)
router = APIRouter()


@router.post("/grade", response_model=AssignmentGradeResponse)
async def grade_assignment_endpoint(
    assignment_title: str = Form(...),
    assignment_description: str = Form(default=""),
    assignment_instructions: str = Form(default=""),
    max_score: float = Form(default=100),
    files: list[UploadFile] = File(default=[]),
):
    """Extract submitted files and grade an assignment submission with the LLM."""
    try:
        student_answer = await extract_submission_text(files)
        result = await grade_assignment(AssignmentGradeRequest(
            assignment_title=assignment_title,
            assignment_description=assignment_description,
            assignment_instructions=assignment_instructions,
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
