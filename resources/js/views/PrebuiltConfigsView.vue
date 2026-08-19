<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cartStore'

const router = useRouter()
const cartStore = useCartStore()
const configs = ref([])
const filteredConfigs = ref([])
const searchQuery = ref('')
const loading = ref(false)
const showCart = ref(false)

const totalCount = computed(() => filteredConfigs.value.length)
const cartCount = computed(() => cartStore.cartCount)
const cartTotal = computed(() => cartStore.cartTotal)

async function fetchConfigs() {
  loading.value = true
  try {
    const response = await fetch('/api/prebuilt-configs')
    const result = await response.json()
    if (result.status === 'success') {
      configs.value = (result.data || []).map((config) => ({
        ...config,
        price: parseFloat(config.price),
      }))
      filteredConfigs.value = configs.value
    }
  } catch (err) {
    console.error('Error fetching prebuilt configs:', err)
  } finally {
    loading.value = false
  }
}

function updateSearch() {
  const query = searchQuery.value.toLowerCase()
  filteredConfigs.value = configs.value.filter((item) =>
    item.name.toLowerCase().includes(query) ||
    (item.description || '').toLowerCase().includes(query)
  )
}

function formatPrice(price) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
}

function goBrowseNewest() {
  router.push('/browse')
}

function openDetail(config) {
  router.push(`/prebuilt-config/${config.id}`)
}

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

onMounted(fetchConfigs)
</script>

<template>
  <div class="min-h-screen bg-[var(--bg-body)] font-body transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
      <div class="mb-6 sm:mb-8">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-widest">Cấu hình xây sẵn</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ totalCount }} cấu hình</p>
      </div>

      <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <div v-for="i in 6" :key="i" class="animate-pulse rounded-2xl border border-gray-200 dark:border-slate-700 p-4">
          <div class="aspect-[4/3] bg-gray-200 dark:bg-slate-700 rounded-xl mb-4"></div>
          <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded mb-3"></div>
          <div class="h-4 bg-gray-200 dark:bg-slate-700 rounded w-2/3"></div>
        </div>
      </div>

      <div v-else-if="filteredConfigs.length === 0" class="bg-white dark:bg-slate-800 rounded-2xl p-8 sm:p-12 text-center border border-gray-200 dark:border-slate-700 shadow-sm">
        <div class="text-5xl mb-4 opacity-40">📦</div>
        <p class="text-gray-600 dark:text-gray-300 mb-4">Không tìm thấy cấu hình phù hợp</p>
        <router-link
          to="/browse"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors shadow-lg shadow-gray-900/20 dark:shadow-white/20"
        >
          Khám phá danh mục khác
        </router-link>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <div v-for="config in filteredConfigs" :key="config.id" class="group bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 overflow-hidden hover:border-cyan-400 dark:hover:border-cyan-500 transition-all duration-300 hover:shadow-xl hover:shadow-cyan-500/10 flex flex-col">
          <div class="aspect-[4/3] bg-gray-50 dark:bg-slate-700/50 flex items-center justify-center overflow-hidden">
            <img v-if="config.thumbnail_url" :src="config.thumbnail_url" :alt="config.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
            <div v-else class="text-5xl text-gray-300 dark:text-slate-600">🧩</div>
          </div>

          <div class="p-4 sm:p-5 space-y-3 sm:space-y-4 flex flex-col flex-1">
            <div>
              <p class="text-xs uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1 sm:mb-2">
                {{ config.is_featured ? 'Nổi bật' : 'Xây sẵn' }}
              </p>
              <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white leading-tight line-clamp-2">
                {{ config.name }}
              </h3>
            </div>

            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 line-clamp-2">{{ config.description }}</p>

            <div class="flex items-center justify-between gap-3 sm:gap-4 pt-2 mt-auto">
              <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-widest">Giá</p>
                <p class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white truncate">{{ formatPrice(config.price) }}</p>
              </div>

              <button @click="openDetail(config)" class="inline-flex items-center justify-center px-3 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-medium transition bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100">
                Xem chi tiết
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.font-system {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
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
