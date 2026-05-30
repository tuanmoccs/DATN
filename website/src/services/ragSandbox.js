export default ($axios) => ({
  process(file, settings) {
    const formData = new FormData()
    formData.append('file', file)
    Object.entries(settings).forEach(([key, value]) => {
      formData.append(`settings[${key}]`, typeof value === 'boolean' ? (value ? 1 : 0) : value)
    })
    return $axios.$post('/teacher/rag-sandbox/process', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 180000,
    })
  },
  retrieve(payload) {
    return $axios.$post('/teacher/rag-sandbox/retrieve', payload, { timeout: 60000 })
  },
  slides(payload) {
    return $axios.$post('/teacher/rag-sandbox/slides', payload, { timeout: 180000 })
  },
  quiz(payload) {
    return $axios.$post('/teacher/rag-sandbox/quiz', payload, { timeout: 180000 })
  },
  delete(sandboxId) {
    return $axios.$delete(`/teacher/rag-sandbox/${sandboxId}`)
  },
})
