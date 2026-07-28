<script setup>
import { ref, computed, watch } from 'vue'
import { useCartStore } from '../stores/cartStore'

const cart = useCartStore()
const loading = ref(false)
const error = ref(null)
const successMessage = ref(null)

// PC Configuration
const pcConfig = ref({
  cpu: null,
  mainboard: null,
  ram: null,
  vga: null,
  ssd: null,
  psu: null,
  case: null,
})

// Available products by category
const products = ref({
  cpu: [],
  mainboard: [],
  ram: [],
  vga: [],
  ssd: [],
  psu: [],
  case: [],
})

// Validation errors
const validationErrors = ref([])

// Fetch products khi mount
async function fetchProducts() {
  loading.value = true
  try {
    const categories = ['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case']
    
    for (const cat of categories) {
      const response = await fetch(`/api/products?search=${cat}`)
      const result = await response.json()
      if (result.status === 'success') {
        products.value[cat] = result.data
      }
    }
  } catch (err) {
    error.value = 'Không thể tải danh sách sản phẩm'
    console.error(err)
  } finally {
    loading.value = false
  }
}

// Validate PC configuration
async function validateConfig() {
  validationErrors.value = []
  error.value = null

  const config = {
    cpu_id: pcConfig.value.cpu?.id,
    mainboard_id: pcConfig.value.mainboard?.id,
    ram_ids: pcConfig.value.ram?.id ? [pcConfig.value.ram.id] : [],
    vga_id: pcConfig.value.vga?.id,
    ssd_id: pcConfig.value.ssd?.id,
    psu_id: pcConfig.value.psu?.id,
    case_id: pcConfig.value.case?.id,
  }

  try {
    const response = await fetch('/api/pc-builder/validate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(config),
    })

    const result = await response.json()

    if (result.status === 'error') {
      validationErrors.value = result.errors || [result.message]
    } else {
      successMessage.value = 'Cấu hình PC hợp lệ! ✓'
      setTimeout(() => {
        successMessage.value = null
      }, 3000)
    }
  } catch (err) {
    error.value = 'Lỗi khi kiểm tra cấu hình'
    console.error(err)
  }
}

// Get AI recommendation
async function getRecommendation() {
  loading.value = true
  error.value = null

  try {
    const response = await fetch('/api/pc-builder/recommend', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ budget: 10000000 }), // 10 triệu VND
    })

    const result = await response.json()

    if (result.status === 'success' && result.data) {
      pcConfig.value = {
        cpu: result.data.cpu,
        mainboard: result.data.mainboard,
        ram: result.data.ram,
        vga: result.data.vga,
        ssd: result.data.ssd,
        psu: result.data.psu,
        case: result.data.case,
      }
      successMessage.value = 'Đã tải cấu hình được đề xuất'
    }
  } catch (err) {
    error.value = 'Không thể lấy đề xuất'
    console.error(err)
  } finally {
    loading.value = false
  }
}

// Calculate total price
const totalPrice = computed(() => {
  return (
    (pcConfig.value.cpu?.price || 0) +
    (pcConfig.value.mainboard?.price || 0) +
    (pcConfig.value.ram?.price || 0) +
    (pcConfig.value.vga?.price || 0) +
    (pcConfig.value.ssd?.price || 0) +
    (pcConfig.value.psu?.price || 0) +
    (pcConfig.value.case?.price || 0)
  )
})

// Add all selected products to cart
function addAllToCart() {
  const components = ['cpu', 'mainboard', 'ram', 'vga', 'ssd', 'psu', 'case']
  
  components.forEach(comp => {
    if (pcConfig.value[comp]) {
      cart.addToCart(pcConfig.value[comp], 1)
    }
  })

  successMessage.value = 'Đã thêm cấu hình vào giỏ hàng'
  setTimeout(() => {
    successMessage.value = null
  }, 2000)
}

function formatPrice(price) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
}

// Load products on mount
fetchProducts()
</script>

