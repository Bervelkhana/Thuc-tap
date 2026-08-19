<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCartStore } from '../stores/cartStore'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()

const config = ref(null)
const loading = ref(false)
const selectedQuantity = ref(1)
const outOfStockMessage = ref('')

const categoryOrder = [
  { id: 8, name: 'CPU', icon: '🖥️' },
  { id: 9, name: 'MAIN', icon: '🔌' },
  { id: 10, name: 'RAM', icon: '💾' },
  { id: 11, name: 'SSD', icon: '💿' },
  { id: 12, name: 'VGA', icon: '🎮' },
  { id: 13, name: 'Case', icon: '📦' },
  { id: 14, name: 'Cooler', icon: '❄️' },
  { id: 15, name: 'PSU', icon: '⚡' },
]

function getProductsByCategory(products, categoryId) {
  if (!products?.length) return []
  return products.filter(p => p.category_id === categoryId)
}

async function fetchConfig() {
  loading.value = true
  try {
    const response = await fetch(`/api/prebuilt-configs/${route.params.id}`)
    const result = await response.json()
    if (result.status === 'success') {
      config.value = {
        ...result.data,
        price: parseFloat(result.data.price),
      }
    }
  } catch (err) {
    console.error('Error fetching config:', err)
  } finally {
    loading.value = false
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
}

function canAddToCart() {
  if (!config.value?.products?.length) return false

  const outOfStockProducts = []
  
  for (const product of config.value.products) {
    const requiredQty = (product.pivot?.quantity || 1) * selectedQuantity.value
    if (product.stock_quantity < requiredQty) {
      outOfStockProducts.push(`${product.name} (cần ${requiredQty}, còn ${product.stock_quantity})`)
    }
  }

  if (outOfStockProducts.length > 0) {
    outOfStockMessage.value = 'Không đủ tồn kho:\n' + outOfStockProducts.join('\n')
    return false
  }

  outOfStockMessage.value = ''
  return true
}

function addWholeBundleToCart() {
  if (!canAddToCart()) return

  config.value.products.forEach((product) => {
    cartStore.addToCart(
      {
        id: product.id,
        name: product.name,
        price: parseFloat(product.price),
        thumbnail_url: product.thumbnail_url,
      },
      (product.pivot?.quantity || 1) * selectedQuantity.value,
    )
  })

  router.push('/checkout-new')
}

onMounted(fetchConfig)
</script>

<template>
  <div class="min-h-screen bg-white font-system">
    <div v-if="loading" class="max-w-5xl mx-auto px-4 py-12 text-center text-gray-600">Đang tải cấu hình...</div>

    <div v-else-if="config" class="max-w-5xl mx-auto px-4 sm:px-6 py-8 lg:py-12 space-y-8">
      <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="grid lg:grid-cols-2 gap-0">
          <div class="aspect-[4/3] bg-gray-50 flex items-center justify-center overflow-hidden">
            <img v-if="config.thumbnail_url" :src="config.thumbnail_url" :alt="config.name" class="w-full h-full object-cover" />
            <div v-else class="text-6xl text-gray-400">🧩</div>
          </div>

          <div class="p-6 lg:p-8 space-y-6">
            <div>
              <p class="text-xs uppercase tracking-widest text-gray-500 mb-2">
                {{ config.is_featured ? 'Nổi bật' : 'Cấu hình xây sẵn' }}
              </p>
              <h1 class="text-3xl font-bold text-gray-900">{{ config.name }}</h1>
              <p class="text-gray-600 mt-3 whitespace-pre-wrap">{{ config.description }}</p>
            </div>

            <div class="flex items-center justify-between py-4 border-y border-gray-200">
              <div>
                <p class="text-sm text-gray-600">Giá</p>
                <p class="text-3xl font-bold text-gray-900">{{ formatPrice(config.price) }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm text-gray-600">Trạng thái</p>
                <p class="text-lg font-semibold" :class="config.is_active ? 'text-green-600' : 'text-red-600'">
                  {{ config.is_active ? 'Đang hiển thị' : 'Đã ẩn' }}
                </p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Số bộ muốn mua</label>
              <input v-model.number="selectedQuantity" @change="canAddToCart" type="number" min="1" class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black" />
            </div>

            <div v-if="outOfStockMessage" class="bg-red-50 border border-red-200 rounded-lg p-4">
              <p class="text-sm font-medium text-red-800 whitespace-pre-wrap">{{ outOfStockMessage }}</p>
            </div>

            <div class="flex gap-4">
              <button 
                @click="addWholeBundleToCart" 
                :disabled="!canAddToCart()"
                :class="['flex-1 px-4 py-3 rounded-xl font-medium transition', canAddToCart() ? 'bg-black text-white hover:bg-gray-900' : 'bg-gray-300 text-gray-600 cursor-not-allowed']"
              >
                Thêm cả bộ vào giỏ
              </button>
              <router-link to="/browse-prebuilt" class="px-4 py-3 rounded-xl bg-gray-200 text-gray-900 hover:bg-gray-300 transition font-medium">
                Quay lại
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Sản phẩm cấu thành</h2>
        <div v-if="config.products?.length" class="space-y-6">
          <div v-for="cat in categoryOrder" :key="cat.id" class="space-y-2">
            <template v-for="item in getProductsByCategory(config.products, cat.id)" :key="item.id">
              <div class="flex items-center justify-between rounded-xl border" :class="item.stock_quantity < (item.pivot?.quantity || 1) * selectedQuantity ? 'border-red-200 bg-red-50' : 'border-gray-200'">
                <div class="px-4 py-3">
                  <p class="font-medium text-gray-900">{{ cat.icon }} {{ item.name }}</p>
                  <p class="text-sm text-gray-600">
                    Số lượng cần: {{ (item.pivot?.quantity || 1) * selectedQuantity }} 
                    <span v-if="item.stock_quantity < (item.pivot?.quantity || 1) * selectedQuantity" class="text-red-600 font-semibold"> (Còn: {{ item.stock_quantity }})</span>
                  </p>
                </div>
                <p class="px-4 text-sm font-semibold text-gray-700">{{ formatPrice(item.price) }}</p>
              </div>
            </template>
          </div>
        </div>
        <div v-else class="text-sm text-gray-600 bg-gray-50 rounded-xl p-4">Chưa có sản phẩm cấu thành.</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.font-system {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
}
</style>


