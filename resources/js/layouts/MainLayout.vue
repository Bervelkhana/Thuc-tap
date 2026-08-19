<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCartStore } from '../stores/cartStore'
import { useDarkMode } from '../composables/useDarkMode'

defineOptions({
  name: 'MainLayout',
})

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()
const { isDark, toggle: toggleDarkMode } = useDarkMode()

const props = defineProps({
  showSidebar: {
    type: Boolean,
    default: true,
  },
})

const isMobileMenuOpen = ref(false)
const searchQuery = ref('')
const categories = ref([])
const categoriesLoading = ref(false)

const cartCount = computed(() => cartStore.cartCount)

const activeCategorySlug = computed(() => route.params.slug)

const iconMap = {
  'CPU': '🧠',
  'MAIN': '🔌',
  'RAM': '💾',
  'VGA': '🎮',
  'SSD': '💿',
  'PSU': '⚡',
  'CASE': '📦',
  'COOLER': '❄️',
}

const slugMap = {
  'CPU': 'cpu',
  'MAIN': 'main',
  'RAM': 'ram',
  'VGA': 'vga',
  'SSD': 'ssd',
  'PSU': 'psu',
  'CASE': 'case',
  'COOLER': 'cooler',
}

const categoryOrder = ['CPU', 'MAIN', 'RAM', 'VGA', 'SSD', 'PSU', 'COOLER', 'CASE']

const orderedCategories = computed(() => {
  return [...categories.value].sort((a, b) => {
    const orderA = categoryOrder.indexOf(a.name)
    const orderB = categoryOrder.indexOf(b.name)
    return (orderA === -1 ? 99 : orderA) - (orderB === -1 ? 99 : orderB)
  })
})

async function fetchCategories() {
  categoriesLoading.value = true
  try {
    const response = await fetch('/api/categories')
    const result = await response.json()
    if (result.status === 'success') {
      categories.value = result.data.map(cat => ({
        ...cat,
        slug: cat.slug || slugMap[cat.name] || cat.name.toLowerCase().replace(/\s+/g, '-'),
        icon: iconMap[cat.name] || '🔧',
      }))
    }
  } catch (err) {
    console.error('Error fetching categories:', err)
  } finally {
    categoriesLoading.value = false
  }
}

function navigateTo(path) {
  router.push(path)
  isMobileMenuOpen.value = false
}

function navigateToCategory(slug) {
  router.push(`/browser-${slug}`)
  isMobileMenuOpen.value = false
}

function handleSearch() {
  const query = searchQuery.value.trim()
  if (query) {
    router.push({ name: 'product-browser', query: { search: query } })
    searchQuery.value = ''
  }
}

