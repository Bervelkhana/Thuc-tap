<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useCartStore } from '../stores/cartStore'

const route = useRoute()
const cartStore = useCartStore()

const categories = ref([])
const products = ref([])
const filteredProducts = ref([])
const recentDiscountProducts = ref([])
const searchQuery = ref('')
const selectedCategory = ref(null)
const loading = ref(false)
const showCart = ref(false)
const addedToCart = ref(null)
const showDetailModal = ref(false)
const selectedProduct = ref(null)
const activeCategoryName = ref('')
const currentPage = ref(1)
const perPage = ref(12)

const totalPages = computed(() => Math.ceil(filteredProducts.value.length / perPage.value))

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredProducts.value.slice(start, end)
})

const showingRange = computed(() => {
  const total = filteredProducts.value.length
  if (total === 0) return '0 sản phẩm'
  const start = (currentPage.value - 1) * perPage.value + 1
  const end = Math.min(currentPage.value * perPage.value, total)
  return `${start}-${end} của ${total} sản phẩm`
})

const pages = computed(() => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
  let end = Math.min(totalPages.value, start + maxVisible - 1)

  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

function applyCategorySelectionBySlug(slug) {
  if (!slug || categories.value.length === 0) {
    return
  }

  const matchedCategory = categories.value.find((category) => category.slug === slug)
  if (matchedCategory) {
    selectedCategory.value = matchedCategory.id
    activeCategoryName.value = matchedCategory.name
    fetchProducts(matchedCategory.id)
    return
  }

  selectedCategory.value = null
  activeCategoryName.value = ''
  fetchProducts()
}

watch([selectedCategory, searchQuery], () => {
  currentPage.value = 1
})

function syncRouteCategory() {
  const slugFromRoute = typeof route.params.slug === 'string' ? route.params.slug : null
  
  if (slugFromRoute && categories.value.length > 0) {
    applyCategorySelectionBySlug(slugFromRoute)
  }
}

watch(
  () => route.params.slug,
  () => {
    syncRouteCategory()
  }
)

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

      syncRouteCategory()
    }
  } catch (err) {
    console.error('Error fetching categories:', err)
  }
}

async function fetchProducts(categoryId = null) {
  loading.value = true
  try {
    const params = new URLSearchParams({ per_page: '1000' })
    if (categoryId) {
      params.set('category_id', String(categoryId))
    }

    const response = await fetch(`/api/products?${params.toString()}`)
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
        discount_percentage: product.discount_percentage || 0,
        sale_price: product.sale_price || parseFloat(product.price),
        is_on_sale: product.is_on_sale || false,
      }))
      filterProducts()
    }
  } catch (err) {
    console.error('Error fetching products:', err)
  } finally {
    loading.value = false
  }
}

async function fetchRecentDiscounts() {
  try {
    const response = await fetch('/api/products/recent-discounts')
    const result = await response.json()
    if (result.status === 'success') {
      recentDiscountProducts.value = result.data.map(product => ({
        id: product.id,
        name: product.name,
        price: parseFloat(product.price),
        sale_price: product.sale_price ? parseFloat(product.sale_price) : parseFloat(product.price),
        discount_percentage: product.discount_percentage || 0,
        is_on_sale: product.is_on_sale || false,
        thumbnail_url: product.thumbnail_url || '',
        stock_quantity: product.stock_quantity,
        description: product.description || '',
      }))
    } else {
      recentDiscountProducts.value = []
    }
  } catch (err) {
    console.error('Error fetching recent discounts:', err)
    recentDiscountProducts.value = []
  }
}

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

const updateSearch = () => {
  filterProducts()
}

function goToPage(page) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  window.scrollTo({ top: 0, behavior: 'smooth' })
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
  fetchRecentDiscounts()
})
</script>

