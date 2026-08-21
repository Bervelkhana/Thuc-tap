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

function handleLogout() {
  adminStore.logout()
  router.push('/login-backend')
}
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Shared Admin Header -->
    <nav class="bg-white shadow-md sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">🏪 TechGear Admin</h1>
          <p class="text-sm text-gray-600">Xin chào, {{ adminStore.admin?.name }}</p>
        </div>
        <div class="flex gap-3">
          <button
            @click="handleLogout"
            class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition"
          >
            Đăng xuất
          </button>
        </div>
      </div>
    </nav>

    <!-- Child route content renders HERE via router-view -->
    <router-view />
  </div>
</template>
