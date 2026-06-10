import unittest

from app.services.assignment_service import (
    _format_submission_source,
    _image_mime_type,
    _normalize_criteria,
    _parse_submission_sources,
    _validate_annotations,
)


class AssignmentAnnotationTests(unittest.TestCase):
    def setUp(self):
        self.student_answer = _format_submission_source(
            "IMG001",
            "bai-lam.jpg",
            "image",
            "Newton's second law\nF = m/a\nAcceleration increases with force",
        )

    def test_formats_image_ocr_with_stable_line_numbers(self):
        self.assertIn("[L001] Newton's second law", self.student_answer)
        self.assertIn('[L002] F = m/a', self.student_answer)

        sources = _parse_submission_sources(self.student_answer)

        self.assertEqual(sources["IMG001"]["file_name"], "bai-lam.jpg")
        self.assertEqual(sources["IMG001"]["lines"][1], (2, "F = m/a"))

    def test_annotation_line_numbers_are_derived_from_quote(self):
        annotations = _validate_annotations(
            [{
                "id": "ann-1",
                "source_id": "IMG001",
                "file_name": "ignored-by-validator.jpg",
                "line_start": 99,
                "line_end": 99,
                "quote": "F = m/a",
                "verdict": "incorrect",
                "explanation": "The formula is incorrect.",
                "correction": "F = ma",
                "score_impact": -1,
                "confidence": 0.9,
            }],
            self.student_answer,
        )

        self.assertEqual(len(annotations), 1)
        self.assertEqual(annotations[0].file_name, "bai-lam.jpg")
        self.assertEqual(annotations[0].line_start, 2)
        self.assertEqual(annotations[0].line_end, 2)

    def test_discards_annotation_with_invented_quote(self):
        annotations = _validate_annotations(
            [{
                "id": "ann-1",
                "source_id": "IMG001",
                "quote": "This sentence does not exist in the submission",
                "verdict": "incorrect",
                "explanation": "There is no supporting evidence.",
            }],
            self.student_answer,
        )

        self.assertEqual(annotations, [])

    def test_low_confidence_annotation_cannot_change_score(self):
        annotations = _validate_annotations(
            [{
                "id": "ann-1",
                "source_id": "IMG001",
                "quote": "F = m/a",
                "verdict": "incorrect",
                "explanation": "The handwriting is uncertain.",
                "score_impact": -2,
                "confidence": 0.4,
            }],
            self.student_answer,
        )

        self.assertEqual(annotations[0].verdict, "unclear")
        self.assertEqual(annotations[0].score_impact, 0)

    def test_infers_image_mime_type_when_upload_is_generic_binary(self):
        self.assertEqual(
            _image_mime_type("camera-upload.JPG", "application/octet-stream"),
            "image/jpeg",
        )

    def test_rescales_percentage_criteria_to_assignment_max_score(self):
        criteria = _normalize_criteria(
            [
                {"criterion": "Accuracy", "max_score": 60, "suggested_score": 48},
                {"criterion": "Clarity", "max_score": 40, "suggested_score": 30},
            ],
            max_score=10,
        )

        self.assertEqual(sum(item.max_score for item in criteria), 10)
        self.assertEqual(sum(item.suggested_score for item in criteria), 7.8)


if __name__ == "__main__":
    unittest.main()