<template>
  <section class="space-y-8">
    <div class="mb-6 sm:mb-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-widest">
            {{ activeCategoryName }}
          </h2>
          <p class="text-sm text-gray-700 dark:text-gray-300 mt-2">{{ showingRange }}</p>
        </div>
      </div>
    </div>

    <!-- Recent Discounts Section -->
    <div v-if="recentDiscountProducts.length > 0 && !activeCategoryName" class="mb-8">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-red-600 dark:text-red-400">🔥 Vừa giảm giá</h2>
        <span class="text-xs text-gray-600 dark:text-gray-300">3 sản phẩm vừa được chỉnh giá</span>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
        <div
          v-for="product in recentDiscountProducts"
          :key="'recent-'+product.id"
          class="group bg-white dark:bg-slate-800 rounded-2xl border border-red-100 dark:border-red-900/50 overflow-hidden hover:border-red-300 dark:hover:border-red-700 transition-all duration-300 hover:shadow-lg hover:shadow-red-500/10 flex flex-col relative"
        >
          <div class="absolute top-2 right-2 z-10">
            <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-lg">-{{ product.discount_percentage }}%</span>
          </div>
          <div class="aspect-[4/3] bg-gray-50 dark:bg-slate-700/50 flex items-center justify-center overflow-hidden">
            <img
              v-if="product.thumbnail_url"
              :src="product.thumbnail_url"
              :alt="product.name"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
             <div v-else class="text-5xl text-gray-600 dark:text-slate-400">🛍️</div>
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
            <div class="flex items-center justify-between gap-3 sm:gap-4 pt-2 mt-auto">
              <div>
              <p class="text-xs text-gray-600 dark:text-gray-300 uppercase tracking-widest">Giá</p>
                <p class="text-xs text-gray-600 dark:text-gray-300 line-through">{{ formatPrice(product.price) }}</p>
                <p class="text-base sm:text-lg font-semibold text-red-600 dark:text-red-400">{{ formatPrice(product.sale_price) }}</p>
              </div>
              <button
                @click="addToCart(product)"
                :disabled="product.stock_quantity === 0"
                :class="[
                  'inline-flex items-center justify-center px-3 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-medium transition',
                  product.stock_quantity === 0
                    ? 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 cursor-not-allowed'
                    : 'bg-red-600 text-white hover:bg-red-700'
                ]"
              >
                {{ product.stock_quantity === 0 ? 'Hết' : 'Thêm vào' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
      <div v-for="i in 6" :key="i" class="animate-pulse rounded-2xl border border-gray-200 dark:border-slate-700 p-4">
        <div class="aspect-[4/3] bg-gray-200 dark:bg-slate-700 rounded-xl mb-4"></div>
        <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded mb-3"></div>
        <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded w-2/3"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredProducts.length === 0" class="bg-white dark:bg-slate-800 rounded-2xl p-8 sm:p-12 text-center border border-gray-200 dark:border-slate-700 shadow-sm">
      <div class="text-5xl mb-4 opacity-40">📦</div>
      <p class="text-gray-600 dark:text-gray-300 mb-4">Không tìm thấy sản phẩm phù hợp</p>
      <router-link
        to="/browse"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors shadow-lg shadow-gray-900/20 dark:shadow-white/20"
      >
        Khám phá danh mục khác
      </router-link>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
      <div
        v-for="product in paginatedProducts"
        :key="product.id"
        class="group bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 overflow-hidden hover:border-cyan-400 dark:hover:border-cyan-500 transition-all duration-300 hover:shadow-xl hover:shadow-cyan-500/10 flex flex-col"
      >
        <!-- Product Image -->
        <div class="aspect-[4/3] bg-gray-50 dark:bg-slate-700/50 flex items-center justify-center overflow-hidden relative">
          <img
            v-if="product.thumbnail_url"
            :src="product.thumbnail_url"
            :alt="product.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
            <div v-else class="text-5xl text-gray-600 dark:text-slate-400">🛍️</div>
          <span v-if="product.discount_percentage > 0" class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-lg shadow-lg shadow-red-500/30">-{{ product.discount_percentage }}%</span>
        </div>

        <!-- Product Info -->
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

           <div class="flex items-center justify-between gap-3 sm:gap-4 pt-2 mt-auto">
             <div>
                 <p class="text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest">Giá</p>
               <div v-if="product.discount_percentage > 0">
                 <p class="text-xs text-gray-600 dark:text-gray-300 line-through">{{ formatPrice(product.price) }}</p>
                 <p class="text-base sm:text-lg font-semibold text-red-600 dark:text-red-400">{{ formatPrice(product.sale_price) }}</p>
               </div>
               <p v-else class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">{{ formatPrice(product.price) }}</p>
             </div>

            <button
              @click="addToCart(product)"
              :disabled="product.stock_quantity === 0"
              :class="[
                'inline-flex items-center justify-center px-3 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-medium transition',
                product.stock_quantity === 0
                   ? 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 cursor-not-allowed'
                  : 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100'
              ]"
            >
              {{ product.stock_quantity === 0 ? 'Hết' : 'Thêm vào' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="col-span-1 sm:col-span-2 lg:col-span-3 flex items-center justify-center gap-2 pt-8">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-slate-800 text-sm text-gray-700 dark:text-gray-300"
        >
          ← Trước
        </button>

        <button
          v-for="p in pages"
          :key="p"
          @click="goToPage(p)"
          :class="[
            'px-3 py-2 border rounded-lg min-w-[40px] text-sm transition-colors',
            p === currentPage
              ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-gray-900 dark:border-white'
              : 'border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800'
          ]"
        >
          {{ p }}
        </button>

        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-slate-800 text-sm text-gray-700 dark:text-gray-300"
        >
          Sau →
        </button>
      </div>
    </div>
  </section>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>



