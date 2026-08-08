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
const showCart = ref(false)
const addedToCart = ref(null)
const showDetailModal = ref(false)
const selectedProduct = ref(null)

const filteredProductsList = computed(() => {
  let result = products.value
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .slice(0, 6)

  if (selectedCategory.value) {
    result = result.filter(p => p.category_id === selectedCategory.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(p => p.name.toLowerCase().includes(query))
  }

  return result
})

async function fetchCategories() {
  try {
    const response = await fetch('/api/categories')
    const result = await response.json()
    if (result.status === 'success') {
      const iconMap = {
        'CPU': '🧠',
        'RAM': '📊',
        'Mainboard': '🔲',
        'VGA': '🎮',
        'SSD': '💾',
        'PSU': '⚡',
        'Case': '🖥️',
      }

      const slugMap = {
        'CPU': 'cpu',
        'RAM': 'ram',
        'Mainboard': 'mainboard',
        'VGA': 'vga',
        'SSD': 'ssd',
        'PSU': 'psu',
        'Case': 'case',
      }

      categories.value = result.data.map(cat => ({
        ...cat,
        slug: cat.slug || slugMap[cat.name] || cat.name.toLowerCase().replace(/\s+/g, '-'),
        icon: iconMap[cat.name] || '🔧'
      }))
    }
  } catch (err) {
    console.error('Error fetching categories:', err)
  }
}

async function fetchProducts() {
  loading.value = true
  try {
    const response = await fetch('/api/products?per_page=100')
    const result = await response.json()
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
    }
  } catch (err) {
    console.error('Error fetching products:', err)
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

// Update filtered products dựa vào computed
const filteredProducts = computed(() => {
  let result = products.value
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .slice(0, 6)

  if (selectedCategory.value) {
    result = result.filter(p => p.category_id === selectedCategory.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(p => p.name.toLowerCase().includes(query))
  }

  return result
})

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
  <div class="min-h-screen bg-white font-system">
    <!-- HEADER -->
    <header class="sticky top-0 z-40 bg-white border-b border-gray-100 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
        <router-link to="/home" class="text-xl font-semibold text-gray-900 hover:text-gray-700 transition">
          ← TechGear
        </router-link>
        
        <div class="flex items-center gap-4 sm:gap-6">
          <div class="hidden md:flex items-center bg-gray-100 rounded-lg px-4 py-2 flex-1 max-w-xs">
            <input
              v-model="searchQuery"
              @input="updateSearch"
              type="text"
              placeholder="Tìm kiếm sản phẩm..."
              class="bg-transparent outline-none text-sm text-gray-700 placeholder-gray-500 w-full"
            />
            <span class="text-gray-400">🔍</span>
          </div>

          <button @click="showCart = !showCart" class="relative group cursor-pointer transition-all duration-200">
            <span class="text-2xl group-hover:scale-110">🛒</span>
            <span v-if="cartCount > 0" class="absolute -top-2 -right-3 bg-black text-white text-xs font-bold px-2 py-1 rounded-full">
              {{ cartCount }}
            </span>
          </button>
        </div>
      </div>

      <div class="md:hidden px-4 pb-4">
        <div class="flex items-center bg-gray-100 rounded-lg px-4 py-2">
          <input
            v-model="searchQuery"
            @input="updateSearch"
            type="text"
            placeholder="Tìm kiếm..."
            class="bg-transparent outline-none text-sm text-gray-700 placeholder-gray-500 w-full"
          />
          <span class="text-gray-400">🔍</span>
        </div>
      </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 lg:py-12">
      <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
        <aside class="w-full lg:w-[280px] xl:w-[300px] shrink-0 order-2 lg:order-1">
          <div class="sticky top-24 rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
              <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-widest">Danh mục</h2>
            </div>

            <div class="p-3 border-b border-gray-100 space-y-2">
              <router-link to="/browse" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-left transition-all duration-200 border-2 bg-black text-white border-black hover:bg-gray-900">
                <span class="text-xl shrink-0">🆕</span>
                <span class="text-sm font-medium leading-tight flex-1">Sản phẩm mới</span>
              </router-link>
              <router-link to="/browse-prebuilt" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-left transition-all duration-200 border-2 bg-white text-gray-800 border-transparent hover:border-gray-200 hover:bg-gray-50">
                <span class="text-xl shrink-0">🧩</span>
                <span class="text-sm font-medium leading-tight flex-1">Cấu hình xây sẵn</span>
              </router-link>
              <a href="/ai-build" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-left transition-all duration-200 border-2 bg-white text-gray-800 border-transparent hover:border-gray-200 hover:bg-gray-50">
                <span class="text-xl shrink-0">🤖</span>
                <span class="text-sm font-medium leading-tight flex-1">Xây dựng cấu hình bằng AI</span>
              </a>
              <a href="/pc-build" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-left transition-all duration-200 border-2 bg-white text-gray-800 border-transparent hover:border-gray-200 hover:bg-gray-50">
                <span class="text-xl shrink-0">🔧</span>
                <span class="text-sm font-medium leading-tight flex-1">Xây dựng cấu hình</span>
              </a>
            </div>

            <div class="p-3 space-y-2">
              <a
                v-for="category in categories"
                :key="category.id"
                :href="`/browser-${category.slug}`"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-left transition-all duration-200 border-2 bg-white text-gray-800 border-transparent hover:border-gray-200 hover:bg-gray-50"
              >
                <span class="text-xl shrink-0">{{ category.icon }}</span>
                <span class="text-sm font-medium leading-tight flex-1">{{ category.name }}</span>
              </a>
            </div>
          </div>
        </aside>

        <main class="flex-1 order-1 lg:order-2 min-w-0">
          <section>
            <div class="mb-6 sm:mb-8">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                  <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-widest">Sản phẩm mới</h2>
                  <p class="text-sm text-gray-500 mt-2">{{ filteredProducts.length }} sản phẩm</p>
                </div>
              </div>
            </div>

            <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
              <div v-for="i in 6" :key="i" class="animate-pulse rounded-2xl border border-gray-200 p-4">
                <div class="aspect-[4/3] bg-gray-200 rounded-xl mb-4"></div>
                <div class="h-4 bg-gray-200 rounded mb-3"></div>
                <div class="h-4 bg-gray-200 rounded w-2/3"></div>
              </div>
            </div>

            <div v-else-if="filteredProducts.length === 0" class="bg-gray-50 rounded-2xl p-8 sm:p-12 text-center border border-gray-200">
              <p class="text-gray-600">Không tìm thấy sản phẩm phù hợp</p>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
              <div
                v-for="product in filteredProducts"
                :key="product.id"
                class="group bg-white rounded-2xl border border-gray-200 overflow-hidden hover:border-gray-400 transition-all duration-300 hover:shadow-lg flex flex-col"
              >
                <div class="aspect-[4/3] bg-gray-50 flex items-center justify-center overflow-hidden">
                  <img
                    v-if="product.thumbnail_url"
                    :src="product.thumbnail_url"
                    :alt="product.name"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                  />
                  <div v-else class="text-5xl text-gray-300">🛍️</div>
                </div>

                <div class="p-4 sm:p-5 space-y-3 sm:space-y-4 flex flex-col flex-1">
                  <div>
                    <p class="text-xs uppercase tracking-widest text-gray-400 mb-1 sm:mb-2">
                      {{ product.stock_quantity > 0 ? 'Còn hàng' : 'Hết hàng' }}
                    </p>
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 leading-tight line-clamp-2">
                      {{ product.name }}
                    </h3>
                  </div>

                  <p class="text-xs sm:text-sm text-gray-500 line-clamp-2">{{ product.description }}</p>

                  <div class="flex items-center justify-between gap-3 sm:gap-4 pt-2 mt-auto">
                    <div>
                      <p class="text-xs text-gray-400 uppercase tracking-widest">Giá</p>
                      <p class="text-base sm:text-lg font-semibold text-black">{{ formatPrice(product.price) }}</p>
                    </div>

                    <button
                      @click="addToCart(product)"
                      :disabled="product.stock_quantity === 0"
                      :class="[
                        'inline-flex items-center justify-center px-3 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-medium transition',
                        product.stock_quantity === 0
                          ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                          : 'bg-black text-white hover:bg-gray-900'
                      ]"
                    >
                      {{ product.stock_quantity === 0 ? 'Hết' : 'Thêm vào' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </main>
      </div>
    </div>

    <!-- MOBILE CART DRAWER -->
    <transition name="slide-up">
      <div
        v-if="showCart"
        class="fixed inset-0 z-50 bg-black/50"
        @click="showCart = false"
      ></div>
    </transition>

    <transition name="slide-up">
      <div
        v-if="showCart"
        class="fixed bottom-0 left-0 right-0 z-50 bg-white rounded-t-2xl p-6 max-h-[80vh] overflow-y-auto"
      >
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-lg font-bold text-gray-900">🛒 Giỏ hàng</h2>
          <button @click="showCart = false" class="text-2xl text-gray-400">✕</button>
        </div>

        <!-- Mobile Cart Items -->
        <div v-if="cartStore.items.length === 0" class="text-center py-8">
          <p class="text-gray-600">Giỏ hàng trống</p>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="item in cartStore.items"
            :key="item.product_id"
            class="flex gap-4 pb-4 border-b border-gray-200"
          >
            <div class="flex-1">
              <p class="font-medium text-gray-900">{{ item.name }}</p>
              <p class="text-sm text-gray-600">{{ formatPrice(item.price) }}</p>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="updateQuantity(item.product_id, item.quantity - 1)"
                class="w-6 h-6 flex items-center justify-center bg-gray-100 rounded text-sm"
              >
                −
              </button>
              <span class="w-6 text-center font-semibold">{{ item.quantity }}</span>
              <button
                @click="updateQuantity(item.product_id, item.quantity + 1)"
                class="w-6 h-6 flex items-center justify-center bg-gray-100 rounded text-sm"
              >
                +
              </button>
            </div>
          </div>

          <div class="border-t border-gray-200 pt-4 space-y-4">
            <div class="flex justify-between font-bold">
              <span>Tổng:</span>
              <span>{{ formatPrice(cartTotal) }}</span>
            </div>
            <router-link
              to="/checkout-new"
              class="block w-full text-center bg-black text-white py-3 rounded-xl font-medium"
            >
              Thanh toán
            </router-link>
          </div>
        </div>
      </div>
    </transition>
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
