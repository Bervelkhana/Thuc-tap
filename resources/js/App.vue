<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import ChatBoxAI from './components/ChatBoxAI.vue'
import MainLayout from './layouts/MainLayout.vue'

const route = useRoute()

const showChatBox = computed(() => route.path !== '/login-backend')

const hideSidebarRoutes = [
  '/',
  '/home',
  '/checkout-new',
  '/checkout',
  '/login-backend',
  '/admin',
  '/admin/dashboard',
]

const showSidebar = computed(() => {
  if (route.path.startsWith('/admin')) return false
  return !hideSidebarRoutes.includes(route.path)
})
</script>

<template>
  <MainLayout :show-sidebar="showSidebar">
    <router-view />
  </MainLayout>

  <ChatBoxAI v-if="showChatBox" />
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap');
</style>


