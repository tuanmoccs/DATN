export default ($axios) => ({
  // Get lessons by class
  getLessonsByClass(classId) {
    return $axios.$get(`/teacher/lessons/class/${classId}`)
  },

  // Create lesson (with optional file upload)
  createLesson(formData) {
    return $axios.$post('/teacher/lessons', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 120000, // 2 min for AI generation
    })
  },

  // Get lesson detail
  getLessonDetail(id) {
    return $axios.$get(`/teacher/lessons/${id}`)
  },

  // Update lesson (with optional file upload)
  updateLesson(id, formData) {
    return $axios.$post(`/teacher/lessons/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 60000,
    })
  },

  // Delete lesson
  deleteLesson(id) {
    return $axios.$delete(`/teacher/lessons/${id}`)
  },

  // Regenerate slides with AI
  regenerateSlides(id, slideCount = 10) {
    return $axios.$post(`/teacher/lessons/${id}/regenerate-slides`, {
      slide_count: slideCount,
    }, { timeout: 120000 })
  },

  // Regenerate quiz with AI
  regenerateQuiz(id, questionCount = 5) {
    return $axios.$post(`/teacher/lessons/${id}/regenerate-quiz`, {
      question_count: questionCount,
    }, { timeout: 120000 })
  },

  // ========== Quiz APIs ==========

  // Get quizzes by lesson
  getQuizzesByLesson(lessonId) {
    return $axios.$get(`/teacher/quizzes/lesson/${lessonId}`)
  },

  // Get quiz detail
  getQuizDetail(quizId) {
    return $axios.$get(`/teacher/quizzes/${quizId}`)
  },

  // Update quiz info
  updateQuiz(quizId, data) {
    return $axios.$put(`/teacher/quizzes/${quizId}`, data)
  },

  // Delete quiz
  deleteQuiz(quizId) {
    return $axios.$delete(`/teacher/quizzes/${quizId}`)
  },

  // Publish quiz
  publishQuiz(quizId, data = {}) {
    return $axios.$post(`/teacher/quizzes/${quizId}/publish`, data)
  },

  // Add question to quiz
  addQuestion(quizId, data) {
    return $axios.$post(`/teacher/quizzes/${quizId}/questions`, data)
  },

  // Update question
  updateQuestion(quizId, questionId, data) {
    return $axios.$put(`/teacher/quizzes/${quizId}/questions/${questionId}`, data)
  },

  // Delete question
  deleteQuestion(quizId, questionId) {
    return $axios.$delete(`/teacher/quizzes/${quizId}/questions/${questionId}`)
  },
})
