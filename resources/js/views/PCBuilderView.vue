<script setup>
import { computed, ref, watch } from 'vue'
import { useCartStore } from '../stores/cartStore'

const cart = useCartStore()

const componentTypes = [
  { key: 'cpu', label: 'CPU', icon: '🖥️' },
  { key: 'mainboard', label: 'Mainboard', icon: '🔌' },
  { key: 'ram', label: 'RAM', icon: '💾' },
  { key: 'vga', label: 'VGA', icon: '🎮' },
  { key: 'ssd', label: 'SSD', icon: '💿' },
  { key: 'psu', label: 'PSU', icon: '⚡' },
  { key: 'case', label: 'Case', icon: '📦' },
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

const searchInputs = ref({
  cpu: '',
  mainboard: '',
  ram: '',
  vga: '',
  ssd: '',
  psu: '',
  case: '',
})

const globalSearchQuery = ref('')
const globalSearchResults = ref({})
const showGlobalResults = ref(false)
const loadingGlobalSearch = ref(false)

const searchResults = ref({
  cpu: [],
  mainboard: [],
  ram: [],
  vga: [],
  ssd: [],
  psu: [],
  case: [],
})

const showResults = ref({
  cpu: false,
  mainboard: false,
  ram: false,
  vga: false,
  ssd: false,
  psu: false,
  case: false,
})

const loadingSearch = ref({
  cpu: false,
  mainboard: false,
  ram: false,
  vga: false,
  ssd: false,
  psu: false,
  case: false,
})

const error = ref(null)
const successMessage = ref(null)

function extractSocket(name) {
  const sockets = ['LGA1700', 'LGA1200', 'AM5', 'AM4', 'TRX4']
  for (const socket of sockets) {
    if (name.includes(socket)) return socket
  }
  return null
}

const compatibilityWarnings = computed(() => {
  const warnings = []
  
  if (selectedParts.value.cpu && selectedParts.value.mainboard) {
    const cpuSocket = extractSocket(selectedParts.value.cpu.name)
    const mbSocket = extractSocket(selectedParts.value.mainboard.name)
    
    if (cpuSocket && mbSocket) {
      if (mbSocket.includes(cpuSocket)) {
        warnings.push({
          type: 'success',
          message: `✅ CPU socket ${cpuSocket} phù hợp với Mainboard`
        })
      } else {
        warnings.push({
          type: 'error',
          message: `❌ CPU socket ${cpuSocket} KHÔNG phù hợp với Mainboard socket ${mbSocket}`
        })
      }
    }
  }
  
  return warnings
})

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
    selectedParts.value.mainboard = null
    searchInputs.value.mainboard = ''
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

    if (categoryKey === 'mainboard' && selectedParts.value.cpu) {
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
    selectedParts.value.mainboard = null
    searchInputs.value.mainboard = ''
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

const totalPrice = computed(() => {
  return componentTypes.reduce((sum, type) => sum + Number(selectedParts.value[type.key]?.price || 0), 0)
})

function addAllToCart() {
  componentTypes.forEach(({ key }) => {
    const product = selectedParts.value[key]
    if (product) {
      cart.addToCart(product, 1)
    }
  })

  successMessage.value = '✅ Đã thêm toàn bộ cấu hình vào giỏ hàng'
  setTimeout(() => {
    successMessage.value = null
  }, 2500)
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
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="max-w-4xl mx-auto px-4 py-8">
      <h1 class="text-4xl font-bold text-gray-900 mb-2">🖥️ Xây dựng PC của bạn</h1>
      <p class="text-gray-600 mb-8">Tìm kiếm và chọn các linh kiện phù hợp. Nhập tên sản phẩm rồi nhấn Enter</p>

      <!-- Global Search Bar -->
      <div class="mb-8 relative">
        <div class="bg-white rounded-lg shadow-lg p-6">
          <div class="flex items-center gap-2">
            <span class="text-2xl">🔍</span>
            <input
              v-model="globalSearchQuery"
              @keydown="handleGlobalKeyDown"
              @focus="showGlobalResults = Object.keys(globalSearchResults).length > 0"
              type="text"
              placeholder="Tìm kiếm tất cả sản phẩm... (ví dụ: Intel i7, RTX 4060, ...)"
              class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition"
            />
            <button
              @click="performGlobalSearch"
              :disabled="loadingGlobalSearch"
              class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium"
            >
              {{ loadingGlobalSearch ? '⏳' : 'Tìm' }}
            </button>
          </div>

          <!-- Global Search Results -->
          <div v-if="showGlobalResults && Object.keys(globalSearchResults).length > 0" class="mt-4 border-t pt-4">
            <div v-for="(categoryData, categoryKey) in globalSearchResults" :key="categoryKey" class="mb-4">
              <h3 class="font-semibold text-gray-900 mb-2">📌 {{ categoryData.category_name }}</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div
                  v-for="product in categoryData.products"
                  :key="product.id"
                  @click="selectProductFromGlobalSearch(categoryKey, product)"
                  class="p-3 bg-gray-50 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-400 cursor-pointer transition"
                >
                  <div class="flex justify-between items-start">
                    <div class="flex-1">
                      <p class="font-semibold text-gray-900 line-clamp-1">{{ product.name }}</p>
                      <p class="text-xs text-gray-500">{{ product.sku }}</p>
                    </div>
                    <p class="font-bold text-blue-600 ml-2">{{ formatPrice(product.price) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Warnings -->
      <div v-if="compatibilityWarnings.length > 0" class="mb-6 space-y-2">
        <div v-for="(warning, idx) in compatibilityWarnings" :key="idx"
          :class="[
            'p-4 rounded-lg border',
            warning.type === 'error' 
              ? 'bg-red-50 text-red-700 border-red-200' 
              : 'bg-green-50 text-green-700 border-green-200'
          ]">
          {{ warning.message }}
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="mb-6 p-4 bg-red-50 text-red-700 rounded-lg border border-red-200">
        ⚠️ {{ error }}
      </div>

      <!-- Success Message -->
      <div v-if="successMessage" class="mb-6 p-4 bg-green-50 text-green-700 rounded-lg border border-green-200">
        {{ successMessage }}
      </div>

      <!-- PC Builder Form -->
      <div class="space-y-6 mb-8">
        <div v-for="component in componentTypes" :key="component.key" class="bg-white rounded-lg shadow-md p-6">
          <!-- Header -->
          <div class="flex items-center mb-4">
            <span class="text-3xl mr-3">{{ component.icon }}</span>
            <h2 class="text-2xl font-bold text-gray-900">{{ component.label }}</h2>
          </div>

          <!-- Search Input -->
          <div class="relative mb-4">
            <input
              :value="searchInputs[component.key]"
              @input="searchInputs[component.key] = $event.target.value"
              @keydown="handleKeyDown(component.key, $event)"
              @focus="showResults[component.key] = searchResults[component.key].length > 0"
              type="text"
              :placeholder="`Nhập tên ${component.label}... (Enter để tìm)`"
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition"
            />
            <button
              @click="searchProducts(component.key)"
              :disabled="loadingSearch[component.key]"
              class="absolute right-2 top-1/2 transform -translate-y-1/2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 text-sm font-medium"
            >
              {{ loadingSearch[component.key] ? '⏳' : '🔍' }}
            </button>
          </div>

          <!-- Search Results Dropdown -->
          <div v-if="showResults[component.key]" class="relative mb-4">
            <div class="absolute top-0 left-0 right-0 bg-white border border-gray-300 rounded-lg shadow-lg z-10 max-h-64 overflow-y-auto">
              <div v-if="loadingSearch[component.key]" class="p-4 text-center text-gray-500">
                Đang tìm kiếm...
              </div>
              <div v-else-if="searchResults[component.key].length === 0" class="p-4 text-center text-gray-500">
                Không tìm thấy sản phẩm
              </div>
              <div v-else class="divide-y">
                <div
                  v-for="product in searchResults[component.key]"
                  :key="product.id"
                  @click="selectProduct(component.key, product)"
                  class="p-4 hover:bg-blue-50 cursor-pointer transition"
                >
                  <div class="flex justify-between items-start">
                    <div class="flex-1">
                      <p class="font-semibold text-gray-900">{{ product.name }}</p>
                      <p class="text-sm text-gray-500">SKU: {{ product.sku }}</p>
                      <p class="text-xs text-gray-400 mt-1">Tồn kho: {{ product.stock_quantity }}</p>
                    </div>
                    <p class="font-bold text-blue-600 ml-4">{{ formatPrice(product.price) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Selected Product Display -->
          <div v-if="selectedParts[component.key]" class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <p class="text-sm text-gray-600 mb-1">Đã chọn:</p>
                <p class="font-bold text-gray-900 text-lg">{{ selectedParts[component.key].name }}</p>
                <p class="text-sm text-gray-600 mt-2">SKU: {{ selectedParts[component.key].sku }}</p>
              </div>
              <div class="text-right">
                <p class="text-2xl font-bold text-blue-600">{{ formatPrice(selectedParts[component.key].price) }}</p>
                <button
                  @click="removePart(component.key)"
                  class="mt-2 px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm font-medium"
                >
                  Xóa
                </button>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
            <p class="text-gray-500 text-lg">Chưa chọn sản phẩm</p>
          </div>
        </div>
      </div>

      <!-- Summary Section -->
      <div class="bg-white rounded-lg shadow-lg p-8 sticky bottom-8">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600 text-lg mb-2">💰 Tổng giá cấu hình:</p>
            <p class="text-5xl font-bold text-blue-600">{{ formatPrice(totalPrice) }}</p>
          </div>
          <button
            @click="addAllToCart"
            :disabled="!selectedParts.cpu || !selectedParts.mainboard"
            class="px-8 py-4 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed font-bold text-lg transition"
          >
            🛒 Thêm vào giỏ hàng
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
input:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>
  const warnings = []
  
  if (selectedParts.value.cpu && selectedParts.value.mainboard) {
    const cpuSocket = extractSocket(selectedParts.value.cpu.name)
    const mbSocket = extractSocket(selectedParts.value.mainboard.name)
    
    if (cpuSocket && mbSocket) {
      if (mbSocket.includes(cpuSocket)) {
        warnings.push({
          type: 'success',
          message: `✅ CPU socket ${cpuSocket} phù hợp với Mainboard`
        })
      } else {
        warnings.push({
          type: 'error',
          message: `❌ CPU socket ${cpuSocket} KHÔNG phù hợp với Mainboard socket ${mbSocket}`
        })
      }
    }
  }
  
  return warnings
})

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

    // Nếu tìm mainboard, thêm cpu_id để filter theo socket
    if (categoryKey === 'mainboard' && selectedParts.value.cpu) {
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

  // Nếu chọn CPU, reset mainboard search
  if (categoryKey === 'cpu') {
    selectedParts.value.mainboard = null
    searchInputs.value.mainboard = ''
  }

  successMessage.value = `Đã chọn ${product.name}`
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

const totalPrice = computed(() => {
  return componentTypes.reduce((sum, type) => sum + Number(selectedParts.value[type.key]?.price || 0), 0)
})

function addAllToCart() {
  componentTypes.forEach(({ key }) => {
    const product = selectedParts.value[key]
    if (product) {
      cart.addToCart(product, 1)
    }
  })

  successMessage.value = '✅ Đã thêm toàn bộ cấu hình vào giỏ hàng'
  setTimeout(() => {
    successMessage.value = null
  }, 2500)
}

function handleKeyDown(categoryKey, event) {
  if (event.key === 'Enter') {
    event.preventDefault()
    searchProducts(categoryKey)
  }
}
