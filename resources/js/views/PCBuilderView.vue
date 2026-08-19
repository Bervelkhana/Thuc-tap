<script setup>
import { computed, ref, watch } from 'vue'
import { useCartStore } from '../stores/cartStore'
import { useRouter } from 'vue-router'

const cart = useCartStore()
const router = useRouter()

const componentTypes = [
  { key: 'cpu', label: 'CPU', icon: '🖥️' },
  { key: 'MAIN', label: 'MAIN', icon: '🔌' },
  { key: 'ram', label: 'RAM', icon: '💾' },
  { key: 'vga', label: 'VGA', icon: '🎮' },
  { key: 'ssd', label: 'SSD', icon: '💿' },
  { key: 'psu', label: 'PSU', icon: '⚡' },
  { key: 'case', label: 'Case', icon: '📦' },
  { key: 'cooler', label: 'Cooler', icon: '❄️' },
]

const selectedParts = ref({
  cpu: null,
  MAIN: null,
  ram: null,
  vga: null,
  ssd: null,
  psu: null,
  case: null,
  cooler: null,
})

const searchInputs = ref({
  cpu: '',
  MAIN: '',
  ram: '',
  vga: '',
  ssd: '',
  psu: '',
  case: '',
  cooler: '',
})

const globalSearchQuery = ref('')
const globalSearchResults = ref({})
const showGlobalResults = ref(false)
const loadingGlobalSearch = ref(false)

const searchResults = ref({
  cpu: [],
  MAIN: [],
  ram: [],
  vga: [],
  ssd: [],
  psu: [],
  case: [],
  cooler: [],
})

const showResults = ref({
  cpu: false,
  MAIN: false,
  ram: false,
  vga: false,
  ssd: false,
  psu: false,
  case: false,
  cooler: false,
})

const loadingSearch = ref({
  cpu: false,
  MAIN: false,
  ram: false,
  vga: false,
  ssd: false,
  psu: false,
  case: false,
  cooler: false,
})

const error = ref(null)
const successMessage = ref(null)

const compatibilityResult = ref(null)
const isValidating = ref(false)
const serverTotal = ref(0)
const totalMismatch = ref(false)

const compatibleMAINs = ref([])
const cpuCompatibilityInfo = ref(null)
const isLoadingCompatible = ref(false)
const showAllMAINs = ref(false)

const compatibleCases = ref([])
const vgaCaseInfo = ref(null)
const isLoadingCompatibleCases = ref(false)
const showAllCases = ref(false)

function extractSocket(name) {
  const sockets = ['LGA1700', 'LGA1200', 'AM5', 'AM4', 'TRX4']
  for (const socket of sockets) {
    if (name.includes(socket)) return socket
  }
  return null
}

