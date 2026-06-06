export default ($axios) => ({
  getReports(params = {}) {
    return $axios.$get('/teacher/competency-reports', { params })
  },

  generateReport(data) {
    return $axios.$post('/teacher/competency-reports/generate', data, { timeout: 120000 })
  },

  generateClassReports(classId) {
    return $axios.$post(`/teacher/competency-reports/class/${classId}/generate-all`, {}, {
      timeout: 60000,
    })
  },

  getGenerateBatchStatus(batchId) {
    return $axios.$get(`/teacher/competency-reports/generate-batches/${batchId}`)
  },

  getRiskAlerts(classId) {
    return $axios.$get(`/teacher/competency-reports/class/${classId}/risk-alerts`)
  },

  updateReport(id, data) {
    return $axios.$put(`/teacher/competency-reports/${id}`, data)
  },

  exportClassPDF(classId) {
    return $axios.$get(`/teacher/competency-reports/class/${classId}/export-pdf`, {
      responseType: 'blob',
      timeout: 60000,
    })
  },
})
