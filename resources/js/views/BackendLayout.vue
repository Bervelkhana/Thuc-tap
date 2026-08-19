<script setup>
import { ref, defineAsyncComponent, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAdminStore } from '../stores/adminStore'
import PrebuiltConfigManagement from '../components/backend/PrebuiltConfigManagement.vue'

const ProductManagement = defineAsyncComponent(() => import('../components/backend/ProductManagement.vue'))
const OrderManagement = defineAsyncComponent(() => import('../components/backend/OrderManagement.vue'))

const route = useRoute()
const adminStore = useAdminStore()
const activeTab = ref('products')
const isAuthenticated = ref(false)
const authChecking = ref(true)

const getActiveTabFromRoute = () => {
  const path = route.path
  if (path.includes('orders')) return 'orders'
  if (path.includes('prebuilt')) return 'prebuilt'
  return 'products'
}

async function checkAuth() {
  authChecking.value = true
  try {
    const headers = { 'Content-Type': 'application/json' }
    if (adminStore.token) {
      headers['Authorization'] = `Bearer ${adminStore.token}`
    }

    const response = await fetch('/api/admin/me', {
      headers,
      credentials: 'same-origin',
    })

    if (response.ok) {
      const result = await response.json()
      isAuthenticated.value = result.status === 'success'
    } else {
      isAuthenticated.value = false
    }
  } catch {
    isAuthenticated.value = false
  } finally {
    authChecking.value = false
  }
}

onMounted(() => {
  checkAuth()
  activeTab.value = getActiveTabFromRoute()
})
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Auth Check -->
    <div v-if="authChecking" class="flex items-center justify-center min-h-screen">
      <p class="text-gray-600">Đang kiểm tra quyền truy cập...</p>
    </div>

    <div v-else-if="!isAuthenticated" class="flex items-center justify-center min-h-screen">
      <div class="text-center space-y-4">
        <p class="text-gray-900 text-xl font-semibold">Bạn cần đăng nhập để truy cập trang này</p>
        <a
          href="/admin/login"
          class="inline-block px-6 py-3 bg-black text-white rounded-lg hover:bg-gray-900 transition"
        >
          Đăng nhập admin
        </a>
      </div>
    </div>

    <template v-else>
      <!-- NAVBAR -->
      <nav class="bg-white shadow-md sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <router-link to="/admin/dashboard" class="text-2xl font-bold text-gray-900 hover:text-gray-700">
          🏪 Backend
        </router-link>
        <div class="flex gap-4">
          <router-link 
            to="/admin/dashboard" 
            class="px-4 py-2 rounded-lg bg-gray-200 text-gray-900 hover:bg-gray-300 transition"
          >
            ← Quay lại
          </router-link>
        </div>
      </div>
    </nav>

    <!-- TABS -->
    <div class="max-w-7xl mx-auto px-6 py-6">
      <div class="bg-white rounded-lg shadow-md">
        <div class="flex border-b overflow-x-auto">
          <button
            @click="activeTab = 'products'"
            :class="[
              'flex-1 min-w-[180px] px-6 py-4 font-semibold transition text-center',
              activeTab === 'products'
                ? 'text-black border-b-2 border-black bg-gray-50'
                : 'text-gray-600 hover:text-gray-900'
            ]"
          >
            📦 Quản lý sản phẩm
          </button>
           <button
             @click="activeTab = 'prebuilt'"
             :class="[
               'flex-1 min-w-[180px] px-6 py-4 font-semibold transition text-center',
               activeTab === 'prebuilt'
                 ? 'text-black border-b-2 border-black bg-gray-50'
                 : 'text-gray-600 hover:text-gray-900'
             ]"
           >
             🧩 Quản lý cấu hình
           </button>
           <button
             @click="activeTab = 'orders'"
             :class="[
               'flex-1 min-w-[180px] px-6 py-4 font-semibold transition text-center',
               activeTab === 'orders'
                 ? 'text-black border-b-2 border-black bg-gray-50'
                 : 'text-gray-600 hover:text-gray-900'
             ]"
           >
             📋 Quản lý đơn hàng
           </button>
        </div>

        <div class="p-6">
          <Suspense>
            <template #default>
              <ProductManagement v-if="activeTab === 'products'" />
              <PrebuiltConfigManagement v-else-if="activeTab === 'prebuilt'" />
              <OrderManagement v-else />
            </template>
            <template #fallback>
              <div class="text-center py-8 text-gray-600">Đang tải...</div>
            </template>
          </Suspense>
        </div>
      </div>
    </div>
    </template>
  </div>
</template>