async function validateCompatibility() {
  const selected = selectedParts.value

  const hasComponents = Object.values(selected).filter(Boolean).length >= 2
  if (!hasComponents) {
    compatibilityResult.value = null
    serverTotal.value = 0
    totalMismatch.value = false
    return
  }

  isValidating.value = true
  totalMismatch.value = false
  try {
    const payload = {
      selected_products: Object.entries(selected)
        .filter(([_, product]) => product)
        .map(([category, product]) => ({
          category,
          product_id: product.id,
        })),
    }

    const response = await fetch('/api/pc-builder/validate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    })

    const data = await response.json()
    const result = data.data || {}

    compatibilityResult.value = result.compatibility || null
    serverTotal.value = (int) (result.server_total || 0)
    totalMismatch.value = result.is_total_valid === false
  } catch (error) {
    console.error('Validation error:', error)
    compatibilityResult.value = null
    serverTotal.value = 0
    totalMismatch.value = false
  } finally {
    isValidating.value = false
  }
}

watch(
  () => [
    selectedParts.value.cpu,
    selectedParts.value.MAIN,
    selectedParts.value.ram,
    selectedParts.value.vga,
    selectedParts.value.ssd,
    selectedParts.value.psu,
    selectedParts.value.case,
  ],
  () => {
    validateCompatibility()
  },
  { deep: true }
)

watch(
  () => selectedParts.value.cpu,
  (newCpu) => {
    if (newCpu) {
      fetchCompatibleMAINs(newCpu.id)
    } else {
      compatibleMAINs.value = []
      cpuCompatibilityInfo.value = null
    }
    showAllMAINs.value = false
  }
)

watch(
  () => selectedParts.value.vga,
  (newVga) => {
    if (newVga) {
      fetchCompatibleCases('vga', newVga.id)
    } else {
      compatibleCases.value = []
      vgaCaseInfo.value = null
    }
    showAllCases.value = false
  }
)

watch(
  () => selectedParts.value.case,
  (newCase) => {
    if (newCase) {
      fetchCompatibleCases('case', null, newCase.id)
    } else {
      compatibleCases.value = []
      vgaCaseInfo.value = null
    }
    showAllCases.value = false
  }
)

async function fetchCompatibleMAINs(cpuId) {
  if (!cpuId) {
    compatibleMAINs.value = []
    cpuCompatibilityInfo.value = null
    return
  }

  isLoadingCompatible.value = true
  try {
    const response = await fetch(`/api/pc-builder/compatible-MAINs?cpu_id=${cpuId}`)
    const data = await response.json()

    if (data.status === 'success') {
      compatibleMAINs.value = data.data.MAINs
      cpuCompatibilityInfo.value = {
        cpu: data.data.cpu,
        total: data.data.total,
      }
    } else {
      compatibleMAINs.value = []
      cpuCompatibilityInfo.value = null
    }
  } catch (error) {
    console.error('Error fetching compatible MAINs:', error)
    compatibleMAINs.value = []
    cpuCompatibilityInfo.value = null
  } finally {
    isLoadingCompatible.value = false
  }
}

function selectMAIN(MAIN) {
  selectedParts.value.MAIN = MAIN
  searchInputs.value.MAIN = ''
  searchResults.value.MAIN = []
  showResults.value.MAIN = false
  validateCompatibility()
}

async function fetchCompatibleCases(mode, vgaId = null, caseId = null) {
  if (!vgaId && !caseId) {
    compatibleCases.value = []
    vgaCaseInfo.value = null
    return
  }

  isLoadingCompatibleCases.value = true
  try {
    const params = new URLSearchParams()
    if (mode === 'vga' && vgaId) {
      params.set('vga_id', String(vgaId))
    } else if (mode === 'case' && caseId) {
      params.set('case_id', String(caseId))
    }

    const response = await fetch(`/api/pc-builder/compatible-cases?${params.toString()}`)
    const data = await response.json()

    if (data.status === 'success') {
      if (data.data.mode === 'vga_selected') {
        compatibleCases.value = data.data.cases
        vgaCaseInfo.value = {
          vga: data.data.vga,
          total: data.data.total,
        }
      } else if (data.data.mode === 'case_selected') {
        compatibleCases.value = data.data.vgas
        vgaCaseInfo.value = {
          case: data.data.case,
          total: data.data.total,
        }
      }
    } else {
      compatibleCases.value = []
      vgaCaseInfo.value = null
    }
  } catch (error) {
    console.error('Error fetching compatible cases/VGAs:', error)
    compatibleCases.value = []
    vgaCaseInfo.value = null
  } finally {
    isLoadingCompatibleCases.value = false
  }
}

function selectCaseFromSuggestion(caseProduct) {
  selectedParts.value.case = caseProduct
  searchInputs.value.case = ''
  searchResults.value.case = []
  showResults.value.case = false
  validateCompatibility()
}

function selectVGAFromSuggestion(vgaProduct) {
  selectedParts.value.vga = vgaProduct
  searchInputs.value.vga = ''
  searchResults.value.vga = []
  showResults.value.vga = false
  validateCompatibility()
}

// Global search handler
async function performGlobalSearch() {
  const query = globalSearchQuery.value.trim()
  
  if (!query || query.length < 2) {
    globalSearchResults.value = {}
    showGlobalResults.value = false
    return
  }

  loadingGlobalSearch.value = true
  error.value = null

  try {
    const response = await fetch(`/api/pc-builder/search?q=${encodeURIComponent(query)}`)
    const result = await response.json()

    if (result.status === 'success') {
      globalSearchResults.value = result.data || {}
      showGlobalResults.value = Object.keys(globalSearchResults.value).length > 0
    } else {
      error.value = result.message || 'Lỗi tìm kiếm'
    }
  } catch (err) {
    error.value = 'Lỗi kết nối tới server'
    console.error(err)
  } finally {
    loadingGlobalSearch.value = false
  }
}

function selectProductFromGlobalSearch(categoryKey, product) {
  selectedParts.value[categoryKey] = product
  globalSearchQuery.value = ''
  globalSearchResults.value = {}
  showGlobalResults.value = false
  searchInputs.value[categoryKey] = ''

  if (categoryKey === 'cpu') {
    selectedParts.value.MAIN = null
    searchInputs.value.MAIN = ''
  }

  successMessage.value = `✅ Đã chọn ${product.name}`
  setTimeout(() => {
    successMessage.value = null
  }, 2000)
}

async function searchProducts(categoryKey) {
  const query = searchInputs.value[categoryKey].trim()
  
  if (!query || query.length < 1) {
    searchResults.value[categoryKey] = []
    showResults.value[categoryKey] = false
    return
  }

  loadingSearch.value[categoryKey] = true
  error.value = null

  try {
    const params = new URLSearchParams({
      category: categoryKey,
      search: query
    })

    if (categoryKey === 'MAIN' && selectedParts.value.cpu) {
      params.append('cpu_id', selectedParts.value.cpu.id)
    }

    const response = await fetch(`/api/pc-builder/components?${params}`)
    const result = await response.json()

    if (result.status === 'success') {
      searchResults.value[categoryKey] = result.data || []
      showResults.value[categoryKey] = searchResults.value[categoryKey].length > 0
    } else {
      error.value = result.message || 'Lỗi tìm kiếm'
    }
  } catch (err) {
    error.value = 'Lỗi kết nối tới server'
    console.error(err)
  } finally {
    loadingSearch.value[categoryKey] = false
  }
}

function selectProduct(categoryKey, product) {
  selectedParts.value[categoryKey] = product
  searchInputs.value[categoryKey] = ''
  searchResults.value[categoryKey] = []
  showResults.value[categoryKey] = false

  if (categoryKey === 'cpu') {
    selectedParts.value.MAIN = null
    searchInputs.value.MAIN = ''
  }

  successMessage.value = `✅ Đã chọn ${product.name}`
  setTimeout(() => {
    successMessage.value = null
  }, 2000)
}

function removePart(categoryKey) {
  selectedParts.value[categoryKey] = null
  searchInputs.value[categoryKey] = ''
  searchResults.value[categoryKey] = []
  showResults.value[categoryKey] = false
}

function formatPrice(price) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(price || 0))
}

