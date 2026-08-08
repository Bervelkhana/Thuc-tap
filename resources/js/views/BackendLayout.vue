<script setup>
import { ref, defineAsyncComponent } from 'vue'
import PrebuiltConfigManagement from '../components/backend/PrebuiltConfigManagement.vue'

const ProductManagement = defineAsyncComponent(() => import('../components/backend/ProductManagement.vue'))
const OrderManagement = defineAsyncComponent(() => import('../components/backend/OrderManagement.vue'))

const activeTab = ref('products')
</script>

<template>
  <div class="min-h-screen bg-gray-100">
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
  </div>
</template>
