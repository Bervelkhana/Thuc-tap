<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useCartStore } from '../stores/cartStore'

const cartStore = useCartStore()
const categories = ref([])
const products = ref([])
const loading = ref(false)
const error = ref(null)
const currentPage = ref(1)
const lastPage = ref(1)
const totalProducts = ref(0)

// Filter state
const filters = reactive({
  category_id: null,
  min_price: null,
  max_price: null,
  sort: 'created_at',
  search: '',
})

// Fetch categories
async function fetchCategories() {
  try {
    const response = await fetch('/api/categories')
    const result = await response.json()
    if (result.status === 'success') {
      categories.value = result.data
    }
  } catch (err) {
    console.error('Error fetching categories:', err)
  }
}

// Fetch products with filters
async function fetchProducts(page = 1) {
  loading.value = true
  error.value = null
  
  try {
    const params = new URLSearchParams()
    params.append('page', page)
    params.append('per_page', 12)
    
    if (filters.category_id) params.append('category_id', filters.category_id)
    if (filters.min_price) params.append('min_price', filters.min_price)
    if (filters.max_price) params.append('max_price', filters.max_price)
    if (filters.sort) params.append('sort', filters.sort)
    
    const response = await fetch(`/api/products?${params.toString()}`)
    const result = await response.json()
    
    if (result.status === 'success') {
      products.value = result.data.map(product => ({
        ...product,
        price: parseFloat(product.price),
      }))
      currentPage.value = result.meta.current_page
      lastPage.value = result.meta.last_page
      totalProducts.value = result.meta.total
    }
  } catch (err) {
    error.value = 'Failed to load products'
    console.error('Error fetching products:', err)
  } finally {
    loading.value = false
  }
}

// Reset filters
function resetFilters() {
  filters.category_id = null
  filters.min_price = null
  filters.max_price = null
  filters.sort = 'created_at'
  filters.search = ''
  currentPage.value = 1
}

// Handle filter changes
watch(filters, () => {
  currentPage.value = 1
  fetchProducts()
}, { deep: true })

// Add to cart
function addToCart(product, quantity = 1) {
  cartStore.addToCart(product, quantity)
  // Show toast notification (simple alert for now)
  alert(`${product.name} đã thêm vào giỏ hàng`)
}

// Format price
function formatPrice(price) {
  return new Intl.NumberFormat('vi-VN', { 
    style: 'currency', 
    currency: 'VND' 
  }).format(price)
}

// Load initial data
onMounted(() => {
  fetchCategories()
  fetchProducts()
})
</script>

