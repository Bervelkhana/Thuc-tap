<script setup>
import { ref, computed, onMounted, watch } from 'vue'

const products = ref([])
const categories = ref([])
const loading = ref(false)
const showForm = ref(false)
const isEditing = ref(false)
const searchQuery = ref('')
const selectedCategory = ref(null)

const currentPage = ref(1)
const perPage = ref(15)
const total = ref(0)
const lastPage = ref(1)

const formData = ref({
  id: null,
  name: '',
  sku: '',
  price: '',
  stock_quantity: '',
  discount_percentage: '',
  category_id: '',
  description: '',
  thumbnail_url: '',
})

const filteredProducts = computed(() => products.value)

const pages = computed(() => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
  let end = Math.min(lastPage.value, start + maxVisible - 1)

  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

async function fetchProducts(page = 1) {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: String(page),
      per_page: String(perPage.value),
    })
    if (searchQuery.value) params.set('search', searchQuery.value)
    if (selectedCategory.value) params.set('category_id', String(selectedCategory.value))

    const response = await fetch(`/api/products?${params.toString()}`)
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const result = await response.json()
    if (result.status === 'success') {
      products.value = Array.isArray(result.data) ? result.data : result.data.data || []
      currentPage.value = result.meta?.current_page ?? page
      perPage.value = result.meta?.per_page ?? perPage.value
      total.value = result.meta?.total ?? 0
      lastPage.value = result.meta?.last_page ?? 1
    } else {
      products.value = []
    }
  } catch (err) {
    console.error('Error fetching products:', err)
    alert(`Lỗi khi tải sản phẩm: ${err.message}`)
  } finally {
    loading.value = false
  }
}

async function fetchCategories() {
  try {
    const response = await fetch('/api/categories')
    const result = await response.json()
    if (result.status === 'success') {
      categories.value = result.data
    }
  } catch (err) {
    console.error('Error fetching categories:', err)
  }
}

function goToPage(page) {
  if (page < 1 || page > lastPage.value) return
  fetchProducts(page)
}

watch(searchQuery, () => goToPage(1))
watch(selectedCategory, () => goToPage(1))

function openAddForm() {
  isEditing.value = false
  formData.value = {
    id: null,
    name: '',
    sku: '',
    price: '',
    stock_quantity: '',
    discount_percentage: '',
    category_id: '',
    description: '',
    thumbnail_url: '',
  }
  showForm.value = true
}

function openEditForm(product) {
  isEditing.value = true
  formData.value = { ...product }
  showForm.value = true
}

async function saveProduct() {
  if (!formData.value.name || !formData.value.sku || !formData.value.price || !formData.value.category_id) {
    alert('Vui lòng điền các trường bắt buộc')
    return
  }

  loading.value = true
  try {
    const url = isEditing.value
      ? `/api/products/${formData.value.id}`
      : '/api/products'

    const method = isEditing.value ? 'PUT' : 'POST'

    const response = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: formData.value.name,
        sku: formData.value.sku,
        price: formData.value.price,
        stock_quantity: formData.value.stock_quantity,
        discount_percentage: formData.value.discount_percentage ? Number(formData.value.discount_percentage) : 0,
        category_id: formData.value.category_id,
        description: formData.value.description,
        thumbnail_url: formData.value.thumbnail_url,
      })
    })

    const result = await response.json()

    if (result.status === 'success') {
      alert(isEditing.value ? 'Cập nhật sản phẩm thành công' : 'Tạo sản phẩm thành công')
      showForm.value = false
      await fetchProducts(currentPage.value)
    } else {
      alert(result.message || 'Lỗi khi lưu sản phẩm')
    }
  } catch (err) {
    console.error('Error saving product:', err)
    alert('Lỗi khi lưu sản phẩm')
  } finally {
    loading.value = false
  }
}

