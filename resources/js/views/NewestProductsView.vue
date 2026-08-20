<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cartStore'

const router = useRouter()
const cartStore = useCartStore()

const categories = ref([])
const products = ref([])
const searchQuery = ref('')
const selectedCategory = ref(null)
const loading = ref(false)
const error = ref(null)
const showCart = ref(false)
const addedToCart = ref(null)
const showDetailModal = ref(false)
const selectedProduct = ref(null)

const filteredProducts = computed(() => {
  let result = products.value
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))

  if (selectedCategory.value) {
    result = result.filter(p => p.category_id === selectedCategory.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(p => p.name.toLowerCase().includes(query))
  }

  return result.slice(0, 6)
})

async function fetchCategories() {
  try {
    const response = await fetch('/api/categories')
    const result = await response.json()
    if (result.status === 'success') {
      const iconMap = {
        'CPU': '🧠',
        'MAIN': '🔧',
        'RAM': '📊',
        'VGA': '🎮',
        'SSD': '💾',
        'PSU': '⚡',
        'COOLER': '❄️',
        'CASE': '📦',
      }

      const slugMap = {
        'CPU': 'cpu',
        'MAIN': 'main',
        'RAM': 'ram',
        'VGA': 'vga',
        'SSD': 'ssd',
        'PSU': 'psu',
        'COOLER': 'cooler',
        'CASE': 'case',
      }

      const categoryOrder = ['CPU', 'MAIN', 'RAM', 'VGA', 'SSD', 'PSU', 'COOLER', 'CASE']

      categories.value = result.data
        .map(cat => ({
          ...cat,
          slug: cat.slug || slugMap[cat.name] || cat.name.toLowerCase().replace(/\s+/g, '-'),
          icon: iconMap[cat.name] || '🔧'
        }))
        .sort((a, b) => categoryOrder.indexOf(a.name) - categoryOrder.indexOf(b.name))
    }
  } catch (err) {
    console.error('Error fetching categories:', err)
  }
}

async function fetchProducts() {
  loading.value = true
  error.value = null
  try {
    const response = await fetch('/api/products?per_page=100')
    const text = await response.text()
    let result
    try {
      result = JSON.parse(text)
    } catch (parseError) {
      console.error('Invalid JSON response:', text)
      throw new Error('Server trả về dữ liệu không hợp lệ')
    }

    if (!response.ok) {
      const message = result?.message || `HTTP ${response.status}`
      throw new Error(message)
    }

    if (result.status === 'success') {
      products.value = result.data.map(product => ({
        id: product.id,
        name: product.name,
        price: parseFloat(product.price),
        category_id: product.category_id,
        thumbnail_url: product.thumbnail_url || '',
        stock_quantity: product.stock_quantity,
        description: product.description || '',
        created_at: product.created_at,
      }))
    } else {
      error.value = result.message || 'Không thể tải sản phẩm'
    }
  } catch (err) {
    console.error('Error fetching products:', err)
    error.value = err.message || 'Không thể tải sản phẩm'
  } finally {
    loading.value = false
  }
}

function updateSearch() {
  // Force update for search
}

function selectCategory(categoryId) {
  selectedCategory.value = selectedCategory.value === categoryId ? null : categoryId
}

function addToCart(product) {
  cartStore.addToCart(product, 1)
  addedToCart.value = product.id
  setTimeout(() => {
    addedToCart.value = null
  }, 2000)
}

function openProductDetail(product) {
  selectedProduct.value = product
  showDetailModal.value = true
}

function closeDetailModal() {
  showDetailModal.value = false
  selectedProduct.value = null
}

function formatPrice(price) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
}

const cartTotal = computed(() => cartStore.cartTotal)
const cartCount = computed(() => cartStore.cartCount)

function removeFromCart(productId) {
  cartStore.removeFromCart(productId)
}

function updateQuantity(productId, quantity) {
  if (quantity <= 0) {
    removeFromCart(productId)
  } else {
    cartStore.updateQuantity(productId, quantity)
  }
}

onMounted(() => {
  fetchCategories()
  fetchProducts()
})
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12 space-y-8">
    <div class="mb-6 sm:mb-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-widest">Sản phẩm mới</h2>
          <p class="text-sm text-gray-700 dark:text-gray-300 mt-2">{{ filteredProducts.length }} sản phẩm</p>
        </div>
      </div>
    </div>

    <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
      <div v-for="i in 6" :key="i" class="animate-pulse rounded-2xl border border-gray-200 dark:border-slate-700 p-4">
        <div class="aspect-[4/3] bg-gray-200 dark:bg-slate-700 rounded-xl mb-4"></div>
        <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded mb-3"></div>
        <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded w-2/3"></div>
      </div>
    </div>

    <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-8 text-center border border-red-200 dark:border-red-800">
      <p class="text-red-600 dark:text-red-400">{{ error }}</p>
      <button @click="fetchProducts" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
        Thử lại
      </button>
    </div>

    <div v-else-if="filteredProducts.length === 0" class="bg-gray-50 dark:bg-slate-800 rounded-2xl p-8 sm:p-12 text-center border border-gray-200 dark:border-slate-700">
      <p class="text-gray-600 dark:text-gray-300">Không tìm thấy sản phẩm phù hợp</p>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
      <div
        v-for="product in filteredProducts"
        :key="product.id"
        class="group bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 overflow-hidden hover:border-cyan-400 dark:hover:border-cyan-500 transition-all duration-300 hover:shadow-xl hover:shadow-cyan-500/10 flex flex-col"
      >
        <div class="aspect-[4/3] bg-gray-50 dark:bg-slate-700/50 flex items-center justify-center overflow-hidden">
          <img
            v-if="product.thumbnail_url"
            :src="product.thumbnail_url"
            :alt="product.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
          <div v-else class="text-5xl text-gray-400 dark:text-slate-500">🛍️</div>
        </div>

        <div class="p-4 sm:p-5 space-y-3 sm:space-y-4 flex flex-col flex-1">
          <div>
            <p class="text-xs uppercase tracking-widest text-gray-700 dark:text-gray-300 mb-1 sm:mb-2">
              {{ product.stock_quantity > 0 ? 'Còn hàng' : 'Hết hàng' }}
            </p>
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white leading-tight line-clamp-2">
              {{ product.name }}
            </h3>
          </div>

          <p class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 line-clamp-2">{{ product.description }}</p>

          <div class="space-y-3 pt-2 mt-auto">
            <div>
              <p class="text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest">Giá</p>
              <p class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white truncate">{{ formatPrice(product.price) }}</p>
            </div>
            <button
              @click="addToCart(product)"
              :disabled="product.stock_quantity === 0"
              class="w-full inline-flex items-center justify-center px-3 sm:px-4 py-2.5 rounded-xl text-xs sm:text-sm font-medium transition"
              :class="product.stock_quantity === 0
                ? 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 cursor-not-allowed'
                : 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100'"
            >
              {{ product.stock_quantity === 0 ? 'Hết' : 'Thêm vào' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
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

/* Slide Up Animation */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from {
  opacity: 0;
  transform: translateY(100%);
}

.slide-up-leave-to {
  opacity: 0;
  transform: translateY(100%);
}
</style>


