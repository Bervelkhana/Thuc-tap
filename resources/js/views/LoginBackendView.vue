<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminStore } from '../stores/adminStore'

const router = useRouter()
const adminStore = useAdminStore()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function handleLogin() {
  if (!email.value || !password.value) {
    error.value = 'Vui lòng nhập email và mật khẩu'
    return
  }

  loading.value = true
  error.value = ''

  try {
    const response = await fetch('/api/admin/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: email.value,
        password: password.value,
      }),
    })

    const result = await response.json()

    if (result.status === 'success') {
      adminStore.setAuth(result.data)
      router.push('/admin/dashboard')
    } else {
      error.value = result.message || 'Đăng nhập thất bại'
    }
  } catch (err) {
    error.value = 'Lỗi kết nối: ' + err.message
  } finally {
    loading.value = false
  }
}

function handleKeydown(e) {
  if (e.key === 'Enter') handleLogin()
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">TechGear</h1>
        <p class="text-gray-400">Quản lý kho hàng</p>
      </div>

      <!-- Login Form -->
      <div class="bg-gray-800 rounded-lg p-8 shadow-xl border border-gray-700">
        <!-- Error Message -->
        <div v-if="error" class="mb-6 p-3 bg-red-900/20 border border-red-500 rounded text-red-400 text-sm">
          {{ error }}
        </div>

        <!-- Email Input -->
        <div class="mb-4">
          <label class="block text-gray-300 text-sm font-medium mb-2">Email</label>
          <input
            v-model="email"
            type="email"
            placeholder="admin@example.com"
            @keydown="handleKeydown"
            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition"
          />
        </div>

        <!-- Password Input -->
        <div class="mb-6">
          <label class="block text-gray-300 text-sm font-medium mb-2">Mật khẩu</label>
          <input
            v-model="password"
            type="password"
            placeholder="••••••••"
            @keydown="handleKeydown"
            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition"
          />
        </div>

        <!-- Login Button -->
        <button
          @click="handleLogin"
          :disabled="loading"
          class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-800 text-white font-semibold py-2 rounded transition"
        >
          <span v-if="!loading">Đăng nhập</span>
          <span v-else>Đang xử lý...</span>
        </button>

        <!-- Demo Info -->
        <div class="mt-6 p-3 bg-blue-900/20 border border-blue-500/30 rounded text-blue-300 text-xs">
          <p class="font-semibold mb-1">Demo Account:</p>
          <p>Email: admin@example.com</p>
          <p>Password: admin123</p>
        </div>
      </div>

      <!-- Back Link -->
      <div class="text-center mt-6">
        <router-link to="/" class="text-gray-400 hover:text-gray-300 text-sm transition">
          ← Quay lại trang chủ
        </router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
input:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>
