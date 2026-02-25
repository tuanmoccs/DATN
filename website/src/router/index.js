import { createRouter, createWebHistory } from "vue-router";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'Login',
      component: () => import('@/pages/auth/LoginPage.vue'),
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
        {
          path: 'classes',
          name: 'TeacherClasses',
          component: () => import('@/pages/teacher/ClassesPage.vue'),
        },
        {
          path: 'classes/:id',
          name: 'TeacherClassDetail',
          component: () => import('@/pages/teacher/ClassDetailPage.vue'),
          props: true,
        },
      ]
    },
    {
      path: '/',
      redirect: '/login'
    },
  ]
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('access_token')
  const userInfo = localStorage.getItem('user_info')
  const user = userInfo ? JSON.parse(userInfo) : null

  // Kiểm tra route yêu cầu authentication
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!token || !user) {
      next({ name: 'Login' })
    } else {
      const requiredRole = to.matched.find(record => record.meta.role)?.meta.role
      if (requiredRole && user.role !== requiredRole) {
        next({ name: 'Login' })
      } else {
        next()
      }
    }
  } else if (to.matched.some(record => record.meta.guest)) {
    // Nếu đã đăng nhập là teacher thì chuyển về dashboard
    if (token && user && user.role === 'teacher') {
      next({ name: 'TeacherDashboard' })
    } else {
      next()
    }
  } else {
    next()
  }
})

export default router