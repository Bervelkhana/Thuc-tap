<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminStore } from '../stores/adminStore'

const router = useRouter()
const adminStore = useAdminStore()

const stats = ref({
  totalOrders: 0,
  totalProducts: 0,
  totalRevenue: 0,
  pendingOrders: 0,
})

const loading = ref(true)

async function fetchDashboardData() {
  try {
    loading.value = true
    // Nếu có API endpoint cho dashboard data
    // const response = await fetch('/api/admin/dashboard', {
    //   headers: {
    //     'Authorization': `Bearer ${adminStore.token}`
    //   }
    // })
    // const data = await response.json()
    // stats.value = data

    // Mock data cho demo
    setTimeout(() => {
      stats.value = {
        totalOrders: 24,
        totalProducts: 156,
        totalRevenue: 25500000,
        pendingOrders: 5,
      }
      loading.value = false
    }, 500)
  } catch (error) {
    console.error('Failed to fetch dashboard data:', error)
    loading.value = false
  }
}

function handleLogout() {
  adminStore.logout()
  router.push('/login-backend')
}

function goToBackend() {
  router.push('/backend')
}

onMounted(() => {
  if (!adminStore.isAuthenticated) {
    router.push('/login-backend')
    return
  }
  fetchDashboardData()
})
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <!-- NAVBAR -->
    <nav class="bg-white shadow-md sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">🏪 TechGear Admin</h1>
          <p class="text-sm text-gray-500">Xin chào, {{ adminStore.admin?.name }}</p>
        </div>
        <div class="flex gap-3">
          <button
            @click="goToBackend"
            class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition"
          >
            Quản lý
          </button>
          <button
            @click="handleLogout"
            class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition"
          >
            Đăng xuất
          </button>
        </div>
      </div>
    </nav>

    <!-- CONTENT -->
    <div class="max-w-7xl mx-auto px-6 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin">
          <svg class="w-8 h-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>
      </div>

      <!-- Dashboard Stats -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Orders -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-600">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm">Tổng đơn hàng</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.totalOrders }}</p>
            </div>
            <div class="text-4xl text-blue-600 opacity-20">📋</div>
          </div>
        </div>

        <!-- Total Products -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-600">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm">Tổng sản phẩm</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.totalProducts }}</p>
            </div>
            <div class="text-4xl text-green-600 opacity-20">📦</div>
          </div>
        </div>

        <!-- Total Revenue -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-600">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm">Doanh thu</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ (stats.totalRevenue / 1000000).toFixed(1) }}M</p>
            </div>
            <div class="text-4xl text-purple-600 opacity-20">💰</div>
          </div>
        </div>

        <!-- Pending Orders -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-600">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm">Đơn chờ xử lý</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.pendingOrders }}</p>
            </div>
            <div class="text-4xl text-orange-600 opacity-20">⏳</div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Thao tác nhanh</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <router-link
            to="/backend/products"
            class="p-4 border-2 border-gray-200 rounded-lg hover:border-blue-600 hover:bg-blue-50 transition text-center"
          >
            <div class="text-3xl mb-2">📦</div>
            <p class="font-semibold text-gray-900">Quản lý sản phẩm</p>
            <p class="text-sm text-gray-500">Thêm, sửa, xóa sản phẩm</p>
          </router-link>
          <router-link
            to="/backend/orders"
            class="p-4 border-2 border-gray-200 rounded-lg hover:border-green-600 hover:bg-green-50 transition text-center"
          >
            <div class="text-3xl mb-2">📋</div>
            <p class="font-semibold text-gray-900">Quản lý đơn hàng</p>
            <p class="text-sm text-gray-500">Xem và cập nhật đơn hàng</p>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
