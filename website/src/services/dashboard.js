export default ($axios) => ({
  // Get teacher dashboard statistics and top students
  getTeacherDashboard() {
    return $axios.$get('/teacher/dashboard')
  },
})