<template>
  <div class="min-h-screen bg-white py-12 px-6 font-system">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-12 text-center">
        <h1 class="text-4xl font-light text-gray-900 mb-4">PC Builder</h1>
        <p class="text-lg text-gray-600">Tạo cấu hình PC của bạn hoặc để AI đề xuất</p>
      </div>

      <!-- Success/Error Messages -->
      <div v-if="successMessage" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
        {{ successMessage }}
      </div>
      <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
        {{ error }}
      </div>

      <!-- Validation Errors -->
      <div v-if="validationErrors.length > 0" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <h3 class="font-semibold text-yellow-800 mb-2">Cảnh báo cấu hình:</h3>
        <ul class="text-yellow-700 space-y-1">
          <li v-for="(err, idx) in validationErrors" :key="idx">• {{ err }}</li>
        </ul>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- PC Configuration Selector -->
        <div class="lg:col-span-2 space-y-6">
          <!-- CPU -->
          <div class="border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">CPU</h3>
            <select 
              v-model="pcConfig.cpu" 
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-black"
            >
              <option :value="null">-- Chọn CPU --</option>
              <option v-for="p in products.cpu" :key="p.id" :value="p">
                {{ p.name }} - {{ formatPrice(p.price) }}
              </option>
            </select>
            <p v-if="pcConfig.cpu" class="mt-2 text-sm text-gray-600">
              Kho: {{ pcConfig.cpu.stock_quantity }} cái
            </p>
          </div>

          <!-- Mainboard -->
          <div class="border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Mainboard</h3>
            <select 
              v-model="pcConfig.mainboard" 
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-black"
            >
              <option :value="null">-- Chọn Mainboard --</option>
              <option v-for="p in products.mainboard" :key="p.id" :value="p">
                {{ p.name }} - {{ formatPrice(p.price) }}
              </option>
            </select>
          </div>

          <!-- RAM -->
          <div class="border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">RAM</h3>
            <select 
              v-model="pcConfig.ram" 
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-black"
            >
              <option :value="null">-- Chọn RAM --</option>
              <option v-for="p in products.ram" :key="p.id" :value="p">
                {{ p.name }} - {{ formatPrice(p.price) }}
              </option>
            </select>
          </div>

          <!-- VGA -->
          <div class="border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">VGA</h3>
            <select 
              v-model="pcConfig.vga" 
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-black"
            >
              <option :value="null">-- Chọn VGA --</option>
              <option v-for="p in products.vga" :key="p.id" :value="p">
                {{ p.name }} - {{ formatPrice(p.price) }}
              </option>
            </select>
          </div>

          <!-- SSD -->
          <div class="border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">SSD/Storage</h3>
            <select 
              v-model="pcConfig.ssd" 
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-black"
            >
              <option :value="null">-- Chọn SSD --</option>
              <option v-for="p in products.ssd" :key="p.id" :value="p">
                {{ p.name }} - {{ formatPrice(p.price) }}
              </option>
            </select>
          </div>

          <!-- PSU -->
          <div class="border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Power Supply</h3>
            <select 
              v-model="pcConfig.psu" 
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-black"
            >
              <option :value="null">-- Chọn PSU --</option>
              <option v-for="p in products.psu" :key="p.id" :value="p">
                {{ p.name }} - {{ formatPrice(p.price) }}
              </option>
            </select>
          </div>

          <!-- Case -->
          <div class="border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Case</h3>
            <select 
              v-model="pcConfig.case" 
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-black"
            >
              <option :value="null">-- Chọn Case --</option>
              <option v-for="p in products.case" :key="p.id" :value="p">
                {{ p.name }} - {{ formatPrice(p.price) }}
              </option>
            </select>
          </div>
        </div>

        <!-- Summary & Actions -->
        <div class="lg:col-span-1">
          <div class="sticky top-20 space-y-4">
            <!-- Price Summary -->
            <div class="border border-gray-200 rounded-lg p-6 space-y-4">
              <h3 class="text-lg font-semibold text-gray-900">Tổng giá</h3>
              
              <div class="space-y-2 text-sm">
                <div v-if="pcConfig.cpu" class="flex justify-between text-gray-700">
                  <span>CPU</span>
                  <span>{{ formatPrice(pcConfig.cpu.price) }}</span>
                </div>
                <div v-if="pcConfig.mainboard" class="flex justify-between text-gray-700">
                  <span>Mainboard</span>
                  <span>{{ formatPrice(pcConfig.mainboard.price) }}</span>
                </div>
                <div v-if="pcConfig.ram" class="flex justify-between text-gray-700">
                  <span>RAM</span>
                  <span>{{ formatPrice(pcConfig.ram.price) }}</span>
                </div>
                <div v-if="pcConfig.vga" class="flex justify-between text-gray-700">
                  <span>VGA</span>
                  <span>{{ formatPrice(pcConfig.vga.price) }}</span>
                </div>
                <div v-if="pcConfig.ssd" class="flex justify-between text-gray-700">
                  <span>SSD</span>
                  <span>{{ formatPrice(pcConfig.ssd.price) }}</span>
                </div>
                <div v-if="pcConfig.psu" class="flex justify-between text-gray-700">
                  <span>PSU</span>
                  <span>{{ formatPrice(pcConfig.psu.price) }}</span>
                </div>
                <div v-if="pcConfig.case" class="flex justify-between text-gray-700">
                  <span>Case</span>
                  <span>{{ formatPrice(pcConfig.case.price) }}</span>
                </div>
              </div>

              <div class="border-t border-gray-200 pt-4">
                <div class="flex justify-between font-semibold text-lg">
                  <span>Tổng</span>
                  <span class="text-black">{{ formatPrice(totalPrice) }}</span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <button
              @click="validateConfig"
              class="w-full px-4 py-3 bg-black text-white rounded-lg font-medium hover:bg-gray-900 transition"
            >
              Kiểm tra tương thích
            </button>

            <button
              @click="getRecommendation"
              :disabled="loading"
              class="w-full px-4 py-3 bg-gray-100 text-gray-900 rounded-lg font-medium hover:bg-gray-200 transition disabled:opacity-50"
            >
              {{ loading ? 'Đang tải...' : 'Đề xuất từ AI' }}
            </button>

            <button
              @click="addAllToCart"
              :disabled="totalPrice === 0"
              class="w-full px-4 py-3 bg-black text-white rounded-lg font-medium hover:bg-gray-900 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Thêm vào giỏ hàng
            </button>

            <router-link to="/checkout-new" class="w-full inline-block">
              <button class="w-full px-4 py-3 bg-gray-100 text-gray-900 rounded-lg font-medium hover:bg-gray-200 transition text-center">
                Đi đến thanh toán
              </button>
            </router-link>
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
