
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

  // Quên mật khẩu
  sendForgotPasswordOtp(data) {
    return $axios.$post('/auth/forgot-password/send-otp', data)
  },
  verifyForgotPasswordOtp(data) {
    return $axios.$post('/auth/forgot-password/verify-otp', data)
  },
  resetPassword(data) {
    return $axios.$post('/auth/forgot-password/reset', data)
  },

  // Profile
  getProfile() {
    return $axios.$get('/profile')
  },
  updateProfile(data) {
    return $axios.$post('/profile/update', data)
  },
  uploadAvatar(formData) {
    return $axios.$post('/profile/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  changePassword(data) {
    return $axios.$post('/profile/change-password', data)
  },
})