<template>
  <div class="min-h-screen bg-[var(--bg-body)] font-body transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8">
        
        <!-- Sidebar Filters -->
        <div class="lg:col-span-1">
          <div class="space-y-8">
            <!-- Filter Title -->
            <div class="space-y-4">
              <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-widest">Filters</h3>
              <div class="w-8 h-px bg-gray-900 dark:bg-white"></div>
            </div>

            <!-- Category Filter -->
            <div class="space-y-3">
              <h4 class="text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wide">Category</h4>
              <div class="space-y-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                  <input 
                    v-model="filters.category_id" 
                    :value="null" 
                    type="radio"
                    class="w-4 h-4 border-gray-300 rounded"
                  >
                  <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white">All</span>
                </label>
                <label v-for="cat in categories" :key="cat.id" class="flex items-center gap-3 cursor-pointer group">
                  <input 
                    v-model="filters.category_id" 
                    :value="cat.id" 
                    type="radio"
                    class="w-4 h-4 border-gray-300 rounded"
                  >
                  <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white">{{ cat.name }}</span>
                </label>
              </div>
            </div>

            <!-- Price Filter -->
            <div class="space-y-3">
              <h4 class="text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wide">Price Range</h4>
              <div class="space-y-2">
                 <input 
                   v-model="filters.min_price" 
                   type="number" 
                   placeholder="Min" 
                    class="w-full px-3 py-2 border border-gray-200 dark:border-slate-600 rounded text-sm placeholder-gray-600 dark:placeholder-gray-300 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-cyan-400"
                 >
                 <input 
                   v-model="filters.max_price" 
                   type="number" 
                   placeholder="Max" 
                    class="w-full px-3 py-2 border border-gray-200 dark:border-slate-600 rounded text-sm placeholder-gray-600 dark:placeholder-gray-300 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-cyan-400"
                 >
              </div>
            </div>

            <!-- Sort -->
            <div class="space-y-3">
              <h4 class="text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wide">Sort By</h4>
              <select v-model="filters.sort" class="w-full px-3 py-2 border border-gray-200 dark:border-slate-600 rounded text-sm bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-cyan-400">
                <option value="created_at">Newest</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
              </select>
            </div>

            <!-- Reset Filters -->
            <button 
              @click="resetFilters"
              class="w-full px-4 py-2 bg-gray-100 dark:bg-slate-800 text-gray-900 dark:text-white text-sm font-medium rounded hover:bg-gray-200 dark:hover:bg-slate-700 transition"
            >
              Reset Filters
            </button>
          </div>
        </div>

        <!-- Products Grid -->
        <div class="lg:col-span-3 space-y-8">
          <!-- Results Info -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-widest">Products</h2>
               <span class="text-xs text-gray-600 dark:text-gray-300">{{ totalProducts }} results</span>
            </div>
            <div class="w-8 h-px bg-gray-900 dark:bg-white"></div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div v-for="i in 6" :key="i" class="space-y-4">
              <div class="h-48 bg-gray-100 dark:bg-slate-700 rounded-lg animate-pulse"></div>
              <div class="h-4 bg-gray-100 dark:bg-slate-700 rounded w-2/3 animate-pulse"></div>
              <div class="h-4 bg-gray-100 dark:bg-slate-700 rounded w-1/2 animate-pulse"></div>
            </div>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg p-8 text-center">
            <p class="text-gray-600 dark:text-gray-300">{{ error }}</p>
          </div>

          <!-- Empty State -->
          <div v-else-if="products.length === 0" class="bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg p-12 text-center">
            <p class="text-gray-600 dark:text-gray-300">No products found. Try adjusting your filters.</p>
          </div>

          <!-- Products Grid -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <article
              v-for="product in products"
              :key="product.id"
              class="group bg-white dark:bg-slate-800 rounded-lg border border-gray-100 dark:border-slate-700 overflow-hidden hover:border-gray-300 dark:hover:border-slate-600 transition-all duration-300"
            >
                 <div class="aspect-square bg-gray-50 dark:bg-slate-700/50 flex items-center justify-center text-gray-700 dark:text-slate-300 group-hover:bg-gray-100 dark:group-hover:bg-slate-700 transition duration-300 overflow-hidden">
                 <img 
                   v-if="product.thumbnail_url" 
                   :src="product.thumbnail_url" 
                   :alt="product.name"
                   class="w-full h-full object-contain"
                 >
                 <span v-else>Product Image</span>
               </div>
              
              <div class="p-6 space-y-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2">
                  {{ product.name }}
                </h4>
                
                <div class="space-y-2">
                  <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatPrice(product.price) }}</p>
                  <p class="text-xs text-gray-600 dark:text-gray-300">
                    Stock: {{ product.stock_quantity }}
                  </p>
                </div>
                
                <button 
                  @click="addToCart(product)"
                  :disabled="product.stock_quantity === 0"
                  class="w-full mt-4 bg-black dark:bg-white text-white dark:text-gray-900 py-3 rounded-lg font-medium hover:bg-gray-900 dark:hover:bg-gray-100 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ product.stock_quantity > 0 ? 'Add to Cart' : 'Out of Stock' }}
                </button>
              </div>
            </article>
          </div>

          <!-- Pagination -->
          <div v-if="!loading && products.length > 0" class="flex items-center justify-center gap-4 pt-8">
            <button 
              @click="fetchProducts(currentPage - 1)"
              :disabled="currentPage === 1"
              class="px-4 py-2 border border-gray-200 dark:border-slate-600 rounded text-sm font-medium hover:border-gray-900 dark:hover:border-white disabled:opacity-50 disabled:cursor-not-allowed text-gray-700 dark:text-gray-300"
            >
              Previous
            </button>
            
            <div class="flex items-center gap-2">
                  <span class="text-sm text-gray-600 dark:text-gray-300">Page {{ currentPage }} of {{ lastPage }}</span>
            </div>
            
            <button 
              @click="fetchProducts(currentPage + 1)"
              :disabled="currentPage === lastPage"
              class="px-4 py-2 border border-gray-200 dark:border-slate-600 rounded text-sm font-medium hover:border-gray-900 dark:hover:border-white disabled:opacity-50 disabled:cursor-not-allowed text-gray-700 dark:text-gray-300"
            >
              Next
            </button>
          </div>
        </div>
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
</style>

