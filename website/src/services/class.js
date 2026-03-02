export default ($axios) => ({
  // Lấy danh sách lớp học của giáo viên
  getClasses() {
    return $axios.$get('/teacher/classes')
  },

  // Tạo lớp học mới
  createClass(data) {
    return $axios.$post('/teacher/classes', data)
  },

  // Lấy chi tiết lớp học
  getClassDetail(id) {
    return $axios.$get(`/teacher/classes/${id}`)
  },

  // Cập nhật lớp học
  updateClass(id, data) {
    return $axios.$put(`/teacher/classes/${id}`, data)
  },

  // Xóa lớp học
  deleteClass(id) {
    return $axios.$delete(`/teacher/classes/${id}`)
  },

  // Duyệt yêu cầu tham gia
  approveEnrollment(enrollmentId) {
    return $axios.$post(`/teacher/classes/enrollments/${enrollmentId}/approve`)
  },

  // Từ chối yêu cầu tham gia
  rejectEnrollment(enrollmentId) {
    return $axios.$post(`/teacher/classes/enrollments/${enrollmentId}/reject`)
  },

  // Xóa học sinh khỏi lớp
  removeStudent(enrollmentId) {
    return $axios.$delete(`/teacher/classes/enrollments/${enrollmentId}`)
  },
})
