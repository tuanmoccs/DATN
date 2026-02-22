
export default ($axios) => ({
   login(credentials) {
    return $axios.$post('/auth/login', credentials)
  },
  registerTeacherSendOtp(userData) {
    return $axios.$post('/auth/register/teacher/send-otp', userData)
  },

  // Đăng ký teacher - Bước 2: Verify OTP
  registerTeacherVerifyOtp(data) {
    return $axios.$post('/auth/register/teacher/verify-otp', data)
  },
  logout() {
    return $axios.$post('/auth/logout')
  },
  getCurrentUser() {
    return $axios.$get('/auth/me')
  },
  refreshToken() {
    return $axios.$post('/auth/refresh')
  },
  forgotPassword(email) {
    return $axios.$post('/auth/forgot-password', { email })
  },
  resetPassword(data) {
    return $axios.$post('/auth/reset-password', data)
  },
  changePassword(data) {
    return $axios.$put('/auth/change-password', data)
  },
  updateProfile(data) {
    return $axios.$put('/auth/profile', data)
  },
})