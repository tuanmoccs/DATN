export default ($axios) => ({
  // ========== Teacher Assignment APIs ==========

  // Lấy danh sách bài tập theo lớp
  getAssignmentsByClass(classId) {
    return $axios.$get(`/teacher/assignments/class/${classId}`)
  },

  // Tạo bài tập mới
  createAssignment(formData) {
    return $axios.$post('/teacher/assignments', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 60000,
    })
  },

  // Lấy chi tiết bài tập
  getAssignmentDetail(id) {
    return $axios.$get(`/teacher/assignments/${id}`)
  },

  // Cập nhật bài tập
  updateAssignment(id, formData) {
    return $axios.$post(`/teacher/assignments/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 60000,
    })
  },

  // Xoá bài tập
  deleteAssignment(id) {
    return $axios.$delete(`/teacher/assignments/${id}`)
  },

  // ========== Submission & Grading APIs ==========

  // Lấy danh sách bài nộp
  getSubmissions(assignmentId) {
    return $axios.$get(`/teacher/assignments/${assignmentId}/submissions`)
  },

  // Lấy chi tiết bài nộp
  getSubmissionDetail(submissionId) {
    return $axios.$get(`/teacher/assignments/submissions/${submissionId}`)
  },

  // Yêu cầu AI chấm điểm
  requestAIGrading(submissionId) {
    return $axios.$post(`/teacher/assignments/submissions/${submissionId}/ai-grade`, {}, {
      timeout: 120000, // 2 min cho AI processing
    })
  },

  // Giáo viên chốt điểm
  finalizeGrading(submissionId, data) {
    return $axios.$post(`/teacher/assignments/submissions/${submissionId}/grade`, data)
  },

  // ========== Student Assignment APIs ==========

  // Học sinh xem danh sách bài tập
  getStudentAssignments(classId) {
    return $axios.$get(`/student/assignments/class/${classId}`)
  },

  // Học sinh nộp bài
  submitAssignment(assignmentId, formData) {
    return $axios.$post(`/student/assignments/${assignmentId}/submit`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 120000,
    })
  },

  searchAssignments(classId, query) {
    return $axios.$get(`/teacher/assignments/class/${classId}/search`, {
      params: { q: query },
    })
  },
})
