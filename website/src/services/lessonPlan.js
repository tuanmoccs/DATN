export default ($axios) => ({
  // CRUD
  getAll() {
    return $axios.$get('/teacher/lesson-plans')
  },
  create(data) {
    return $axios.$post('/teacher/lesson-plans', data)
  },
  getDetail(id) {
    return $axios.$get(`/teacher/lesson-plans/${id}`)
  },
  update(id, data) {
    return $axios.$put(`/teacher/lesson-plans/${id}`, data)
  },
  delete(id) {
    return $axios.$delete(`/teacher/lesson-plans/${id}`)
  },
  search(query) {
    return $axios.$get('/teacher/lesson-plans/search', { params: { q: query } })
  },

  // Upload tài liệu tham khảo
  uploadReference(id, file) {
    const formData = new FormData()
    formData.append('file', file)
    return $axios.$post(`/teacher/lesson-plans/${id}/upload-reference`, formData, {
      timeout: 180000,
    })
  },
  uploadReferenceText(id, text) {
    return $axios.$post(`/teacher/lesson-plans/${id}/upload-reference-text`, { text })
  },

  // AI autocomplete
  aiSuggest(id, text) {
    return $axios.$post(`/teacher/lesson-plans/${id}/ai-suggest`, { text }, {
      timeout: 20000,
    })
  },
})
