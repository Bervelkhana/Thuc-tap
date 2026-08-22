<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import ChatBoxAI from './components/ChatBoxAI.vue'
import MainLayout from './layouts/MainLayout.vue'

const route = useRoute()

const isAdminRoute = computed(() => {
  return route.path.startsWith('/admin') || route.path.startsWith('/backend')
})

const showChatBox = computed(() => {
  if (route.path === '/login-backend') return false
  if (isAdminRoute.value) return false
  return true
})

const hideSidebarRoutes = [
  '/',
  '/home',
  '/checkout-new',
  '/checkout',
  '/order-success'
]

const showSidebar = computed(() => {
  if (isAdminRoute.value) return false
  const path = route.path
  return !hideSidebarRoutes.some((hiddenPath) => path === hiddenPath || path.startsWith(hiddenPath + '/'))
})
</script>

<template>
  <!-- Admin routes: only Vue Router's nested AdminLayout renders the admin pages -->
  <router-view v-if="isAdminRoute" />

  <!-- Public/frontend routes: use MainLayout -->
  <MainLayout v-else-if="route.path !== '/login-backend'" :show-sidebar="showSidebar">
    <router-view />
  </MainLayout>

  <!-- Login page: no layout wrapper -->
  <router-view v-else />

  <ChatBoxAI v-if="showChatBox" />
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap');
</style>


