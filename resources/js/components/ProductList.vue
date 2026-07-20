<script setup>
import { ref, onMounted } from 'vue'
import { useCartStore } from '../stores/cartStore'

// Dùng Pinia store thay cho state cục bộ để chia sẻ giỏ hàng toàn cục
const cart = useCartStore()

const products = ref([])
const loading = ref(false)
const error = ref('')

async function fetchProducts() {
  loading.value = true
  error.value = ''
  try {
    const res = await fetch('/api/products')
    const json = await res.json()
    products.value = json.data
  } catch (e) {
    error.value = 'Không thể tải danh sách sản phẩm.'
  } finally {
    loading.value = false
  }
}

function formatPrice(value) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
}

onMounted(fetchProducts)
</script>

<template>
  <div class="max-w-6xl mx-auto p-4">
    <header class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold text-gray-800">Danh sách linh kiện máy tính</h1>
      <router-link
        to="/checkout"
        class="text-sm bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-900 transition"
      >
        Giỏ hàng ({{ cart.cartCount }})
      </router-link>
    </header>

    <p v-if="loading" class="text-gray-500">Đang tải...</p>
    <p v-if="error" class="text-red-500">{{ error }}</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <article
        v-for="p in products"
        :key="p.id"
        class="border rounded-lg p-4 flex flex-col hover:shadow-md transition"
      >
        <h2 class="text-lg font-medium text-gray-800">{{ p.name }}</h2>
        <p class="text-sm text-gray-500 mb-2">{{ p.category?.name }}</p>
        <p class="text-xl font-semibold text-blue-600 mb-4">{{ formatPrice(p.price) }}</p>
        <button
          @click="cart.addToCart(p)"
          class="mt-auto bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition"
        >
          Thêm vào giỏ
        </button>
      </article>
    </div>
  </div>
</template>
