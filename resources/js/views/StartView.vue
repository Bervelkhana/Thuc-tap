<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const showModal = ref(false)

function createRipple(event) {
  const btn = event.currentTarget
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
}

function handleCTAClick(event) {
  createRipple(event)
  setTimeout(() => {
    showModal.value = true
  }, 100)
}

function closeModal() {
  showModal.value = false
}

function navigateToHome() {
  closeModal()
  router.push('/home')
}

function navigateToPCBuilder() {
  router.push('/pc-builder')
}
</script>

<template>
  <div class="min-h-screen bg-white font-system">
    <!-- HEADER -->
    <header class="sticky top-0 z-50 bg-white border-b border-gray-100">
      <div class="max-w-6xl mx-auto px-8 py-6 flex items-center justify-between">
        <a href="#" class="text-xl font-semibold text-gray-900 tracking-tight">TechGear</a>
        
        <nav class="flex items-center gap-12">
          <a href="#" class="text-sm text-gray-600 hover:text-gray-900 transition duration-200">Catalog</a>
          <a href="#" class="text-sm text-gray-600 hover:text-gray-900 transition duration-200">About</a>
          <button class="relative group cursor-pointer">
            <span class="text-sm text-gray-600 group-hover:text-gray-900 transition duration-200">Cart</span>
            <span class="absolute -top-2 -right-4 bg-black text-white text-xs font-medium px-2 py-1 rounded-full">0</span>
          </button>
        </nav>
      </div>
    </header>

    <!-- HERO SECTION -->
    <main class="relative flex items-center justify-center bg-white overflow-hidden py-32">
      <div class="relative text-center max-w-3xl mx-auto px-8 space-y-12">
        <!-- Tagline -->
        <p class="text-sm font-medium text-gray-600 uppercase tracking-widest">Build Your Dream PC</p>
        
        <!-- Main Heading -->
        <h1 class="text-6xl md:text-7xl font-light text-gray-900 leading-tight tracking-tight">
          Linh kiện PC chất lượng
        </h1>
        
        <!-- Subheading -->
        <p class="text-lg text-gray-600 leading-relaxed max-w-2xl mx-auto font-light">
          Khám phá bộ sưu tập linh kiện máy tính premium với giá cạnh tranh. Tư vấn cấu hình thông minh bằng AI.
        </p>
        
        <!-- Whitespace -->
        <div class="pt-8"></div>
        
        <!-- CTA Buttons -->
        <div class="flex flex-col items-center gap-6">
          <button 
            @click="handleCTAClick"
            class="relative inline-flex items-center justify-center px-10 py-4 bg-black text-white font-medium rounded-lg overflow-hidden group hover:bg-gray-900 transition-all duration-300 shadow-sm hover:shadow-xl"
          >
            <span class="relative z-10">Khám phá ngay</span>
          </button>
          
          <!-- Secondary CTA -->
          <button
            @click="navigateToPCBuilder"
            class="text-sm text-gray-600 hover:text-gray-900 font-medium transition duration-200"
          >
            Xem AI PC Builder →
          </button>
        </div>
      </div>
    </main>

    <!-- MODAL -->
    <transition name="fade">
      <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-12 space-y-8 animate-slideUp">
          <div class="text-center">
            <div class="text-4xl mb-6">✨</div>
            <h3 class="text-2xl font-semibold text-gray-900">Welcome to TechGear!</h3>
          </div>
          
          <p class="text-gray-600 text-center leading-relaxed text-sm">
            Chào mừng bạn! Hãy khám phá các linh kiện máy tính chất lượng cao của chúng tôi 
            với giá cạnh tranh nhất thị trường.
          </p>
          
          <div class="flex gap-3 pt-4 flex-col">
            <button
              @click="closeModal"
              class="px-4 py-3 bg-gray-100 text-gray-900 font-medium rounded-lg hover:bg-gray-200 transition duration-200"
            >
              Đóng
            </button>
            <button
              @click="navigateToHome"
              class="px-4 py-3 bg-black text-white font-medium rounded-lg hover:bg-gray-900 transition duration-200"
            >
              Bắt đầu khám phá
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-gray-100 py-16">
      <div class="max-w-6xl mx-auto px-8 text-center">
        <p class="text-sm text-gray-600">© 2026 TechGear. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.font-system {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

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
</style>

