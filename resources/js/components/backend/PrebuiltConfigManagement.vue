<script setup>
import { ref, computed, onMounted } from 'vue'

const configs = ref([])
const products = ref([])
const loading = ref(false)
const showForm = ref(false)
const isEditing = ref(false)
const searchQuery = ref('')
const formErrors = ref({})

const formData = ref({
  id: null,
  name: '',
  slug: '',
  price: '',
  description: '',
  thumbnail_url: '',
  is_featured: false,
  is_active: true,
  sort_order: 0,
  product_ids: [],
  product_quantities: {},
})

const filteredConfigs = computed(() => {
  if (!searchQuery.value) return configs.value
  const query = searchQuery.value.toLowerCase()
  return configs.value.filter((item) => item.name.toLowerCase().includes(query))
})

const totalPrice = computed(() => {
  let total = 0
  formData.value.product_ids.forEach(productId => {
    const product = products.value.find(p => p.id === productId)
    const quantity = formData.value.product_quantities[productId] || 1
    if (product) {
      total += parseFloat(product.price) * quantity
    }
  })
  return total
})

async function fetchConfigs() {
  loading.value = true
  try {
    const response = await fetch('/api/prebuilt-configs?all=true')
    const result = await response.json()
    if (result.status === 'success') {
      configs.value = result.data || []
    }
  } catch (err) {
    console.error('Error fetching prebuilt configs:', err)
    alert('Lỗi khi tải cấu hình xây sẵn')
  } finally {
    loading.value = false
  }
}

async function fetchProducts() {
  try {
    const response = await fetch('/api/products?per_page=1000')
    const result = await response.json()
    if (result.status === 'success') {
      products.value = result.data || []
    }
  } catch (err) {
    console.error('Error fetching products:', err)
  }
}

function validateForm() {
  formErrors.value = {}
  
  if (!formData.value.name || formData.value.name.trim() === '') {
    formErrors.value.name = 'Tên cấu hình không được để trống'
  }
  
  if (formData.value.product_ids.length === 0) {
    formErrors.value.products = 'Phải chọn ít nhất 1 sản phẩm'
  }
  
  for (const productId of formData.value.product_ids) {
    const quantity = formData.value.product_quantities[productId]
    if (!quantity || quantity < 1) {
      formErrors.value[`qty_${productId}`] = 'Số lượng phải >= 1'
    }
  }
  
  return Object.keys(formErrors.value).length === 0
}

function openAddForm() {
  isEditing.value = false
  formData.value = {
    id: null,
    name: '',
    slug: '',
    price: '',
    description: '',
    thumbnail_url: '',
    is_featured: false,
    is_active: true,
    sort_order: 0,
    product_ids: [],
    product_quantities: {},
  }
  formErrors.value = {}
  showForm.value = true
}

function openEditForm(config) {
  isEditing.value = true
  const productQuantities = {}
  config.products?.forEach(p => {
    productQuantities[p.id] = p.pivot?.quantity || 1
  })
  formData.value = {
    id: config.id,
    name: config.name,
    slug: config.slug,
    price: config.price,
    description: config.description,
    thumbnail_url: config.thumbnail_url,
    is_featured: config.is_featured,
    is_active: config.is_active,
    sort_order: config.sort_order,
    product_ids: config.products?.map(p => p.id) || [],
    product_quantities: productQuantities,
  }
  formErrors.value = {}
  showForm.value = true
}

async function saveConfig() {
  if (!validateForm()) {
    return
  }

  loading.value = true
  try {
    const url = isEditing.value
      ? `/api/prebuilt-configs/${formData.value.id}`
      : '/api/prebuilt-configs'
    
    const method = isEditing.value ? 'PUT' : 'POST'

    const response = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: formData.value.name,
        slug: formData.value.slug,
        price: totalPrice.value,
        description: formData.value.description,
        thumbnail_url: formData.value.thumbnail_url,
        is_featured: formData.value.is_featured,
        is_active: formData.value.is_active,
        sort_order: formData.value.sort_order,
        product_ids: formData.value.product_ids,
        product_quantities: formData.value.product_quantities,
      }),
    })

    const result = await response.json()
    if (result.status === 'success') {
      alert(isEditing.value ? 'Cập nhật thành công' : 'Thêm cấu hình xây sẵn thành công')
      showForm.value = false
      await fetchConfigs()
    } else {
      alert(result.message || 'Lỗi khi lưu cấu hình')
    }
  } catch (err) {
    console.error(err)
    alert('Lỗi khi lưu cấu hình')
  } finally {
    loading.value = false
  }
}

