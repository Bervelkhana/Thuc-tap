<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const categories = ref([])
const flashSale = ref([])
const saleProducts = ref([])
const newestProducts = ref([])
const showModal = ref(false)
const modalMessage = ref('')
const loading = ref({
  categories: false,
  products: false,
  sales: false,
  newest: false,
})
const error = ref({
  categories: null,
  products: null,
  sales: null,
  newest: null,
})

// Fetch categories từ API
async function fetchCategories() {
  loading.value.categories = true
  error.value.categories = null
  try {
    const response = await fetch('/api/categories')
    const result = await response.json()
    
    if (result.status === 'success') {
      const iconMap = {
        'CPU': '🧠',
        'Mainboard': '🔧',
        'RAM': '📊',
        'VGA': '🎮',
        'SSD': '💾',
        'PSU': '⚡',
        'COOLER': '❄️',
        'CASE': '📦',
      }

      const categoryOrder = ['CPU', 'Mainboard', 'RAM', 'VGA', 'SSD', 'PSU', 'COOLER', 'CASE']

      categories.value = result.data
        .map(cat => ({
          ...cat,
          icon: iconMap[cat.name] || '🔧'
        }))
        .sort((a, b) => categoryOrder.indexOf(a.name) - categoryOrder.indexOf(b.name))
    }
  } catch (err) {
    error.value.categories = err.message
    console.error('Lỗi khi tải danh mục:', err)
  } finally {
    loading.value.categories = false
  }
}

// Fetch products từ API
async function fetchProducts() {
  loading.value.products = true
  error.value.products = null
  try {
    const response = await fetch('/api/products?per_page=5')
    const result = await response.json()
    
    if (result.status === 'success') {
      flashSale.value = result.data.map(product => ({
        id: product.id,
        name: product.name,
        price: parseFloat(product.price),
        oldPrice: parseFloat(product.price) * 1.15, // Giả sử discount 15% cho demo
        thumb: product.thumbnail_url || '',
      }))
    }
  } catch (err) {
    error.value.products = err.message
    console.error('Lỗi khi tải sản phẩm:', err)
  } finally {
    loading.value.products = false
  }
}

// Fetch sản phẩm đang sale từ API
async function fetchSaleProducts() {
  loading.value.sales = true
  error.value.sales = null
  try {
    const response = await fetch('/api/products/sales?per_page=6')
    const result = await response.json()
    
    if (result.status === 'success') {
  saleProducts.value = result.data.map(product => {
    const discount = product.discount_percentage || 0
    const salePrice = discount > 0 ? parseFloat(product.price) * (1 - discount / 100) : parseFloat(product.sale_price || product.price)
    return {
      id: product.id,
      name: product.name,
      price: parseFloat(product.price),
      salePrice: salePrice,
      discount: discount,
      thumbnail_url: product.thumbnail_url || '',
    }
  })
    }
  } catch (err) {
    error.value.sales = err.message
    console.error('Lỗi khi tải sản phẩm sale:', err)
  } finally {
    loading.value.sales = false
  }
}

// Fetch sản phẩm mới nhất từ API
async function fetchNewestProducts() {
  loading.value.newest = true
  error.value.newest = null
  try {
    const response = await fetch('/api/products/newest?per_page=6')
    const result = await response.json()
    
    if (result.status === 'success') {
      newestProducts.value = result.data.map(product => ({
        id: product.id,
        name: product.name,
        price: parseFloat(product.price),
        salePrice: parseFloat(product.sale_price || product.price),
        discount: product.discount_percentage || 0,
        thumbnail_url: product.thumbnail_url || '',
      }))
    }
  } catch (err) {
    error.value.newest = err.message
    console.error('Lỗi khi tải sản phẩm mới:', err)
  } finally {
    loading.value.newest = false
  }
}

// Load dữ liệu khi component mount
onMounted(() => {
  fetchCategories()
  fetchSaleProducts()
  fetchNewestProducts()
})

function formatPrice(v) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v)
}

function discount(item) {
  return Math.round((1 - item.price / item.oldPrice) * 100)
}

// Xử lý click nút CTA chính - điều hướng đến ProductBrowser
function handleCTAClick(event) {
  const btn = event.target
  
  // Tạo ripple effect
  const ripple = document.createElement('span')
  const rect = btn.getBoundingClientRect()
  const size = Math.max(rect.width, rect.height)
  const x = event.clientX - rect.left - size / 2
  const y = event.clientY - rect.top - size / 2
  
  ripple.style.width = ripple.style.height = size + 'px'
  ripple.style.left = x + 'px'
  ripple.style.top = y + 'px'
  ripple.classList.add('ripple')
  btn.appendChild(ripple)
  
  setTimeout(() => {
    ripple.remove()
    // Điều hướng đến ProductBrowser sau hiệu ứng ripple
    router.push({ name: 'product-browser' })
  }, 600)
}

