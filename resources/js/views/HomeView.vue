<script setup>
import { ref } from 'vue'

const categories = ref([
  { id: 1, name: 'CPU', icon: '🧠' },
  { id: 2, name: 'RAM', icon: '📊' },
  { id: 3, name: 'Mainboard', icon: '🔲' },
  { id: 4, name: 'VGA', icon: '🎮' },
  { id: 5, name: 'SSD', icon: '💾' },
  { id: 6, name: 'PSU', icon: '⚡' },
  { id: 7, name: 'Case', icon: '🖥️' },
])

const flashSale = ref([
  { id: 1, name: 'Intel Core i5-13400F', price: 3290000, oldPrice: 3690000, thumb: '' },
  { id: 2, name: 'NVIDIA RTX 4060 8GB', price: 7290000, oldPrice: 7990000, thumb: '' },
  { id: 3, name: 'Corsair Vengeance DDR5 16GB', price: 1290000, oldPrice: 1490000, thumb: '' },
  { id: 4, name: 'Samsung 980 PRO 1TB', price: 1990000, oldPrice: 2290000, thumb: '' },
  { id: 5, name: 'ASUS ROG STRIX B760-A', price: 5490000, oldPrice: 5990000, thumb: '' },
])

const showModal = ref(false)
const modalMessage = ref('')

function formatPrice(v) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v)
}

function discount(item) {
  return Math.round((1 - item.price / item.oldPrice) * 100)
}

// Xử lý click nút CTA chính
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
  
  setTimeout(() => ripple.remove(), 600)
  
  // Hiển thị modal
  modalMessage.value = '✨ Chào mừng bạn! Hãy khám phá các linh kiện máy tính chất lượng cao của chúng tôi.'
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}
</script>