async function deleteProduct(id, name) {
  if (!confirm(`Bạn chắc chắn muốn xóa "${name}"?`)) return

  loading.value = true
  try {
    const response = await fetch(`/api/products/${id}`, { method: 'DELETE' })
    const result = await response.json()

    if (result.status === 'success') {
      alert('Xóa sản phẩm thành công')
      await fetchProducts(currentPage.value)
    } else {
      alert(result.message || 'Lỗi khi xóa sản phẩm')
    }
  } catch (err) {
    console.error('Error deleting product:', err)
    alert('Lỗi khi xóa sản phẩm')
  } finally {
    loading.value = false
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
}

onMounted(() => {
  fetchProducts()
  fetchCategories()
})
</script>

<template>
  <div class="space-y-6">
    <!-- HEADER -->
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gray-900">📦 Quản lý sản phẩm</h2>
      <button
        @click="openAddForm"
        class="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-900 transition font-medium"
      >
        + Thêm sản phẩm
      </button>
    </div>

    <!-- FILTERS -->
    <div class="bg-white rounded-lg p-4 border border-gray-200 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tìm kiếm theo tên hoặc SKU..."
          class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black"
        />
        <select
          v-model="selectedCategory"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black"
        >
          <option :value="null">Tất cả danh mục</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>
      </div>
      <p class="text-sm text-gray-600">Tìm thấy {{ total }} sản phẩm</p>
    </div>

    <!-- PRODUCTS TABLE -->
    <div v-if="!loading" class="overflow-x-auto">
      <table class="w-full border-collapse">
        <thead>
          <tr class="bg-gray-100 border-b-2 border-gray-300">
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">ID</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Sản phẩm</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">SKU</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Giá</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Tồn kho</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Danh mục</th>
            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-900">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="product in filteredProducts"
            :key="product.id"
            class="border-b border-gray-200 hover:bg-gray-50 transition"
          >
            <td class="px-4 py-3 text-sm text-gray-900">{{ product.id }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">{{ product.name }}</td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ product.sku }}</td>
            <td class="px-4 py-3 text-sm font-semibold">{{ formatPrice(product.price) }}</td>
            <td class="px-4 py-3 text-sm">
              <span :class="[
                'px-3 py-1 rounded-lg text-xs font-semibold',
                product.stock_quantity > 0
                  ? 'bg-green-100 text-green-800'
                  : 'bg-red-100 text-red-800'
              ]">
                {{ product.stock_quantity }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
              {{ categories.find(c => c.id === product.category_id)?.name || 'N/A' }}
            </td>
            <td class="px-4 py-3 text-center space-x-2">
              <button
                @click="openEditForm(product)"
                class="px-3 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 transition"
              >
                ✏️ Sửa
              </button>
              <button
                @click="deleteProduct(product.id, product.name)"
                class="px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 transition"
              >
                🗑️ Xóa
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="filteredProducts.length === 0" class="text-center py-8 text-gray-600">
        Không tìm thấy sản phẩm
      </div>
    </div>

    <div v-else class="text-center py-8 text-gray-600">
      Đang tải...
    </div>

    <!-- PAGINATION -->
    <div v-if="lastPage > 1" class="flex items-center justify-center gap-2">
      <button
        @click="goToPage(currentPage - 1)"
        :disabled="currentPage === 1"
        class="px-3 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
      >
        ← Trước
      </button>

      <button
        v-for="p in pages"
        :key="p"
        @click="goToPage(p)"
        :class="[
          'px-3 py-2 border rounded-lg min-w-[40px]',
          p === currentPage
            ? 'bg-black text-white border-black'
            : 'border-gray-300 hover:bg-gray-50'
        ]"
      >
        {{ p }}
      </button>

      <button
        @click="goToPage(currentPage + 1)"
        :disabled="currentPage === lastPage"
        class="px-3 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
      >
        Sau →
      </button>
    </div>

    <!-- FORM MODAL -->
    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
          <h3 class="text-lg font-bold text-gray-900">
            {{ isEditing ? '✏️ Sửa sản phẩm' : '➕ Thêm sản phẩm' }}
          </h3>
          <button @click="showForm = false" class="text-2xl text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <div class="p-6 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tên sản phẩm *</label>
              <input
                v-model="formData.name"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black"
                placeholder="VD: Intel Core i7"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">SKU *</label>
              <input
                v-model="formData.sku"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black"
                placeholder="VD: CPU-INTEL-I7"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Giá (VND) *</label>
              <input
                v-model="formData.price"
                type="number"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black"
                placeholder="0"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tồn kho *</label>
              <input
                v-model="formData.stock_quantity"
                type="number"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black"
                placeholder="0"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Giảm giá (%)</label>
              <input
                v-model="formData.discount_percentage"
                type="number"
                min="0"
                max="100"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black"
                placeholder="0"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Danh mục *</label>
              <select
                v-model="formData.category_id"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black"
              >
                <option value="">-- Chọn danh mục --</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
            <textarea
              v-model="formData.description"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black"
              placeholder="Mô tả chi tiết sản phẩm..."
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">URL ảnh sản phẩm</label>
            <input
              v-model="formData.thumbnail_url"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black"
              placeholder="https://..."
            />
          </div>

          <div class="flex gap-4 pt-6">
            <button
              @click="saveProduct"
              :disabled="loading"
              class="flex-1 px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-900 transition font-medium disabled:opacity-50"
            >
              {{ loading ? 'Đang lưu...' : (isEditing ? 'Cập nhật' : 'Thêm') }}
            </button>
            <button
              @click="showForm = false"
              class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition font-medium"
            >
              Huỷ
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