async function deleteConfig(id, name) {
  if (!confirm(`Bạn chắc chắn muốn xóa "${name}"?`)) return

  loading.value = true
  try {
    const response = await fetch(`/api/prebuilt-configs/${id}`, {
      method: 'DELETE',
    })
    const result = await response.json()

    if (result.status === 'success') {
      alert('Xóa cấu hình thành công')
      await fetchConfigs()
    } else {
      alert(result.message || 'Lỗi khi xóa cấu hình')
    }
  } catch (err) {
    console.error(err)
    alert('Lỗi khi xóa cấu hình')
  } finally {
    loading.value = false
  }
}

async function toggleActive(id) {
  try {
    const response = await fetch(`/api/prebuilt-configs/${id}/toggle-active`, {
      method: 'PATCH',
    })
    const result = await response.json()

    if (result.status === 'success') {
      alert(result.message)
      await fetchConfigs()
    }
  } catch (err) {
    console.error(err)
    alert('Lỗi khi cập nhật trạng thái')
  }
}

async function toggleFeatured(id) {
  try {
    const response = await fetch(`/api/prebuilt-configs/${id}/toggle-featured`, {
      method: 'PATCH',
    })
    const result = await response.json()

    if (result.status === 'success') {
      alert(result.message)
      await fetchConfigs()
    }
  } catch (err) {
    console.error(err)
    alert('Lỗi khi cập nhật trạng thái')
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
}

function getProductQuantityDetails(config) {
  if (!config.products?.length) return '0 sản phẩm'
  const details = config.products.map(p => `${p.name} (x${p.pivot?.quantity || 1})`).join(', ')
  return details.length > 50 ? details.substring(0, 47) + '...' : details
}

onMounted(() => {
  fetchConfigs()
  fetchProducts()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">🧩 Quản lý cấu hình xây sẵn</h2>
        <p class="text-sm text-gray-500 mt-1">Tạo và quản lý các cấu hình được hiển thị trên trang Cấu hình xây sẵn</p>
      </div>
      <button @click="openAddForm" class="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-900 transition font-medium">
        + Thêm cấu hình
      </button>
    </div>

    <div class="bg-white rounded-lg p-4 border border-gray-200">
      <input v-model="searchQuery" type="text" placeholder="Tìm kiếm cấu hình..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black" />
    </div>

    <div v-if="!loading" class="overflow-x-auto bg-white rounded-lg border border-gray-200">
      <table class="w-full border-collapse">
        <thead>
          <tr class="bg-gray-100 border-b-2 border-gray-300">
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">ID</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Tên</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Giá</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Sản phẩm cấu thành</th>
            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-900">Nổi bật</th>
            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-900">Hiển thị</th>
            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-900">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="config in filteredConfigs" :key="config.id" class="border-b border-gray-200 hover:bg-gray-50 transition">
            <td class="px-4 py-3 text-sm text-gray-900">{{ config.id }}</td>
            <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ config.name }}</td>
            <td class="px-4 py-3 text-sm font-semibold">{{ formatPrice(config.price) }}</td>
            <td class="px-4 py-3 text-xs text-gray-600 max-w-xs truncate" :title="getProductQuantityDetails(config)">
              {{ getProductQuantityDetails(config) }}
            </td>
            <td class="px-4 py-3 text-center">
              <button @click="toggleFeatured(config.id)" :class="['px-2 py-1 text-xs font-semibold rounded transition', config.is_featured ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                {{ config.is_featured ? '⭐ Có' : '✗ Không' }}
              </button>
            </td>
            <td class="px-4 py-3 text-center">
              <button @click="toggleActive(config.id)" :class="['px-2 py-1 text-xs font-semibold rounded transition', config.is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200']">
                {{ config.is_active ? '👁 Hiển thị' : '🙈 Ẩn' }}
              </button>
            </td>
            <td class="px-4 py-3 text-center space-x-2">
              <button @click="openEditForm(config)" class="px-3 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 transition">✏️ Sửa</button>
              <button @click="deleteConfig(config.id, config.name)" class="px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 transition">🗑️ Xóa</button>
            </td>
          </tr>
        </tbody>
      </table>
      
      <div v-if="filteredConfigs.length === 0" class="text-center py-8 text-gray-600">
        Không tìm thấy cấu hình
      </div>
    </div>

    <div v-else class="text-center py-8 text-gray-600">Đang tải...</div>

    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
          <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? '✏️ Sửa cấu hình' : '➕ Thêm cấu hình xây sẵn' }}</h3>
          <button @click="showForm = false" class="text-2xl text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <div class="p-6 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tên cấu hình *</label>
              <input v-model="formData.name" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black" placeholder="VD: Gaming Build Entry" />
              <p v-if="formErrors.name" class="text-xs text-red-600 mt-1">{{ formErrors.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
              <input v-model="formData.slug" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black" placeholder="gaming-build-entry" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Giá tính tự động</label>
              <div class="px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700 font-semibold">
                {{ formatPrice(totalPrice) }}
              </div>
              <p class="text-xs text-gray-500 mt-1">Dựa trên sản phẩm đã chọn</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Thứ tự</label>
              <input v-model.number="formData.sort_order" type="number" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black" placeholder="0" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">URL ảnh</label>
              <input v-model="formData.thumbnail_url" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black" placeholder="https://..." />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
            <textarea v-model="formData.description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black" placeholder="Mô tả chi tiết cấu hình..."></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Chọn sản phẩm cấu thành *</label>
            <div v-if="formErrors.products" class="mb-2 text-xs text-red-600 bg-red-50 px-3 py-2 rounded">{{ formErrors.products }}</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50">
              <div v-for="product in products" :key="product.id" class="flex items-center justify-between gap-2 p-2 bg-white rounded border border-gray-200">
                <label class="flex items-center gap-2 cursor-pointer flex-1">
                  <input v-model="formData.product_ids" :value="product.id" type="checkbox" class="rounded" />
                  <span class="text-sm text-gray-700">{{ product.name }}</span>
                </label>
                <div v-if="formData.product_ids.includes(product.id)" class="flex items-center gap-1">
                  <input v-model.number="formData.product_quantities[product.id]" type="number" min="1" class="w-12 px-2 py-1 text-xs border border-gray-200 rounded focus:outline-none focus:border-black" placeholder="1" />
                  <span class="text-xs text-gray-500">x</span>
                </div>
                <span v-if="formErrors[`qty_${product.id}`]" class="text-xs text-red-600">!</span>
              </div>
            </div>
            <p v-if="formData.product_ids.length" class="text-sm text-gray-500 mt-2">Đã chọn {{ formData.product_ids.length }} sản phẩm</p>
          </div>

          <div class="flex gap-4 pt-2">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="formData.is_featured" type="checkbox" />
              <span class="text-sm text-gray-700">⭐ Đánh dấu nổi bật</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="formData.is_active" type="checkbox" />
              <span class="text-sm text-gray-700">👁 Hiển thị</span>
            </label>
          </div>

          <div class="flex gap-4 pt-6 border-t border-gray-200">
            <button @click="saveConfig" :disabled="loading" class="flex-1 px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-900 transition font-medium disabled:opacity-50">
              {{ loading ? 'Đang lưu...' : (isEditing ? 'Cập nhật' : 'Thêm') }}
            </button>
            <button @click="showForm = false" class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition font-medium">Huỷ</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
