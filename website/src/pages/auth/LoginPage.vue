<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <LoginComponent />
  </div>
</template>
<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import LoginComponent from '@/components/auth/LoginComponent.vue';

const router = useRouter()

onMounted(() => {
  const token = localStorage.getItem('access_token')
  const userInfo = localStorage.getItem('user_info')

  if (token && userInfo) {
    const user = JSON.parse(userInfo)
    if (user.role === 'teacher') {
      router.push('/teacher/dashboard')
    }
  }
})
</script>