<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCartStore } from '../stores/cartStore'

const cartStore = useCartStore()

const categories = ref([])
const products = ref([])
const filteredProducts = ref([])
const searchQuery = ref('')
const selectedCategory = ref(null)
const loading = ref(false)
const showCart = ref(false)
const addedToCart = ref(null)

// Fetch categories từ API
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
      categories.value = result.data.map(cat => ({
        ...cat,
        icon: iconMap[cat.name] || '🔧'
      }))
    }
  } catch (err) {
    console.error('Error fetching categories:', err)
  }
}

// Fetch sản phẩm từ API
async function fetchProducts() {
  loading.value = true
  try {
    const response = await fetch('/api/products?per_page=50')
    console.log('API Response status:', response.status)
    const result = await response.json()
    console.log('API Response:', result)
    if (result.status === 'success') {
      products.value = result.data.map(product => ({
        id: product.id,
        name: product.name,
        price: parseFloat(product.price),
        category_id: product.category_id,
        thumbnail_url: product.thumbnail_url || '',
        stock_quantity: product.stock_quantity,
      }))
      console.log('Products loaded:', products.value.length)
      filterProducts()
    } else {
      console.error('API error:', result)
    }
  } catch (err) {
    console.error('Error fetching products:', err)
  } finally {
    loading.value = false
  }
}

// Filter sản phẩm dựa trên category và search
function filterProducts() {
  let result = products.value

  if (selectedCategory.value) {
    result = result.filter(p => p.category_id === selectedCategory.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(p => p.name.toLowerCase().includes(query))
  }

  filteredProducts.value = result
}

// Watch search query
const updateSearch = () => {
  filterProducts()
}

// Watch category selection
const selectCategory = (categoryId) => {
  selectedCategory.value = selectedCategory.value === categoryId ? null : categoryId
  filterProducts()
}

// Add to cart
function addToCart(product) {
  cartStore.addToCart(product, 1)
  addedToCart.value = product.id
  setTimeout(() => {
    addedToCart.value = null
  }, 2000)
}

// Format price
function formatPrice(price) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
}

// Cart total
const cartTotal = computed(() => cartStore.cartTotal)
const cartCount = computed(() => cartStore.cartCount)

// Remove from cart
function removeFromCart(productId) {
  cartStore.removeFromCart(productId)
}

// Update quantity
function updateQuantity(productId, quantity) {
  if (quantity <= 0) {
    removeFromCart(productId)
  } else {
    cartStore.updateQuantity(productId, quantity)
  }
}

// Load data on mount
onMounted(() => {
  fetchCategories()
  fetchProducts()
})
</script>

