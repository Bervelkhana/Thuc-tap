<script setup>
import { computed, onMounted, ref } from 'vue'
import { useCartStore } from '../stores/cartStore'

const cart = useCartStore()

const loading = ref(false)
const error = ref(null)
const successMessage = ref(null)
const validationErrors = ref([])
const productsLoading = ref(false)

const componentTypes = [
  { key: 'cpu', label: 'CPU', required: true },
  { key: 'mainboard', label: 'Mainboard', required: true },
  { key: 'ram', label: 'RAM', required: true },
  { key: 'vga', label: 'VGA', required: true },
  { key: 'ssd', label: 'SSD', required: true },
  { key: 'psu', label: 'PSU', required: true },
  { key: 'case', label: 'Case', required: true },
]

const selectedParts = ref({
  cpu: null,
  mainboard: null,
  ram: null,
  vga: null,
  ssd: null,
  psu: null,
  case: null,
})

const products = ref({
  cpu: [],
  mainboard: [],
  ram: [],
  vga: [],
  ssd: [],
  psu: [],
  case: [],
})

const modalOpen = ref(false)
const modalCategory = ref(null)
const modalSearch = ref('')

const visibleModalProducts = computed(() => {
  const list = products.value[modalCategory.value] || []
  const query = modalSearch.value.trim().toLowerCase()
  if (!query) return list
  return list.filter((p) => p.name.toLowerCase().includes(query) || String(p.sku || '').toLowerCase().includes(query))
})

function formatPrice(price) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(price || 0))
}

function partDisplay(partType) {
  return selectedParts.value[partType]
}

function openModal(categoryKey) {
  modalCategory.value = categoryKey
  modalSearch.value = ''
  modalOpen.value = true

  if (!products.value[categoryKey] || products.value[categoryKey].length === 0) {
    fetchComponentsForCategory(categoryKey)
  }
}

function closeModal() {
  modalOpen.value = false
  modalCategory.value = null
  modalSearch.value = ''
}

function selectPart(product) {
  if (!modalCategory.value) return
  selectedParts.value[modalCategory.value] = product
  closeModal()
}

function removePart(categoryKey) {
  selectedParts.value[categoryKey] = null
}

const totalPrice = computed(() => {
  return componentTypes.reduce((sum, type) => sum + Number(selectedParts.value[type.key]?.price || 0), 0)
})

function buildPayload() {
  return {
    cpu_id: selectedParts.value.cpu?.id,
    mainboard_id: selectedParts.value.mainboard?.id,
    ram_ids: selectedParts.value.ram?.id ? [selectedParts.value.ram.id] : [],
    vga_id: selectedParts.value.vga?.id,
    ssd_id: selectedParts.value.ssd?.id,
    psu_id: selectedParts.value.psu?.id,
    case_id: selectedParts.value.case?.id,
  }
}

async function validateConfig() {
  validationErrors.value = []
  error.value = null

  try {
    const response = await fetch('/api/pc-builder/validate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(buildPayload()),
    })

    const result = await response.json()
    if (result.status === 'error') {
      validationErrors.value = result.errors || [result.message]
      return false
    }

    successMessage.value = 'Cấu hình PC hợp lệ! ✓'
    setTimeout(() => {
      successMessage.value = null
    }, 2500)
    return true
  } catch (err) {
    error.value = 'Lỗi khi kiểm tra cấu hình'
    console.error(err)
    return false
  }
}

async function getRecommendation() {
  loading.value = true
  error.value = null

  try {
    const response = await fetch('/api/pc-builder/recommend', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ budget: 10000000 }),
    })

    const result = await response.json()
    if (result.status === 'success' && result.data) {
      selectedParts.value = {
        cpu: result.data.cpu || null,
        mainboard: result.data.mainboard || null,
        ram: result.data.ram || null,
        vga: result.data.vga || null,
        ssd: result.data.ssd || null,
        psu: result.data.psu || null,
        case: result.data.case || null,
      }
      successMessage.value = 'Đã tải cấu hình được đề xuất'
      setTimeout(() => {
        successMessage.value = null
      }, 2500)
    }
  } catch (err) {
    error.value = 'Không thể lấy đề xuất'
    console.error(err)
  } finally {
    loading.value = false
  }
}

function addAllToCart() {
  componentTypes.forEach(({ key }) => {
    const product = selectedParts.value[key]
    if (product) {
      cart.addToCart(product, 1)
    }
  })

  successMessage.value = 'Đã thêm toàn bộ cấu hình vào giỏ hàng'
  setTimeout(() => {
    successMessage.value = null
  }, 2500)
}

