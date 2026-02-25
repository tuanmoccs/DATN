<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Sidebar -->
    <TeacherSidebar :collapsed="sidebarCollapsed" @toggle="sidebarCollapsed = !sidebarCollapsed" />

    <!-- Main content area -->
    <div :class="[
      'transition-all duration-300',
      sidebarCollapsed ? 'ml-20' : 'ml-64',
    ]">
      <!-- Header -->
      <TeacherHeader />

      <!-- Page content -->
      <main class="p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import TeacherSidebar from '@/components/partial/TeacherSidebar.vue'
import TeacherHeader from '@/components/partial/TeacherHeader.vue'

const sidebarCollapsed = ref(false)
</script>