function toggleMobileMenu() {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

watch(() => route.path, () => {
  isMobileMenuOpen.value = false
})

onMounted(() => {
  fetchCategories()
})
</script>

<template>
  <div class="min-h-screen transition-colors duration-300" :class="isDark ? 'dark' : ''">
    <div class="min-h-screen bg-[var(--bg-body)] text-[var(--text-primary)] font-body pt-16 lg:pt-16">

      <!-- ===== HEADER ===== -->
      <header class="sticky top-0 z-[9999] h-16 lg:h-16 backdrop-blur-xl bg-white/95 dark:bg-slate-900/95 border-b border-gray-200 dark:border-slate-700 shadow-sm transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex items-center justify-between h-16 lg:h-18">

            <!-- Left: Logo + Mobile Menu -->
            <div class="flex items-center gap-3">
              <button
                v-if="showSidebar"
                @click="toggleMobileMenu"
                class="lg:hidden p-2 -ml-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors"
                aria-label="Toggle menu"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
              </button>

              <router-link
                to="/browse"
                class="flex items-center gap-2 group"
              >
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-cyan-500/30 group-hover:shadow-cyan-500/50 transition-shadow">
                  TG
                </div>
                <span class="text-xl font-display font-bold text-gray-900 dark:text-white tracking-tight group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                  TechGear
                </span>
              </router-link>
            </div>

            <!-- Center: Search Bar (Desktop) -->
            <div class="hidden md:flex flex-1 max-w-xl mx-8">
              <div class="relative w-full">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                <input
                  v-model="searchQuery"
                  @keyup.enter="handleSearch"
                  type="text"
                  placeholder="Tìm kiếm linh kiện, CPU, VGA..."
                  class="block w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-transparent transition-all duration-200"
                />
              </div>
            </div>

            <!-- Right: Actions -->
            <div class="flex items-center gap-2 sm:gap-3">
              <!-- Cart -->
              <router-link
                to="/checkout-new"
                class="relative p-2 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors"
                aria-label="Shopping cart"
              >
                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span
                  v-if="cartCount > 0"
                  class="absolute -top-0.5 -right-0.5 sm:-top-1 sm:-right-1 bg-gradient-to-r from-orange-500 to-red-500 text-white text-[10px] sm:text-xs font-bold px-1.5 py-0.5 sm:px-2 sm:py-1 rounded-full shadow-lg shadow-orange-500/30"
                >
                  {{ cartCount }}
                </span>
              </router-link>

              <!-- Dark Mode Toggle -->
              <button
                @click="toggleDarkMode"
                class="p-2 rounded-xl text-gray-600 dark:text-yellow-400 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors"
                aria-label="Toggle dark mode"
              >
                <!-- Sun icon (shown in dark mode) -->
                <svg v-if="isDark" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <!-- Moon icon (shown in light mode) -->
                <svg v-else class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Mobile Search (shown below header on mobile) -->
          <div class="md:hidden pb-3">
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                v-model="searchQuery"
                @keyup.enter="handleSearch"
                type="text"
                placeholder="Tìm kiếm sản phẩm..."
                class="block w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-transparent transition-all duration-200"
              />
            </div>
          </div>
        </div>
      </header>

      <!-- ===== MOBILE OVERLAY ===== -->
      <Transition name="fade">
        <div
          v-if="isMobileMenuOpen && showSidebar"
          class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 lg:hidden"
          @click="toggleMobileMenu"
        />
      </Transition>

      <!-- ===== SIDEBAR + CONTENT WRAPPER ===== -->
      <div v-if="showSidebar" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-6 lg:gap-8 py-6 lg:py-8">

          <!-- SIDEBAR -->
          <Transition name="slide">
            <aside
              v-if="showSidebar"
              :class="[
                'fixed inset-y-0 left-0 z-40 w-72 bg-white dark:bg-slate-900 border-r border-gray-200 dark:border-slate-700 shadow-2xl transform transition-transform duration-300 lg:static lg:translate-x-0 lg:w-64 xl:w-72 lg:shrink-0 lg:block lg:shadow-none lg:border-none lg:bg-transparent lg:dark:bg-transparent',
                isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
              ]"
            >
              <!-- Mobile close button -->
              <div class="flex items-center justify-between p-4 lg:hidden border-b border-gray-200 dark:border-slate-700">
                <span class="font-display font-bold text-lg text-gray-900 dark:text-white">Menu</span>
                <button
                  @click="toggleMobileMenu"
                  class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors"
                >
                  <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <div class="p-4 space-y-6 overflow-y-auto h-full">
                <!-- Quick Links -->
                <div class="space-y-1">
                  <p class="px-3 text-[11px] font-semibold text-gray-500 dark:text-gray-500 uppercase tracking-widest mb-2">Menu</p>
                  <nav class="space-y-1">
                    <router-link
                      to="/browse"
                      class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-all duration-200"
                    >
                      <span class="text-base">🆕</span>
                      Sản phẩm mới
                    </router-link>
                    <router-link
                      to="/browse-prebuilt"
                      class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-all duration-200"
                    >
                      <span class="text-base">🧩</span>
                      Cấu hình xây sẵn
                    </router-link>
                    <router-link
                      to="/pc-builder"
                      class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-all duration-200"
                    >
                      <span class="text-base">🔧</span>
                      Xây dựng cấu hình
                    </router-link>
                    <a
                      href="/ai-build"
                      class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-all duration-200"
                    >
                      <span class="text-base">🤖</span>
                      Xây dựng bằng AI
                    </a>
                  </nav>
                </div>

                <!-- Categories -->
                <div class="space-y-1">
                  <p class="px-3 text-[11px] font-semibold text-gray-500 dark:text-gray-500 uppercase tracking-widest mb-2">Danh mục</p>
                  <nav class="space-y-1">
                    <router-link
                      v-for="category in orderedCategories"
                      :key="category.id"
                      :to="`/browser-${category.slug}`"
                      :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 border-l-4',
                        activeCategorySlug === category.slug
                          ? 'bg-gradient-to-r from-cyan-50 to-purple-50 dark:from-cyan-900/20 dark:to-purple-900/20 border-cyan-400 text-cyan-700 dark:text-cyan-300'
                          : 'border-transparent text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 hover:border-gray-300 dark:hover:border-slate-600'
                      ]"
                    >
                      <span class="text-base">{{ category.icon }}</span>
                      <span class="flex-1">{{ category.name }}</span>
                    </router-link>
                  </nav>
                </div>
              </div>
            </aside>
          </Transition>

          <!-- MAIN CONTENT -->
          <main class="flex-1 min-w-0">
            <slot />
          </main>
        </div>
      </div>

      <!-- ===== CONTENT WITHOUT SIDEBAR ===== -->
      <div v-else class="min-h-screen">
        <slot />
      </div>

    </div>
  </div>
</template>

<style scoped>
/* Fade transition for overlay */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Slide transition for sidebar */
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(-100%);
}

/* Custom scrollbar for sidebar */
:deep(.overflow-y-auto) {
  scrollbar-width: thin;
  scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
}

:deep(.overflow-y-auto::-webkit-scrollbar) {
  width: 4px;
}

:deep(.overflow-y-auto::-webkit-scrollbar-track) {
  background: transparent;
}

:deep(.overflow-y-auto::-webkit-scrollbar-thumb) {
  background-color: rgba(0, 0, 0, 0.2);
  border-radius: 4px;
}

.dark :deep(.overflow-y-auto::-webkit-scrollbar-thumb) {
  background-color: rgba(255, 255, 255, 0.2);
}

/* Line clamp utility */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>