function handleSelectFromModal(product) {
  selectPart(product)
}

async function fetchComponentsForCategory(categoryKey) {
  productsLoading.value = true
  try {
    const response = await fetch(`/api/pc-builder/components?category=${categoryKey}`)
    const result = await response.json()
    if (result.status === 'success') {
      products.value[categoryKey] = result.data || []
    }
  } catch (err) {
    error.value = `Không thể tải danh sách ${categoryKey}`
    console.error(err)
  } finally {
    productsLoading.value = false
  }
}

async function fetchRecommendationData() {
  loading.value = true
  error.value = null

  try {
    const response = await fetch('/api/pc-builder/recommend', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ budget: 10000000 }),
    })

    const result = await response.json()
    if (result.status === 'success' && result.data) {
      selectedParts.value = {
        cpu: result.data.cpu || null,
        mainboard: result.data.mainboard || null,
        ram: result.data.ram || null,
        vga: result.data.vga || null,
        ssd: result.data.ssd || null,
        psu: result.data.psu || null,
        case: result.data.case || null,
      }
      successMessage.value = 'Đã tải cấu hình được đề xuất'
      setTimeout(() => {
        successMessage.value = null
      }, 2500)
    }
  } catch (err) {
    error.value = 'Không thể lấy đề xuất'
    console.error(err)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await Promise.all(componentTypes.map((type) => fetchComponentsForCategory(type.key)))
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 font-system">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 lg:py-12">
      <div class="mb-8">
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Xây dựng cấu hình</h1>
        <p class="text-gray-600 mt-2">Chọn từng linh kiện, kiểm tra tương thích và thêm cả bộ vào giỏ hàng.</p>
      </div>

      <div v-if="successMessage" class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-700">
        {{ successMessage }}
      </div>
      <div v-if="error" class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
        {{ error }}
      </div>
      <div v-if="validationErrors.length" class="mb-6 rounded-2xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-yellow-800">
        <p class="font-semibold mb-2">Cảnh báo cấu hình:</p>
        <ul class="list-disc pl-5 space-y-1">
          <li v-for="(err, idx) in validationErrors" :key="idx">{{ err }}</li>
        </ul>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-[1fr_360px] gap-6 items-start">
        <!-- Component rows -->
        <section class="space-y-4">
          <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4 bg-gray-50">
              <h2 class="text-sm font-semibold uppercase tracking-widest text-gray-900">Linh kiện bắt buộc</h2>
            </div>

            <div class="divide-y divide-gray-200">
              <div v-for="type in componentTypes" :key="type.key" class="p-4 sm:p-5">
                <div v-if="!partDisplay(type.key)" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                  <div>
                    <p class="text-xs uppercase tracking-widest text-gray-400">{{ type.label }}</p>
                    <h3 class="text-lg font-semibold text-gray-900 mt-1">Chưa chọn linh kiện</h3>
                  </div>
                  <button
                    @click="openModal(type.key)"
                    class="inline-flex items-center justify-center rounded-xl bg-black px-4 py-2.5 text-sm font-medium text-white hover:bg-gray-900 transition"
                  >
                    Chọn linh kiện
                  </button>
                </div>

                <div v-else class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                  <div class="flex items-center gap-4 min-w-0">
                    <div class="h-16 w-16 rounded-xl bg-gray-100 flex items-center justify-center overflow-hidden shrink-0">
                      <img
                        v-if="partDisplay(type.key).thumbnail_url"
                        :src="partDisplay(type.key).thumbnail_url"
                        :alt="partDisplay(type.key).name"
                        class="h-full w-full object-cover"
                      />
                      <span v-else class="text-2xl text-gray-400">🧩</span>
                    </div>
                    <div class="min-w-0">
                      <p class="text-xs uppercase tracking-widest text-gray-400">{{ type.label }}</p>
                      <h3 class="truncate text-lg font-semibold text-gray-900">{{ partDisplay(type.key).name }}</h3>
                      <p class="text-sm text-gray-600">{{ formatPrice(partDisplay(type.key).price) }}</p>
                    </div>
                  </div>

                  <div class="flex gap-2">
                    <button
                      @click="openModal(type.key)"
                      class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                    >
                      Đổi
                    </button>
                    <button
                      @click="removePart(type.key)"
                      class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-100 transition"
                    >
                      Xóa
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal -->
          <transition name="fade">
            <div v-if="modalOpen" class="fixed inset-0 z-50 bg-black/50 p-4 flex items-center justify-center" @click.self="closeModal">
              <div class="w-full max-w-4xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="border-b border-gray-200 px-5 py-4 flex items-center justify-between bg-white sticky top-0 z-10">
                  <div>
                    <h3 class="text-lg font-semibold text-gray-900">Chọn linh kiện - {{ componentTypes.find(c => c.key === modalCategory)?.label }}</h3>
                    <p class="text-sm text-gray-500">Tìm và chọn sản phẩm phù hợp</p>
                  </div>
                  <button @click="closeModal" class="text-2xl text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <div class="p-4 border-b border-gray-200 bg-gray-50">
                  <div class="flex items-center bg-white rounded-xl border border-gray-300 px-4 py-3 focus-within:border-black">
                    <span class="text-gray-400 mr-3">🔍</span>
                    <input
                      v-model="modalSearch"
                      type="text"
                      class="w-full outline-none bg-transparent text-sm text-gray-700"
                      placeholder="Tìm kiếm linh kiện..."
                    />
                  </div>
                </div>

                <div class="p-4 overflow-y-auto">
                  <div v-if="visibleModalProducts.length === 0" class="py-16 text-center text-gray-500">
                    Không tìm thấy linh kiện phù hợp
                  </div>

                  <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                      v-for="product in visibleModalProducts"
                      :key="product.id"
                      class="rounded-2xl border border-gray-200 bg-white overflow-hidden hover:border-black transition-shadow hover:shadow-md flex flex-col"
                    >
                      <div class="aspect-[4/3] bg-gray-50 flex items-center justify-center overflow-hidden">
                        <img
                          v-if="product.thumbnail_url"
                          :src="product.thumbnail_url"
                          :alt="product.name"
                          class="w-full h-full object-cover"
                        />
                        <div v-else class="text-5xl text-gray-300">🛍️</div>
                      </div>

                      <div class="p-4 space-y-3 flex flex-col flex-1">
                        <div>
                          <h4 class="font-semibold text-gray-900 line-clamp-2">{{ product.name }}</h4>
                          <p class="text-sm text-gray-500">Tồn kho: {{ product.stock_quantity }}</p>
                        </div>

                        <div class="mt-auto flex items-center justify-between gap-3">
                          <p class="font-semibold text-gray-900">{{ formatPrice(product.price) }}</p>
                          <button
                            @click="handleSelectFromModal(product)"
                            :disabled="product.stock_quantity === 0"
                            class="rounded-xl px-4 py-2 text-sm font-medium transition"
                            :class="product.stock_quantity === 0 ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-black text-white hover:bg-gray-900'"
                          >
                            Chọn
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </transition>
        </section>

        <!-- Summary panel -->
        <aside class="xl:sticky xl:top-6">
          <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4 bg-gray-50">
              <h2 class="text-sm font-semibold uppercase tracking-widest text-gray-900">Tổng kết cấu hình</h2>
            </div>

            <div class="p-5 space-y-4">
              <div v-for="type in componentTypes" :key="type.key" class="flex items-center justify-between gap-3 text-sm">
                <span class="text-gray-600">{{ type.label }}</span>
                <span class="font-medium text-gray-900 truncate max-w-[180px] text-right">
                  {{ selectedParts[type.key] ? formatPrice(selectedParts[type.key].price) : '-' }}
                </span>
              </div>

              <div class="border-t border-gray-200 pt-4 flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-900">Tổng cộng chi phí</span>
                <span class="text-lg font-bold text-black">{{ formatPrice(totalPrice) }}</span>
              </div>

              <button
                @click="addAllToCart"
                :disabled="totalPrice === 0"
                class="w-full rounded-xl px-4 py-3 text-sm font-medium transition"
                :class="totalPrice === 0 ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-black text-white hover:bg-gray-900'"
              >
                Thêm tất cả vào giỏ hàng
              </button>

              <button
                @click="validateConfig"
                class="w-full rounded-xl px-4 py-3 text-sm font-medium transition bg-gray-100 text-gray-900 hover:bg-gray-200"
              >
                Kiểm tra tương thích
              </button>

              <button
                @click="getRecommendation"
                :disabled="loading"
                class="w-full rounded-xl px-4 py-3 text-sm font-medium transition bg-white text-gray-900 border border-gray-300 hover:bg-gray-50 disabled:opacity-50"
              >
                {{ loading ? 'Đang tải...' : 'Đề xuất từ AI' }}
              </button>
            </div>
          </div>
        </aside>
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

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