const selectedPartsSummary = computed(() => {
  return componentTypes.map(type => ({
    key: type.key,
    label: type.label,
    name: selectedParts.value[type.key]?.name || ''
  }))
})

const hasSelectedParts = computed(() => {
  return componentTypes.some(type => selectedParts.value[type.key])
})

function clearAllParts() {
  componentTypes.forEach(type => {
    selectedParts.value[type.key] = null
    searchInputs.value[type.key] = ''
    searchResults.value[type.key] = []
    showResults.value[type.key] = false
  })
  compatibleMAINs.value = []
  cpuCompatibilityInfo.value = null
  showAllMAINs.value = false
  compatibleCases.value = []
  vgaCaseInfo.value = null
  showAllCases.value = false
  validateCompatibility()
}

function goHome() {
  router.push('/')
}

function addAllToCart() {
  componentTypes.forEach(({ key }) => {
    const product = selectedParts.value[key]
    if (product) {
      cart.addToCart(product, 1)
    }
  })

  router.push('/checkout-new')
}

function handleKeyDown(categoryKey, event) {
  if (event.key === 'Enter') {
    event.preventDefault()
    searchProducts(categoryKey)
  }
}

function handleGlobalKeyDown(event) {
  if (event.key === 'Enter') {
    event.preventDefault()
    performGlobalSearch()
  }
}
</script>

