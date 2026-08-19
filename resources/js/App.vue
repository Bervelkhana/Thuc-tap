<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import ChatBoxAI from './components/ChatBoxAI.vue'
import MainLayout from './layouts/MainLayout.vue'
import AdminLayout from './layouts/AdminLayout.vue'

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
]

const showSidebar = computed(() => {
  if (isAdminRoute.value) return false
  return !hideSidebarRoutes.includes(route.path)
})
</script>

<template>
  <AdminLayout v-if="isAdminRoute">
    <router-view />
  </AdminLayout>
  <MainLayout v-else-if="route.path !== '/login-backend'" :show-sidebar="!hideSidebar">
    <router-view />
  </MainLayout>
  <router-view v-else />

  <ChatBoxAI v-if="showChatBox" />
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap');
</style>


