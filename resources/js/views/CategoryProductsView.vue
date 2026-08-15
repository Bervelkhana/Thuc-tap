<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCartStore } from '../stores/cartStore'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()

const categoryId = computed(() => parseInt(route.params.categoryId))
const categoryName = ref('')
const products = ref([])
const loading = ref(false)
const error = ref(null)
const addedToCart = ref(null)
const currentPage = ref(1)
const perPage = ref(12)
const total = ref(0)
const lastPage = ref(1)

const showingRange = computed(() => {
  if (total.value === 0) return '0 sản phẩm'
  const start = (currentPage.value - 1) * perPage.value + 1
  const end = Math.min(currentPage.value * perPage.value, total.value)
  return `${start}-${end} của ${total.value} sản phẩm`
})

const pages = computed(() => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
  let end = Math.min(lastPage.value, start + maxVisible - 1)

  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

// Fetch products theo category từ API
async function fetchCategoryProducts(page = 1) {
  loading.value = true
  error.value = null
  try {
    const response = await fetch(`/api/products?category_id=${categoryId.value}&per_page=12&page=${page}`)
    const result = await response.json()
    
    if (result.status === 'success') {
      products.value = result.data.map(product => ({
        id: product.id,
        name: product.name,
        price: parseFloat(product.price),
        category_id: product.category_id,
        thumbnail_url: product.thumbnail_url || '',
        stock_quantity: product.stock_quantity,
      }))
      
      currentPage.value = result.meta?.current_page ?? page
      total.value = result.meta?.total ?? 0
      lastPage.value = result.meta?.last_page ?? 1
      
      // Get category name từ first product hoặc từ route params
      if (products.value.length > 0) {
        const categoryMap = {
          'CPU': '🧠',
          'RAM': '📊',
          'Mainboard': '🔲',
          'VGA': '🎮',
          'SSD': '💾',
          'PSU': '⚡',
          'Case': '🖥️',
        }
        const catResponse = await fetch('/api/categories')
        const catResult = await catResponse.json()
        const cat = catResult.data.find(c => c.id === categoryId.value)
        categoryName.value = cat?.name || 'Danh mục'
      }
    }
  } catch (err) {
    error.value = 'Không thể tải sản phẩm'
    console.error('Error fetching products:', err)
  } finally {
    loading.value = false
  }
}

function goToPage(page) {
  if (page < 1 || page > lastPage.value) return
  fetchCategoryProducts(page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function formatPrice(price) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
}

function addToCart(product) {
  cartStore.addToCart(product, 1)
  addedToCart.value = product.id
  setTimeout(() => {
    addedToCart.value = null
  }, 2000)
}

function goBack() {
  router.push('/home')
}

onMounted(() => {
  fetchCategoryProducts()
})
</script>

<template>
  <div class="min-h-screen bg-white font-system">
    <!-- HEADER -->
    <header class="sticky top-0 z-50 relative bg-white border-b border-gray-100 shadow-sm">
      <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <button
          @click="goBack"
          class="text-xl font-semibold text-gray-900 hover:text-gray-700 transition flex items-center gap-2"
        >
          ← Quay lại
        </button>

        <div class="flex items-center gap-6">
          <!-- Cart Icon -->
          <button
            @click="$router.push('/browse')"
            class="relative group cursor-pointer pointer-events-auto transition-all duration-200"
          >
            <span class="text-2xl group-hover:scale-110">🛒</span>
            <span v-if="cartStore.cartCount > 0" class="absolute -top-2 -right-3 bg-black text-white text-xs font-bold px-2 py-1 rounded-full">
              {{ cartStore.cartCount }}
            </span>
          </button>
        </div>
      </div>
    </header>

    <!-- MAIN CONTENT -->
    <div class="max-w-7xl mx-auto px-6 py-12">
      <!-- TITLE -->
      <div class="mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">{{ categoryName }}</h1>
        <p class="text-gray-600">{{ showingRange }}</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div v-for="i in 6" :key="i" class="animate-pulse">
          <div class="h-48 bg-gray-200 rounded-lg mb-4"></div>
          <div class="h-4 bg-gray-200 rounded mb-2"></div>
          <div class="h-4 bg-gray-200 rounded w-2/3"></div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-8 text-center">
        <p class="text-red-600">{{ error }}</p>
        <button 
          @click="fetchCategoryProducts"
          class="mt-4 px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-900 transition"
        >
          Thử lại
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="products.length === 0" class="bg-gray-50 border border-gray-200 rounded-lg p-12 text-center">
        <p class="text-gray-600">Không có sản phẩm trong danh mục này</p>
        <button 
          @click="goBack"
          class="mt-4 px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-900 transition"
        >
          Quay lại trang chủ
        </button>
      </div>

      <!-- Products Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div
          v-for="product in products"
          :key="product.id"
          class="group bg-white rounded-lg border border-gray-200 overflow-hidden hover:border-gray-400 transition-all duration-300 hover:shadow-lg"
        >
          <!-- Product Image Placeholder -->
          <div class="h-48 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-gray-400 group-hover:from-gray-200 group-hover:to-gray-300 transition">
            <span v-if="product.thumbnail_url">
              <img :src="product.thumbnail_url" :alt="product.name" class="w-full h-full object-cover" />
            </span>
            <span v-else class="text-4xl">📦</span>
          </div>

          <!-- Product Info -->
          <div class="p-6 space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 line-clamp-2 group-hover:text-black">
              {{ product.name }}
            </h3>

            <div class="space-y-2">
              <p class="text-lg font-bold text-gray-900">{{ formatPrice(product.price) }}</p>
              <p class="text-xs text-gray-500">Tồn kho: {{ product.stock_quantity }}</p>
            </div>

            <!-- Add to Cart Button -->
            <button
              @click="addToCart(product)"
              :disabled="product.stock_quantity === 0"
              :class="[
                'w-full py-3 rounded-lg font-medium transition-all duration-200 text-sm',
                product.stock_quantity === 0
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-black text-white hover:bg-gray-900 active:scale-95'
              ]"
            >
              <span v-if="addedToCart === product.id" class="text-green-400">✓ Đã thêm</span>
              <span v-else>{{ product.stock_quantity === 0 ? 'Hết hàng' : 'Thêm vào giỏ' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="lastPage > 1" class="flex items-center justify-center gap-2 pt-8">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="px-3 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 text-sm"
        >
          ← Trước
        </button>

        <button
          v-for="p in pages"
          :key="p"
          @click="goToPage(p)"
          :class="[
            'px-3 py-2 border rounded-lg min-w-[40px] text-sm',
            p === currentPage
              ? 'bg-black text-white border-black'
              : 'border-gray-300 hover:bg-gray-50'
          ]"
        >
          {{ p }}
        </button>

        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === lastPage"
          class="px-3 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 text-sm"
        >
          Sau →
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.font-system {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
