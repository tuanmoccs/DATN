import { createRouter, createWebHistory } from "vue-router";
import Login from '@/pages/auth/LoginPage.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'Login',
      component: Login,
      meta: { guest: true }
    },
    {
      path: '/register',
      name: 'Register',
      component: () => import('@/pages/auth/RegisterPage.vue'),
      meta: { guest: true }
    },
    {
      path: '/teacher',
      component: () => import('@/layouts/TeacherLayout.vue'),
      meta: { requiresAuth: true, role: 'teacher' },
      children: [
        {
          path: 'dashboard',
          name: 'TeacherDashboard',
          component: () => import('@/pages/teacher/DashboardPage.vue'),
        },
        // Thêm các route khác cho teacher
      ]
    },
    {
      path: '/',
      redirect: '/login'
    },
  ]
  
})
console.log('Login component:', Login)

router.beforeEach((to, from, next) => {

  console.log('➡️ to:', to.fullPath)
  console.log('➡️ matched:', to.matched)
  next()
  const token = localStorage.getItem('access_token')
  const userInfo = localStorage.getItem('user_info')
  const user = userInfo ? JSON.parse(userInfo) : null

  // Kiểm tra route yêu cầu authentication
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!token || !user) {
      // Chưa đăng nhập, redirect về login
      next({ name: 'Login' })
    } else {
      // Kiểm tra role
      const requiredRole = to.matched.find(record => record.meta.role)?.meta.role
      if (requiredRole && user.role !== requiredRole) {
        // Sai role, redirect về dashboard tương ứng
        if (user.role === 'teacher') {
          next({ name: 'TeacherDashboard' })
        }
      } else {
        next()
      }
    }
  } else if (to.matched.some(record => record.meta.guest)) {
    // Route cho guest (login, register)
    if (token && user) {
      // Đã đăng nhập, redirect về dashboard
      if (user.role === 'teacher') {
        next({ name: 'TeacherDashboard' })
      }
    } else {
      next()
    }
  } else {
    next()
  }
})

export default router