<template>
  <div class="min-h-screen bg-white font-system">
    <!-- ===== HEADER - Minimalist ===== -->
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">TechGear</h1>
        
        <nav class="flex items-center gap-8">
          <button class="text-gray-700 hover:text-gray-900 transition">Catalog</button>
          <button class="text-gray-700 hover:text-gray-900 transition">About</button>
          <button class="relative group cursor-pointer">
            <span class="text-gray-700 group-hover:text-gray-900 transition">Cart</span>
            <span class="absolute -top-2 -right-3 bg-gray-900 text-white text-xs px-1.5 py-0.5 rounded-full">0</span>
          </button>
        </nav>
      </div>
    </header>

    <!-- ===== HERO - Minimalist with Whitespace ===== -->
    <section class="relative h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 overflow-hidden">
      <div class="absolute inset-0 opacity-5">
        <div class="absolute top-10 right-20 w-96 h-96 bg-gray-400 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 left-20 w-80 h-80 bg-gray-300 rounded-full blur-3xl"></div>
      </div>
      
      <div class="relative text-center max-w-2xl mx-auto px-6 space-y-8">
        <!-- Tagline -->
        <p class="text-sm font-medium text-gray-600 uppercase tracking-widest">Build Your Dream PC</p>
        
        <!-- Main Heading -->
        <h2 class="text-5xl md:text-6xl lg:text-7xl font-bold text-gray-900 leading-tight">
          Linh kiện PC chất lượng
        </h2>
        
        <!-- Subheading -->
        <p class="text-xl text-gray-600 leading-relaxed max-w-xl mx-auto">
          Khám phá bộ sưu tập linh kiện máy tính premium với giá cạnh tranh. Tư vấn cấu hình thông minh bằng AI.
        </p>
        
        <!-- Whitespace -->
        <div class="pt-8"></div>
        
        <!-- CTA Button with Ripple Effect -->
        <button 
          @click="handleCTAClick"
          class="relative inline-flex items-center justify-center px-8 py-4 bg-gray-900 text-white font-semibold rounded-lg overflow-hidden group hover:shadow-2xl transition-all duration-300 hover:scale-105"
        >
          <span class="relative z-10">Khám phá ngay</span>
          <span class="absolute inset-0 bg-gradient-to-r from-gray-800 to-gray-900 opacity-0 group-hover:opacity-100 transition-opacity"></span>
        </button>
        
        <!-- Secondary CTA -->
        <div class="pt-4">
          <button class="text-gray-700 hover:text-gray-900 underline font-medium transition">
            Xem AI PC Builder →
          </button>
        </div>
      </div>
    </section>

    <!-- ===== CATEGORIES SECTION ===== -->
    <section class="max-w-7xl mx-auto px-6 py-24 space-y-12">
      <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 uppercase tracking-wide">Danh mục</h3>
        <div class="w-12 h-1 bg-gray-900"></div>
      </div>
      
      <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-6">
        <button
          v-for="c in categories"
          :key="c.id"
          class="group flex flex-col items-center gap-4 p-6 bg-gray-50 rounded-xl hover:bg-gray-900 hover:text-white transition-all duration-300 border border-gray-200 hover:border-gray-900"
        >
          <span class="text-4xl group-hover:scale-110 transition-transform">{{ c.icon }}</span>
          <span class="text-sm font-medium text-gray-700 group-hover:text-white text-center">{{ c.name }}</span>
        </button>
      </div>
    </section>

    <!-- ===== FLASH SALE SECTION ===== -->
    <section class="max-w-7xl mx-auto px-6 py-24 space-y-12 bg-gray-50 rounded-2xl">
      <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 uppercase tracking-wide">⚡ Flash Sale</h3>
        <div class="w-12 h-1 bg-red-600"></div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
        <article
          v-for="item in flashSale"
          :key="item.id"
          class="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300"
        >
          <div class="h-40 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-gray-400 group-hover:from-gray-200 group-hover:to-gray-300 transition">
            Product Image
          </div>
          
          <div class="p-6 space-y-3">
            <div class="flex items-center justify-between">
              <span class="bg-red-100 text-red-700 text-xs font-bold px-2.5 py-1 rounded-full">
                -{{ discount(item) }}%
              </span>
            </div>
            
            <h4 class="text-sm font-semibold text-gray-900 line-clamp-2 group-hover:text-gray-700">
              {{ item.name }}
            </h4>
            
            <div class="space-y-1">
              <p class="text-lg font-bold text-gray-900">{{ formatPrice(item.price) }}</p>
              <p class="text-xs text-gray-500 line-through">{{ formatPrice(item.oldPrice) }}</p>
            </div>
            
            <button class="w-full mt-4 bg-gray-900 text-white py-2.5 rounded-lg font-medium hover:bg-gray-800 transition-colors">
              Add to Cart
            </button>
          </div>
        </article>
      </div>
    </section>

    <!-- ===== FOOTER - Minimal ===== -->
    <footer class="bg-gray-900 text-gray-400 py-12">
      <div class="max-w-7xl mx-auto px-6 text-center">
        <p class="text-sm">© 2026 TechGear. All rights reserved.</p>
      </div>
    </footer>

    <!-- ===== MODAL - Minimal Design ===== -->
    <transition name="fade">
      <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 space-y-6 animate-slideUp">
          <div class="text-center">
            <div class="text-5xl mb-4">✨</div>
            <h3 class="text-2xl font-bold text-gray-900">Welcome!</h3>
          </div>
          
          <p class="text-gray-600 text-center leading-relaxed">
            {{ modalMessage }}
          </p>
          
          <div class="flex gap-4">
            <button
              @click="closeModal"
              class="flex-1 px-4 py-3 bg-gray-100 text-gray-900 font-semibold rounded-lg hover:bg-gray-200 transition"
            >
              Đóng
            </button>
            <button
              @click="closeModal"
              class="flex-1 px-4 py-3 bg-gray-900 text-white font-semibold rounded-lg hover:bg-gray-800 transition"
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
  background: rgba(255, 255, 255, 0.6);
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
    opacity: 1;
  }
}

/* Font System */
.font-system {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
}
</style>
