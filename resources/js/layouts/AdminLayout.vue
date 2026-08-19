<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminStore } from '../stores/adminStore'

const router = useRouter()
const adminStore = useAdminStore()

onMounted(() => {
  if (!adminStore.isAuthenticated) {
    router.push('/login-backend')
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <slot />
  </div>
</template>
