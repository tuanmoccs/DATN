export default ($axios) => ({
  getReports(params = {}) {
    return $axios.$get('/teacher/competency-reports', { params })
  },

  generateReport(data) {
    return $axios.$post('/teacher/competency-reports/generate', data, { timeout: 120000 })
  },

  updateReport(id, data) {
    return $axios.$put(`/teacher/competency-reports/${id}`, data)
  },
})