<template>
  <div class="min-h-screen bg-[var(--bg-body)] font-body transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
      <div class="flex flex-col lg:flex-row gap-6">
        <!-- LEFT: Component Selection -->
        <div class="flex-1 min-w-0 space-y-4">
          <p class="text-gray-600 dark:text-gray-400 mb-6">Tìm kiếm và chọn các linh kiện phù hợp. Nhập tên sản phẩm rồi nhấn Enter</p>

          <!-- Global Search Bar -->
          <div class="mb-6 relative">
            <div class="bg-white rounded-lg shadow p-4">
              <div class="flex items-center gap-2">
                <span class="text-xl">🔍</span>
                <input
                  v-model="globalSearchQuery"
                  @keydown="handleGlobalKeyDown"
                  @focus="showGlobalResults = Object.keys(globalSearchResults).length > 0"
                  type="text"
                  placeholder="Tìm kiếm tất cả sản phẩm..."
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition text-sm"
                />
                <button
                  @click="performGlobalSearch"
                  :disabled="loadingGlobalSearch"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 text-sm font-medium"
                >
                  {{ loadingGlobalSearch ? '⏳' : 'Tìm' }}
                </button>
              </div>

              <!-- Global Search Results -->
              <div v-if="showGlobalResults && Object.keys(globalSearchResults).length > 0" class="mt-3 border-t pt-3">
                <div v-for="(categoryData, categoryKey) in globalSearchResults" :key="categoryKey" class="mb-3">
                  <h3 class="font-semibold text-gray-900 mb-2 text-sm">📌 {{ categoryData.category_name }}</h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div
                      v-for="product in categoryData.products"
                      :key="product.id"
                      @click="selectProductFromGlobalSearch(categoryKey, product)"
                      class="p-2 bg-gray-50 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-400 cursor-pointer transition"
                    >
                      <div class="flex justify-between items-start">
                        <div class="flex-1 min-w-0">
                          <p class="font-semibold text-gray-900 text-sm truncate">{{ product.name }}</p>
                          <p class="text-xs text-gray-600">{{ product.sku }}</p>
                        </div>
                        <p class="font-bold text-blue-600 text-sm ml-2">{{ formatPrice(product.price) }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Warnings -->
          <div v-if="totalMismatch" class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg border text-sm">
            ⚠️ Có sự không khớp trong tổng giá. Vui lòng thử lại.
          </div>

          <!-- Error Message -->
          <div v-if="error" class="p-3 bg-red-50 text-red-700 rounded-lg border border-red-200 text-sm">
            ⚠️ {{ error }}
          </div>

          <!-- Success Message -->
          <div v-if="successMessage" class="p-3 bg-green-50 text-green-700 rounded-lg border border-green-200 text-sm">
            {{ successMessage }}
          </div>

          <!-- PC Builder Form -->
          <div class="space-y-3">
            <div v-for="component in componentTypes" :key="component.key" class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
              <!-- Header -->
              <div class="flex items-center mb-3">
                <span class="text-2xl mr-2">{{ component.icon }}</span>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ component.label }}</h2>
              </div>

              <!-- Search Input -->
              <div class="relative mb-3">
                <input
                  :value="searchInputs[component.key]"
                  @input="searchInputs[component.key] = $event.target.value"
                  @keydown="handleKeyDown(component.key, $event)"
                  @focus="showResults[component.key] = searchResults[component.key].length > 0"
                  type="text"
                  :placeholder="`Nhập tên ${component.label}...`"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 dark:focus:border-cyan-400 transition text-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                />
                <button
                  @click="searchProducts(component.key)"
                  :disabled="loadingSearch[component.key]"
                  class="absolute right-1 top-1/2 transform -translate-y-1/2 px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 text-xs font-medium"
                >
                  {{ loadingSearch[component.key] ? '⏳' : '🔍' }}
                </button>
              </div>

              <!-- Search Results Dropdown -->
              <div v-if="showResults[component.key]" class="relative mb-3">
                <div class="absolute top-0 left-0 right-0 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg shadow-lg z-10 max-h-64 overflow-y-auto">
                  <div v-if="loadingSearch[component.key]" class="p-3 text-center text-gray-600 dark:text-gray-400 text-sm">
                    Đang tìm kiếm...
                  </div>
                  <div v-else-if="searchResults[component.key].length === 0" class="p-3 text-center text-gray-600 dark:text-gray-400 text-sm">
                    Không tìm thấy sản phẩm
                  </div>
                  <div v-else class="divide-y divide-gray-200 dark:divide-slate-700">
                    <div
                      v-for="product in searchResults[component.key]"
                      :key="product.id"
                      @click="selectProduct(component.key, product)"
                      class="p-3 hover:bg-blue-50 dark:hover:bg-slate-700 cursor-pointer transition"
                    >
                      <div class="flex justify-between items-start">
                        <div class="flex-1 min-w-0">
                          <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ product.name }}</p>
                          <p class="text-xs text-gray-600 dark:text-gray-400">SKU: {{ product.sku }}</p>
                          <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Tồn kho: {{ product.stock_quantity }}</p>
                        </div>
                        <p class="font-bold text-blue-600 text-sm ml-2">{{ formatPrice(product.price) }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Selected Product Display -->
              <div v-if="selectedParts[component.key]" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                <div class="flex items-start justify-between gap-2">
                  <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Đã chọn:</p>
                    <p class="font-bold text-gray-900 dark:text-white text-sm truncate">{{ selectedParts[component.key].name }}</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">SKU: {{ selectedParts[component.key].sku }}</p>
                  </div>
                  <div class="text-right shrink-0">
                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ formatPrice(selectedParts[component.key].price) }}</p>
                    <button
                      @click="removePart(component.key)"
                      class="mt-1 px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-xs font-medium"
                    >
                      Xóa
                    </button>
                  </div>
                </div>
              </div>

              <!-- Empty State -->
              <div v-else class="bg-gray-50 dark:bg-slate-700/50 border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-lg p-4 text-center">
                <p class="text-gray-600 dark:text-gray-400 text-sm">Chưa chọn sản phẩm</p>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT: Sticky Sidebar -->
        <div class="lg:w-[380px] shrink-0">
          <div class="sticky top-24 space-y-4">
            <!-- Selected Parts Summary -->
            <div v-if="hasSelectedParts" class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
              <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Linh kiện đã chọn</h3>
              <div class="space-y-1">
                <div v-for="part in selectedPartsSummary" :key="part.key" class="flex justify-between text-xs">
                  <span class="text-gray-600 dark:text-gray-400">{{ part.label }}:</span>
                  <span class="text-gray-900 dark:text-white font-medium truncate ml-2">{{ part.name || 'Chưa chọn' }}</span>
                </div>
              </div>
              <button
                @click="clearAllParts"
                class="mt-3 w-full px-3 py-1.5 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-slate-600 text-xs font-medium transition"
              >
                Xóa tất cả
              </button>
            </div>

            <!-- Compatibility Result -->
            <Transition name="compatibility">
              <div v-if="compatibilityResult" class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Kết quả kiểm tra tương thích</h3>
                
                <!-- Errors -->
                <div v-if="compatibilityResult.errors?.length" class="space-y-2">
                  <div 
                    v-for="error in compatibilityResult.errors" 
                    :key="error.type"
                    class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-2"
                  >
                    <div class="flex items-start gap-2">
                      <span class="text-red-600 dark:text-red-400 font-bold text-xs">✕</span>
                      <div>
                        <p class="text-xs font-medium text-red-800 dark:text-red-300">{{ error.message }}</p>
                        <p v-if="error.type === 'gpu_case_length_mismatch' && error.details" class="text-xs text-red-600 dark:text-red-400 mt-1">
                          GPU: {{ error.details.gpu_length_mm }}mm | Case: {{ error.details.case_max_gpu_length_mm }}mm | 
                          Vượt quá: {{ error.details.excess_mm }}mm
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
            
                <!-- Warnings -->
                <div v-if="compatibilityResult.warnings?.length" class="mt-2 space-y-2">
                  <div 
                    v-for="warning in compatibilityResult.warnings" 
                    :key="warning.type"
                    class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 p-2"
                  >
                    <div class="flex items-start gap-2">
                      <span class="text-yellow-600 dark:text-yellow-400 font-bold text-xs">⚠</span>
                      <p class="text-xs text-yellow-800 dark:text-yellow-300">{{ warning.message }}</p>
                    </div>
                  </div>
                </div>
            
                <!-- Success -->
                <div v-if="compatibilityResult.is_compatible && !compatibilityResult.errors?.length" class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-2">
                  <div class="flex items-center gap-2">
                    <span class="text-green-600 dark:text-green-400 font-bold text-xs">✓</span>
                    <p class="text-xs font-medium text-green-800 dark:text-green-300">
                      Cấu hình tương thích
                    </p>
                  </div>
                  
                  <!-- GPU-Case detail -->
                  <div v-if="compatibilityResult.details?.gpu_case" class="mt-1 text-xs text-green-700 dark:text-green-400">
                    <p>GPU vừa Case (còn dư {{ compatibilityResult.details.gpu_case.remaining_space_mm }}mm)</p>
                  </div>
                  
                  <!-- Cooler-CPU Socket Compatibility -->
                  <div v-if="compatibilityResult.details?.cooler_cpu" class="mt-1 text-xs" :class="{
                    'text-green-700 dark:text-green-400': compatibilityResult.details.cooler_cpu.status === 'compatible',
                    'text-gray-600 dark:text-gray-400': compatibilityResult.details.cooler_cpu.status === 'unknown'
                  }">
                    <p v-if="compatibilityResult.details.cooler_cpu.status === 'compatible'">
                      ✓ Cooler hỗ trợ socket {{ compatibilityResult.details.cooler_cpu.cpu_socket }}
                    </p>
                    <p v-else-if="compatibilityResult.details.cooler_cpu.status === 'unknown'">
                      {{ compatibilityResult.details.cooler_cpu.message }}
                    </p>
                  </div>
                </div>
          
                <!-- Loading -->
                <div v-if="isValidating" class="mt-2 flex items-center gap-2 text-gray-600 dark:text-gray-400">
                  <div class="h-3 w-3 animate-spin rounded-full border-2 border-gray-300 dark:border-slate-600 border-t-black dark:border-t-white"></div>
                  <span class="text-xs">Đang kiểm tra...</span>
                </div>
              </div>
            </Transition>

            <!-- Auto-suggested MAINs -->
            <div v-if="cpuCompatibilityInfo && selectedParts.cpu" class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
              <div class="flex items-center justify-between mb-2">
                <h3 class="text-xs font-semibold text-blue-900 dark:text-blue-300">
                  MAIN tương thích
                </h3>
                <span class="text-xs text-blue-600 dark:text-blue-400">
                  {{ cpuCompatibilityInfo.cpu.socket_type }}
                </span>
              </div>

              <!-- Loading -->
              <div v-if="isLoadingCompatible" class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                <div class="h-3 w-3 animate-spin rounded-full border-2 border-blue-300 dark:border-blue-700 border-t-blue-600 dark:border-t-blue-400"></div>
                <span class="text-xs">Đang tìm...</span>
              </div>

              <!-- No compatible MAINs -->
              <div v-else-if="compatibleMAINs.length === 0" class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 p-2">
                <p class="text-xs text-yellow-800 dark:text-yellow-300">
                  Không tìm thấy MAIN tương thích.
                </p>
              </div>

              <!-- Compatible MAINs list -->
              <div v-else class="space-y-1">
                <div class="grid grid-cols-1 gap-1">
                  <div 
                    v-for="mb in (showAllMAINs ? compatibleMAINs : compatibleMAINs.slice(0, 3))" 
                    :key="mb.id"
                    class="flex items-center justify-between rounded-lg bg-gray-50 dark:bg-slate-700 border border-blue-100 dark:border-blue-900 p-2 hover:border-blue-300 dark:hover:border-blue-700 transition cursor-pointer"
                    @click="selectMAIN(mb)"
                  >
                    <div class="flex-1 min-w-0">
                      <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ mb.name }}</p>
                      <p class="text-xs text-gray-600 dark:text-gray-400">
                        {{ mb.brand }} | {{ mb.socket_type }}
                      </p>
                    </div>
                    <div class="text-right shrink-0 ml-2">
                      <p class="text-xs font-semibold text-blue-600 dark:text-blue-400">{{ formatPrice(mb.price) }}</p>
                    </div>
                  </div>
                </div>
                
                <!-- Show more button -->
                <button 
                  v-if="!showAllMAINs && compatibleMAINs.length > 3"
                  @click="showAllMAINs = true"
                  class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 mt-1"
                >
                  Xem tất cả {{ compatibleMAINs.length }} →
                </button>
              </div>
            </div>

            <!-- Auto-suggested Cases for VGA / VGAs for Case -->
            <div v-if="vgaCaseInfo && (selectedParts.vga || selectedParts.case)" class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
              <div class="flex items-center justify-between mb-2">
                <h3 class="text-xs font-semibold text-green-900 dark:text-green-300">
                  {{ selectedParts.vga ? 'Case phù hợp' : 'VGA phù hợp' }}
                </h3>
                <span class="text-xs text-green-600 dark:text-green-400">
                  {{ vgaCaseInfo.total }} sản phẩm
                </span>
              </div>

              <!-- Loading -->
              <div v-if="isLoadingCompatibleCases" class="flex items-center gap-2 text-green-600 dark:text-green-400">
                <div class="h-3 w-3 animate-spin rounded-full border-2 border-green-300 dark:border-green-700 border-t-green-600 dark:border-t-green-400"></div>
                <span class="text-xs">Đang tìm...</span>
              </div>

              <!-- No matches -->
              <div v-else-if="compatibleCases.length === 0" class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 p-2">
                <p class="text-xs text-yellow-800 dark:text-yellow-300">
                  Không tìm thấy {{ selectedParts.vga ? 'Case' : 'VGA' }} phù hợp.
                </p>
              </div>

              <!-- Compatible list -->
              <div v-else class="space-y-1">
                <div class="grid grid-cols-1 gap-1">
                  <div 
                    v-for="item in (showAllCases ? compatibleCases : compatibleCases.slice(0, 3))" 
                    :key="item.id"
                    class="flex items-center justify-between rounded-lg bg-gray-50 dark:bg-slate-700 border border-green-100 dark:border-green-900 p-2 hover:border-green-300 dark:hover:border-green-700 transition cursor-pointer"
                    @click="selectedParts.vga ? selectCaseFromSuggestion(item) : selectVGAFromSuggestion(item)"
                  >
                    <div class="flex-1 min-w-0">
                      <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ item.name }}</p>
                      <p class="text-xs text-gray-600 dark:text-gray-400">
                        {{ item.brand }}
                        <span v-if="selectedParts.vga && item.max_gpu_length_mm">
                          | Tối đa {{ item.max_gpu_length_mm }}mm
                        </span>
                        <span v-if="selectedParts.case && item.gpu_length_mm">
                          | {{ item.gpu_length_mm }}mm
                        </span>
                      </p>
                    </div>
                    <div class="text-right shrink-0 ml-2">
                      <p class="text-xs font-semibold text-green-600 dark:text-green-400">{{ formatPrice(item.price) }}</p>
                    </div>
                  </div>
                </div>
                
                <button 
                  v-if="!showAllCases && compatibleCases.length > 3"
                  @click="showAllCases = true"
                  class="text-xs text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 mt-1"
                >
                  Xem tất cả {{ compatibleCases.length }} →
                </button>
              </div>
            </div>

             <!-- Summary Section -->
             <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
               <div class="mb-3">
                  <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">Tổng giá cấu hình</p>
                  <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ formatPrice(serverTotal) }}</p>
                  <p v-if="totalMismatch" class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                    Tổng giá không khớp. Đang hiển thị giá từ server.
                  </p>
                </div>
              <button
                @click="addAllToCart"
                :disabled="!selectedParts.cpu || !selectedParts.MAIN"
                class="w-full px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium text-sm transition"
              >
                🛒 Thêm vào giỏ hàng
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
input:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.compatibility-enter-active,
.compatibility-leave-active {
  transition: all 0.3s ease;
}
.compatibility-enter-from,
.compatibility-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>


