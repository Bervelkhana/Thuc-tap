<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const configs = ref([])
const filteredConfigs = ref([])
const searchQuery = ref('')
const loading = ref(false)

const totalCount = computed(() => filteredConfigs.value.length)

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

onMounted(fetchConfigs)
</script>

<template>
  <div class="min-h-screen bg-white font-system">
    <header class="sticky top-0 z-40 bg-white border-b border-gray-100 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
        <router-link to="/home" class="text-xl font-semibold text-gray-900 hover:text-gray-700 transition">
          ← TechGear
        </router-link>

        <div class="hidden md:flex items-center bg-gray-100 rounded-lg px-4 py-2 flex-1 max-w-xs ml-6">
          <input
            v-model="searchQuery"
            @input="updateSearch"
            type="text"
            placeholder="Tìm kiếm cấu hình..."
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
              <button @click="goBrowseNewest" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-left transition-all duration-200 border-2 bg-white text-gray-800 border-transparent hover:border-gray-200 hover:bg-gray-50">
                <span class="text-xl shrink-0">🆕</span>
                <span class="text-sm font-medium leading-tight flex-1">Sản phẩm mới</span>
              </button>
              <router-link to="/browse-prebuilt" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-left transition-all duration-200 border-2 bg-black text-white border-black hover:bg-gray-900">
                <span class="text-xl shrink-0">🧩</span>
                <span class="text-sm font-medium leading-tight flex-1">Cấu hình xây sẵn</span>
              </router-link>
              <a href="/pc-build" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-left transition-all duration-200 border-2 bg-white text-gray-800 border-transparent hover:border-gray-200 hover:bg-gray-50">
                <span class="text-xl shrink-0">🔧</span>
                <span class="text-sm font-medium leading-tight flex-1">Xây dựng cấu hình</span>
              </a>
            </div>
          </div>
        </aside>

        <main class="flex-1 order-1 lg:order-2 min-w-0">
          <div class="mb-6 sm:mb-8">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-widest">Cấu hình xây sẵn</h2>
            <p class="text-sm text-gray-500 mt-2">{{ totalCount }} cấu hình</p>
          </div>

          <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <div v-for="i in 6" :key="i" class="animate-pulse rounded-2xl border border-gray-200 p-4">
              <div class="aspect-[4/3] bg-gray-200 rounded-xl mb-4"></div>
              <div class="h-4 bg-gray-200 rounded mb-3"></div>
              <div class="h-4 bg-gray-200 rounded w-2/3"></div>
            </div>
          </div>

          <div v-else-if="filteredConfigs.length === 0" class="bg-gray-50 rounded-2xl p-8 sm:p-12 text-center border border-gray-200">
            <p class="text-gray-600">Không tìm thấy cấu hình phù hợp</p>
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <div v-for="config in filteredConfigs" :key="config.id" class="group bg-white rounded-2xl border border-gray-200 overflow-hidden hover:border-gray-400 transition-all duration-300 hover:shadow-lg flex flex-col">
              <div class="aspect-[4/3] bg-gray-50 flex items-center justify-center overflow-hidden">
                <img v-if="config.thumbnail_url" :src="config.thumbnail_url" :alt="config.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                <div v-else class="text-5xl text-gray-300">🧩</div>
              </div>

              <div class="p-4 sm:p-5 space-y-3 sm:space-y-4 flex flex-col flex-1">
                <div>
                  <p class="text-xs uppercase tracking-widest text-gray-400 mb-1 sm:mb-2">
                    {{ config.is_featured ? 'Nổi bật' : 'Xây sẵn' }}
                  </p>
                  <h3 class="text-base sm:text-lg font-semibold text-gray-900 leading-tight line-clamp-2">
                    {{ config.name }}
                  </h3>
                </div>

                <p class="text-xs sm:text-sm text-gray-500 line-clamp-2">{{ config.description }}</p>

                <div class="flex items-center justify-between gap-3 sm:gap-4 pt-2 mt-auto">
                  <div>
                    <p class="text-xs text-gray-400 uppercase tracking-widest">Giá</p>
                    <p class="text-base sm:text-lg font-semibold text-black">{{ formatPrice(config.price) }}</p>
                  </div>

                  <button @click="openDetail(config)" class="inline-flex items-center justify-center px-3 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-medium transition bg-black text-white hover:bg-gray-900">
                    Xem chi tiết
                  </button>
                </div>
              </div>
            </div>
          </div>
        </main>
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
</style>