function closeModal() {
  showModal.value = false
}
</script>

<template>
  <div class="min-h-screen bg-white font-system">
    <!-- ===== HEADER - Minimalist ===== -->
    <header class="sticky top-0 z-50 bg-white border-b border-gray-100">
      <div class="max-w-6xl mx-auto px-8 py-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-900 tracking-tight">TechGear</h1>
        
        <nav class="flex items-center gap-12">
          <button class="text-sm text-gray-600 hover:text-gray-900 transition duration-200">Catalog</button>
          <button class="text-sm text-gray-600 hover:text-gray-900 transition duration-200">About</button>
          <a href="/ai-build" class="text-sm text-gray-600 hover:text-gray-900 transition duration-200">Xây dựng cấu hình bằng AI</a>
          <button class="relative group cursor-pointer">
            <span class="text-sm text-gray-600 group-hover:text-gray-900 transition duration-200">Cart</span>
            <span class="absolute -top-2 -right-4 bg-black text-white text-xs font-medium px-2 py-1 rounded-full">0</span>
          </button>
        </nav>
      </div>
    </header>

    <!-- ===== HERO - Clean Minimalist ===== -->
    <section class="relative flex items-center justify-center bg-white overflow-hidden py-32">
      <div class="relative text-center max-w-3xl mx-auto px-8 space-y-12">
        <!-- Main Heading -->
        <h2 class="text-6xl md:text-7xl font-light text-gray-900 leading-tight tracking-tight">
          Linh kiện PC chất lượng
        </h2>
        
        <!-- Subheading -->
        <p class="text-lg text-gray-600 leading-relaxed max-w-2xl mx-auto font-light">
          Khám phá bộ sưu tập linh kiện máy tính premium với giá cạnh tranh. Tư vấn cấu hình thông minh bằng AI.
        </p>
        
        <!-- Whitespace -->
        <div class="pt-8"></div>
        
        <!-- Primary CTA Button -->
        <button 
          @click="handleCTAClick"
          class="inline-flex items-center justify-center px-10 py-4 bg-black text-white font-medium rounded-lg overflow-hidden group hover:bg-gray-900 transition-all duration-300 shadow-sm hover:shadow-xl"
        >
          <span class="relative z-10">Khám phá ngay</span>
        </button>
        
        <!-- Secondary CTA -->
        <div class="pt-4">
          <button class="text-sm text-gray-600 hover:text-gray-900 font-medium transition duration-200">
            Xem AI PC Builder →
          </button>
        </div>
      </div>
    </section>

    <!-- ===== CATEGORIES SECTION ===== -->
    <section class="max-w-6xl mx-auto px-8 py-32 space-y-16">
      <div class="space-y-6">
        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-widest">Danh mục</h3>
        <div class="w-16 h-px bg-gray-900"></div>
      </div>
      
      <!-- Loading State -->
      <div v-if="loading.categories" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-8">
        <div v-for="i in 7" :key="i" class="h-32 bg-gray-100 rounded-lg animate-pulse"></div>
      </div>
      
      <!-- Error State -->
      <div v-else-if="error.categories" class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
        <p class="text-gray-600">Không thể tải danh mục. Vui lòng thử lại.</p>
      </div>
      
      <!-- Categories Grid -->
      <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-8">
        <router-link
          v-for="c in categories"
          :key="c.id"
          :to="`/category/${c.id}`"
          class="group flex flex-col items-center gap-5 p-8 bg-white rounded-lg hover:bg-black hover:text-white transition-all duration-300 border border-gray-100 hover:border-black cursor-pointer"
        >
          <span class="text-4xl group-hover:scale-110 transition-transform duration-300">{{ c.icon }}</span>
          <span class="text-xs font-medium text-gray-700 group-hover:text-white text-center">{{ c.name }}</span>
        </router-link>
      </div>
    </section>

    <!-- ===== SALE SECTION ===== -->
    <section class="max-w-6xl mx-auto px-8 py-32 space-y-16">
      <div class="space-y-6">
        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-widest">🔥 Sản phẩm giảm giá</h3>
        <div class="w-16 h-px bg-black"></div>
      </div>
      
      <!-- Loading State -->
      <div v-if="loading.sales" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div v-for="i in 6" :key="i" class="bg-white rounded-lg border border-gray-100 overflow-hidden">
          <div class="h-48 bg-gray-100 animate-pulse"></div>
          <div class="p-8 space-y-4">
            <div class="h-4 bg-gray-100 rounded animate-pulse w-1/3"></div>
            <div class="h-4 bg-gray-100 rounded animate-pulse w-2/3"></div>
            <div class="h-6 bg-gray-100 rounded animate-pulse w-1/2"></div>
            <div class="h-10 bg-gray-100 rounded animate-pulse"></div>
          </div>
        </div>
      </div>
      
      <!-- Error State -->
      <div v-else-if="error.sales" class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
        <p class="text-gray-600">Không thể tải sản phẩm giảm giá. Vui lòng thử lại.</p>
      </div>
      
      <!-- Empty State -->
      <div v-else-if="saleProducts.length === 0" class="bg-gray-50 border border-gray-200 rounded-lg p-12 text-center">
        <p class="text-gray-600">Hiện tại không có sản phẩm giảm giá.</p>
      </div>
      
      <!-- Sale Products Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <article
          v-for="item in saleProducts"
          :key="item.id"
          class="group bg-white rounded-lg border border-gray-100 overflow-hidden hover:border-gray-300 transition-all duration-300"
        >
           <div class="aspect-square bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-gray-100 transition duration-300 relative overflow-hidden">
             <img v-if="item.thumbnail_url" :src="item.thumbnail_url" :alt="item.name" class="w-full h-full object-contain">
             <span v-else>Product Image</span>
            <!-- Sale Badge -->
            <span v-if="item.discount > 0" class="absolute top-4 right-4 bg-red-500 text-white text-xs font-bold px-3 py-1.5 rounded">
              -{{ item.discount }}%
            </span>
          </div>
          
          <div class="p-8 space-y-4">
            <h4 class="text-sm font-medium text-gray-900 line-clamp-2">
              {{ item.name }}
            </h4>
            
            <div class="space-y-2">
              <p class="text-lg font-semibold text-gray-900">{{ formatPrice(item.salePrice) }}</p>
              <p v-if="item.discount > 0" class="text-xs text-gray-500 line-through">{{ formatPrice(item.price) }}</p>
            </div>
            
            <button class="w-full mt-6 bg-black text-white py-3 rounded-lg font-medium hover:bg-gray-900 transition-colors duration-200">
              Thêm vào giỏ
            </button>
          </div>
        </article>
      </div>
    </section>

    <!-- ===== NEWEST PRODUCTS SECTION ===== -->
    <section class="max-w-6xl mx-auto px-8 py-32 space-y-16">
      <div class="space-y-6">
        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-widest">✨ Sản phẩm mới</h3>
        <div class="w-16 h-px bg-black"></div>
      </div>
      
      <!-- Loading State -->
      <div v-if="loading.newest" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div v-for="i in 6" :key="i" class="bg-white rounded-lg border border-gray-100 overflow-hidden">
          <div class="h-48 bg-gray-100 animate-pulse"></div>
          <div class="p-8 space-y-4">
            <div class="h-4 bg-gray-100 rounded animate-pulse w-1/3"></div>
            <div class="h-4 bg-gray-100 rounded animate-pulse w-2/3"></div>
            <div class="h-6 bg-gray-100 rounded animate-pulse w-1/2"></div>
            <div class="h-10 bg-gray-100 rounded animate-pulse"></div>
          </div>
        </div>
      </div>
      
      <!-- Error State -->
      <div v-else-if="error.newest" class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
        <p class="text-gray-600">Không thể tải sản phẩm mới. Vui lòng thử lại.</p>
      </div>
      
      <!-- Empty State -->
      <div v-else-if="newestProducts.length === 0" class="bg-gray-50 border border-gray-200 rounded-lg p-12 text-center">
        <p class="text-gray-600">Hiện tại không có sản phẩm mới.</p>
      </div>
      
      <!-- Newest Products Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <article
          v-for="item in newestProducts"
          :key="item.id"
          class="group bg-white rounded-lg border border-gray-100 overflow-hidden hover:border-gray-300 transition-all duration-300"
        >
           <div class="aspect-square bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-gray-100 transition duration-300 overflow-hidden">
             <img v-if="item.thumbnail_url" :src="item.thumbnail_url" :alt="item.name" class="w-full h-full object-contain">
             <span v-else>Product Image</span>
           </div>
           
           <div class="p-8 space-y-4">
             <h4 class="text-sm font-medium text-gray-900 line-clamp-2">
               {{ item.name }}
             </h4>
            
            <div class="space-y-2">
              <p class="text-lg font-semibold text-gray-900">{{ formatPrice(item.price) }}</p>
            </div>
            
            <button class="w-full mt-6 bg-black text-white py-3 rounded-lg font-medium hover:bg-gray-900 transition-colors duration-200">
              Thêm vào giỏ
            </button>
          </div>
        </article>
      </div>
    </section>

    <!-- ===== FLASH SALE SECTION ===== -->
    <section class="max-w-6xl mx-auto px-8 py-32 space-y-16">
      <div class="space-y-6">
        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-widest">⚡ Flash Sale</h3>
        <div class="w-16 h-px bg-black"></div>
      </div>
      
      <!-- Loading State -->
      <div v-if="loading.products" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
        <div v-for="i in 5" :key="i" class="bg-white rounded-lg border border-gray-100 overflow-hidden">
          <div class="h-48 bg-gray-100 animate-pulse"></div>
          <div class="p-8 space-y-4">
            <div class="h-4 bg-gray-100 rounded animate-pulse w-1/3"></div>
            <div class="h-4 bg-gray-100 rounded animate-pulse w-2/3"></div>
            <div class="h-6 bg-gray-100 rounded animate-pulse w-1/2"></div>
            <div class="h-10 bg-gray-100 rounded animate-pulse"></div>
          </div>
        </div>
      </div>
      
      <!-- Error State -->
      <div v-else-if="error.products" class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
        <p class="text-gray-600">Không thể tải sản phẩm Flash Sale. Vui lòng thử lại.</p>
      </div>
      
      <!-- Empty State -->
      <div v-else-if="flashSale.length === 0" class="bg-gray-50 border border-gray-200 rounded-lg p-12 text-center">
        <p class="text-gray-600">Hiện tại không có sản phẩm Flash Sale.</p>
      </div>
      
      <!-- Flash Sale Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
        <article
          v-for="item in flashSale"
          :key="item.id"
          class="group bg-white rounded-lg border border-gray-100 overflow-hidden hover:border-gray-300 transition-all duration-300"
        >
           <div class="aspect-square bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-gray-100 transition duration-300 overflow-hidden">
             <img v-if="item.thumb" :src="item.thumb" :alt="item.name" class="w-full h-full object-contain">
             <span v-else>Product Image</span>
           </div>
           
           <div class="p-8 space-y-4">
             <div class="flex items-center justify-between">
              <span class="bg-black text-white text-xs font-bold px-3 py-1.5 rounded">
                -{{ discount(item) }}%
              </span>
            </div>
            
            <h4 class="text-sm font-medium text-gray-900 line-clamp-2">
              {{ item.name }}
            </h4>
            
            <div class="space-y-2">
              <p class="text-lg font-semibold text-gray-900">{{ formatPrice(item.price) }}</p>
              <p class="text-xs text-gray-500 line-through">{{ formatPrice(item.oldPrice) }}</p>
            </div>
            
            <button class="w-full mt-6 bg-black text-white py-3 rounded-lg font-medium hover:bg-gray-900 transition-colors duration-200">
              Thêm vào giỏ
            </button>
          </div>
        </article>
      </div>
    </section>

    <!-- ===== FOOTER - Minimal ===== -->
    <footer class="bg-white border-t border-gray-100 py-16">
      <div class="max-w-6xl mx-auto px-8 text-center">
        <p class="text-sm text-gray-600">© 2026 TechGear. All rights reserved.</p>
      </div>
    </footer>

    <!-- ===== MODAL - Minimal Design ===== -->
    <transition name="fade">
      <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-12 space-y-8 animate-slideUp">
          <div class="text-center">
            <div class="text-4xl mb-6">✨</div>
            <h3 class="text-2xl font-semibold text-gray-900">Welcome!</h3>
          </div>
          
          <p class="text-gray-600 text-center leading-relaxed text-sm">
            {{ modalMessage }}
          </p>
          
          <div class="flex gap-3 pt-4">
            <button
              @click="closeModal"
              class="flex-1 px-4 py-3 bg-gray-100 text-gray-900 font-medium rounded-lg hover:bg-gray-200 transition duration-200"
            >
              Đóng
            </button>
            <button
              @click="closeModal"
              class="flex-1 px-4 py-3 bg-black text-white font-medium rounded-lg hover:bg-gray-900 transition duration-200"
            >
              Bắt đầu
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
/* Ripple Effect Animation */
.ripple {
  position: absolute;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.15);
  transform: scale(0);
  animation: ripple-animation 0.6s ease-out;
  pointer-events: none;
}

@keyframes ripple-animation {
  to {
    transform: scale(4);
    opacity: 0;
  }
}

/* Fade Transition for Modal */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Slide Up Animation for Modal */
.animate-slideUp {
  animation: slideUp 0.4s ease-out;
}

@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
  }
}

/* Font System */
.font-system {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Line Clamp */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