<template>
  <div class="min-h-screen bg-white font-system">
    <!-- HEADER -->
    <header class="sticky top-0 z-40 bg-white border-b border-gray-100 shadow-sm">
      <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <router-link to="/home" class="text-xl font-semibold text-gray-900 hover:text-gray-700 transition">
          ← TechGear
        </router-link>
        
        <div class="flex items-center gap-6">
          <!-- Search Bar -->
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

          <!-- Cart Icon -->
          <button
            @click="showCart = !showCart"
            class="relative group cursor-pointer transition-all duration-200"
          >
            <span class="text-2xl group-hover:scale-110">🛒</span>
            <span v-if="cartCount > 0" class="absolute -top-2 -right-3 bg-black text-white text-xs font-bold px-2 py-1 rounded-full">
              {{ cartCount }}
            </span>
          </button>
        </div>
      </div>

      <!-- Mobile Search -->
      <div class="md:hidden px-6 pb-4">
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

    <!-- MAIN CONTENT -->
    <div class="max-w-7xl mx-auto px-6 py-12 flex gap-8">
      <!-- LEFT: Products -->
      <div class="flex-1">
        <!-- CATEGORIES SECTION -->
        <section class="mb-16">
          <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-widest mb-8">Danh mục</h2>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
            <button
              v-for="category in categories"
              :key="category.id"
              @click="selectCategory(category.id)"
              :class="[
                'group flex flex-col items-center gap-3 p-6 rounded-lg border-2 transition-all duration-300',
                selectedCategory === category.id
                  ? 'bg-black text-white border-black'
                  : 'bg-white text-gray-900 border-gray-200 hover:border-black hover:bg-gray-50'
              ]"
            >
              <span class="text-3xl group-hover:scale-110 transition-transform">{{ category.icon }}</span>
              <span class="text-xs font-medium text-center">{{ category.name }}</span>
            </button>
          </div>
        </section>

        <!-- PRODUCTS SECTION -->
        <section>
          <div class="flex items-center justify-between mb-8">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-widest">
              {{ selectedCategory ? 'Sản phẩm trong danh mục' : 'Tất cả sản phẩm' }}
            </h2>
            <span class="text-sm text-gray-600">{{ filteredProducts.length }} sản phẩm</span>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div v-for="i in 6" :key="i" class="animate-pulse">
              <div class="h-48 bg-gray-200 rounded-lg mb-4"></div>
              <div class="h-4 bg-gray-200 rounded mb-2"></div>
              <div class="h-4 bg-gray-200 rounded w-2/3"></div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else-if="filteredProducts.length === 0" class="bg-gray-50 rounded-lg p-12 text-center">
            <p class="text-gray-600">Không tìm thấy sản phẩm phù hợp</p>
          </div>

          <!-- Products Grid -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div
              v-for="product in filteredProducts"
              :key="product.id"
              class="group bg-white rounded-lg border border-gray-200 overflow-hidden hover:border-gray-400 transition-all duration-300 hover:shadow-lg"
            >
              <!-- Product Image Placeholder -->
              <div class="h-48 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-gray-400 group-hover:from-gray-200 group-hover:to-gray-300 transition">
                <span v-if="product.thumbnail_url">
                  <img :src="product.thumbnail_url" :alt="product.name" class="w-full h-full object-cover" />
                </span>
                <span v-else>📦</span>
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
        </section>
      </div>

      <!-- RIGHT: CART SIDEBAR -->
      <aside
        :class="[
          'w-80 bg-white rounded-lg border border-gray-200 p-6 sticky top-24 h-fit transition-all duration-300 max-lg:hidden',
          showCart ? 'block' : 'hidden'
        ]"
      >
        <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
          🛒 Giỏ hàng <span class="text-sm font-normal text-gray-600">({{ cartCount }} sản phẩm)</span>
        </h2>

        <!-- Cart Items -->
        <div v-if="cartStore.items.length === 0" class="text-center py-12">
          <p class="text-gray-600 text-sm">Giỏ hàng trống</p>
        </div>

        <div v-else class="space-y-6">
          <!-- Cart Items List -->
          <div class="space-y-4 max-h-96 overflow-y-auto">
            <div
              v-for="item in cartStore.items"
              :key="item.product_id"
              class="flex gap-4 pb-4 border-b border-gray-200"
            >
              <!-- Item Info -->
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 line-clamp-2">{{ item.name }}</p>
                <p class="text-xs text-gray-600 mt-1">{{ formatPrice(item.price) }}</p>
              </div>

              <!-- Quantity Control -->
              <div class="flex items-center gap-2">
                <button
                  @click="updateQuantity(item.product_id, item.quantity - 1)"
                  class="w-6 h-6 flex items-center justify-center rounded bg-gray-100 hover:bg-gray-200 text-xs"
                >
                  −
                </button>
                <span class="w-6 text-center text-sm font-semibold">{{ item.quantity }}</span>
                <button
                  @click="updateQuantity(item.product_id, item.quantity + 1)"
                  class="w-6 h-6 flex items-center justify-center rounded bg-gray-100 hover:bg-gray-200 text-xs"
                >
                  +
                </button>
              </div>

              <!-- Remove Button -->
              <button
                @click="removeFromCart(item.product_id)"
                class="text-gray-400 hover:text-red-600 transition text-sm"
              >
                ✕
              </button>
            </div>
          </div>

          <!-- Cart Summary -->
          <div class="space-y-4 pt-4 border-t border-gray-200">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Tạm tính:</span>
              <span class="font-semibold text-gray-900">{{ formatPrice(cartTotal) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Phí ship:</span>
              <span class="font-semibold text-gray-900">Miễn phí</span>
            </div>
            <div class="flex justify-between text-base font-bold border-t border-gray-200 pt-4">
              <span>Tổng cộng:</span>
              <span class="text-black">{{ formatPrice(cartTotal) }}</span>
            </div>

            <!-- Checkout Button -->
            <router-link
              to="/checkout-new"
              class="block w-full text-center bg-black text-white py-3 rounded-lg font-medium hover:bg-gray-900 transition-colors"
            >
              Thanh toán
            </router-link>
          </div>
        </div>
      </aside>
    </div>

    <!-- MOBILE CART DRAWER -->
    <transition name="slide-up">
      <div
        v-if="showCart"
        class="fixed inset-0 z-50 bg-black/50 lg:hidden"
        @click="showCart = false"
      ></div>
    </transition>

    <transition name="slide-up">
      <div
        v-if="showCart"
        class="fixed bottom-0 left-0 right-0 z-50 bg-white rounded-t-2xl p-6 max-h-[80vh] overflow-y-auto lg:hidden"
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
              class="block w-full text-center bg-black text-white py-3 rounded-lg font-medium"
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
