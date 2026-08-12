<script setup>
import { ref, computed, onMounted } from 'vue'

const configs = ref([])
const loading = ref(false)
const showForm = ref(false)
const isEditing = ref(false)
const searchQuery = ref('')
const formErrors = ref({})

// ─── Component definitions ────────────────────────────────────────────
const COMPONENTS = [
  { key: 'cpu',        label: 'CPU',         slug: 'cpu',        placeholder: 'Nhập tên CPU để tìm kiếm...' },
  { key: 'mainboard',  label: 'Mainboard',   slug: 'mainboard',  placeholder: 'Nhập tên Mainboard để tìm kiếm...' },
  { key: 'ram',        label: 'RAM',         slug: 'ram',        placeholder: 'Nhập tên RAM để tìm kiếm...' },
  { key: 'vga',        label: 'VGA',         slug: 'vga',        placeholder: 'Nhập tên VGA để tìm kiếm...' },
  { key: 'ssd',        label: 'SSD',         slug: 'ssd',        placeholder: 'Nhập tên SSD để tìm kiếm...' },
  { key: 'psu',        label: 'PSU (Nguồn)', slug: 'psu',        placeholder: 'Nhập tên PSU để tìm kiếm...' },
  { key: 'case',       label: 'Case (Vỏ)',   slug: 'case',       placeholder: 'Nhập tên Case để tìm kiếm...' },
]

const icons = {
  cpu:    '🧠',
  mainboard: '🔲',
  ram:    '💾',
  vga:    '🎮',
  ssd:    '⚡',
  psu:    '🔌',
  case:   '🖥️',
}

// ─── selectedComponents: one product object per component slot ────────
const selectedComponents = ref({})
COMPONENTS.forEach(c => { selectedComponents.value[c.key] = null })

// ─── Per-component search state ───────────────────────────────────────
const searchState = ref({})
COMPONENTS.forEach(c => {
  searchState.value[c.key] = {
    query: '',
    results: [],
    open: false,
    loading: false,
  }
})

// ─── Click-outside: close any open dropdown ───────────────────────────
function closeAllDropdowns() {
  COMPONENTS.forEach(c => {
    searchState.value[c.key].open = false
    searchState.value[c.key].results = []
  })
}

function handleClickOutside(e) {
  if (!showForm.value) return
  const el = e.target.closest('[data-search-block]')
  if (!el) {
    closeAllDropdowns()
  }
}

// ─── fetchConfigs / formData ──────────────────────────────────────────
const formData = ref({
  id: null, name: '', slug: '', price: '', description: '',
  thumbnail_url: '', is_featured: false, is_active: true, sort_order: 0,
})

const filteredConfigs = computed(() => {
  if (!searchQuery.value) return configs.value
  const q = searchQuery.value.toLowerCase()
  return configs.value.filter((item) => item.name.toLowerCase().includes(q))
})

const totalPrice = computed(() => {
  let total = 0
  Object.values(selectedComponents.value).forEach(p => {
    if (p) total += parseFloat(p.price) || 0
  })
  return total
})

const selectedCount = computed(() => {
  return Object.values(selectedComponents.value).filter(Boolean).length
})

// ─── Async search per component with debounce ─────────────────────────
async function searchProduct(componentKey, query) {
  const state = searchState.value[componentKey]
  if (!state) return

  // Only fire search when there is at least 1 character typed
  if (!query || query.trim() === '') {
    state.results = []
    state.loading = false
    return
  }

  const trimmedQ = query.trim()

  state.loading = true
  state.open = true

  try {
    const catInfo = COMPONENTS.find(c => c.key === componentKey)

    console.log(`[Search ${componentKey}] Calling API with:`, {
      category_slug: catInfo.slug,
      q: trimmedQ,
    })

    const url = `/api/products/search?category_slug=${encodeURIComponent(catInfo.slug)}&q=${encodeURIComponent(trimmedQ)}&per_page=10`

    console.log(`[Search ${componentKey}] Full URL:`, url)

    const response = await fetch(url, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })

    console.log(`[Search ${componentKey}] Response status:`, response.status)

    if (!response.ok) throw new Error(`HTTP ${response.status}`)

    const result = await response.json()

    console.log(`[Search ${componentKey}] Raw JSON response:`, result)

    // Flexible parsing — handle multiple response shapes
    let products = []
    if (Array.isArray(result.data)) {
      products = result.data
    } else if (result.data && Array.isArray(result.data.data)) {
      products = result.data.data
    } else if (result.data && Array.isArray(result.data.items)) {
      products = result.data.items
    } else if (Array.isArray(result)) {
      products = result
    }

    console.log(`[Search ${componentKey}] Extracted ${products.length} products`, products)

    state.results = products
    state.loading = false
  } catch (err) {
    console.error(`[Search ${componentKey}] Error:`, err.message)
    state.results = []
    state.loading = false
  }
}

function debouncedSearch(componentKey, query) {
  clearTimeout(searchState.value[componentKey]._timer)
  searchState.value[componentKey]._timer = setTimeout(() => {
    searchProduct(componentKey, query)
  }, 350)
}

function selectProduct(componentKey, product) {
  selectedComponents.value[componentKey] = product
  searchState.value[componentKey].query = ''
  searchState.value[componentKey].results = []
  searchState.value[componentKey].open = false
}

function deselectProduct(componentKey) {
  selectedComponents.value[componentKey] = null
}

function toggleDropdown(componentKey) {
  const s = searchState.value[componentKey]
  s.open = !s.open
  if (!s.open) {
    s.results = []
  } else if (!s.query) {
    s.results = []
  }
}

// ─── Form helpers ─────────────────────────────────────────────────────
function validateForm() {
  formErrors.value = {}

  if (!formData.value.name || formData.value.name.trim() === '') {
    formErrors.value.name = 'Tên cấu hình không được để trống'
  }

  if (selectedCount.value === 0) {
    formErrors.value.components = 'Phải chọn ít nhất 1 sản phẩm'
  }

  return Object.keys(formErrors.value).length === 0
}

function openAddForm() {
  isEditing.value = false
  formData.value = {
    id: null, name: '', slug: '', price: '', description: '',
    thumbnail_url: '', is_featured: false, is_active: true, sort_order: 0,
  }
  formErrors.value = {}
  COMPONENTS.forEach(c => {
    selectedComponents.value[c.key] = null
    searchState.value[c.key].query = ''
    searchState.value[c.key].results = []
    searchState.value[c.key].open = false
  })
  showForm.value = true
}

function openEditForm(config) {
  isEditing.value = true
  COMPONENTS.forEach(c => {
    searchState.value[c.key].query = ''
    searchState.value[c.key].results = []
    searchState.value[c.key].open = false
  })

  config.products?.forEach(p => {
    const comp = COMPONENTS.find(c => c.key === p.component_key || (p.category && p.category.slug === c.slug))
    if (comp) {
      selectedComponents.value[comp.key] = p
    } else {
      // Fallback: match by category_id
      const slugKey = getCategoryKeyFromName(p.category?.name)
      if (slugKey) selectedComponents.value[slugKey] = p
    }
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
  }
  formErrors.value = {}
  showForm.value = true
}

function getCategoryKeyFromName(categoryName) {
  const map = {
    'CPU': 'cpu', 'Mainboard': 'mainboard', 'RAM': 'ram',
    'VGA': 'vga', 'SSD': 'ssd', 'HDD': 'ssd',
    'PSU': 'psu', 'Case': 'case',
  }
  return map[categoryName] || null
}

async function saveConfig() {
  if (!validateForm()) return

  loading.value = true
  try {
    const productIds = []
    const productQuantities = {}

    Object.entries(selectedComponents.value).forEach(([key, product]) => {
      if (product) {
        productIds.push(product.id)
        productQuantities[product.id] = 1
      }
    })

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
        product_ids: productIds,
        product_quantities: productQuantities,
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
    const response = await fetch(`/api/prebuilt-configs/${id}`, { method: 'DELETE' })
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
    const response = await fetch(`/api/prebuilt-configs/${id}/toggle-active`, { method: 'PATCH' })
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
    const response = await fetch(`/api/prebuilt-configs/${id}/toggle-featured`, { method: 'PATCH' })
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

// ─── Lifecycle ────────────────────────────────────────────────────────
onMounted(() => {
  fetchConfigs()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">🧩 Quản lý cấu hình xây sẵn</h2>
        <p class="text-sm text-gray-500 mt-1">Tạo và quản lý các cấu hình được hiển thị trên trang Cấu hình xây sẵn</p>
      </div>
      <button @click="openAddForm" class="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-900 transition font-medium">
        + Thêm cấu hình
      </button>
    </div>

    <!-- Search configs -->
    <div class="bg-white rounded-lg p-4 border border-gray-200">
      <input v-model="searchQuery" type="text" placeholder="Tìm kiếm cấu hình..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black" />
    </div>

    <!-- Configs table -->
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

      <div v-if="filteredConfigs.length === 0" class="text-center py-8 text-gray-600">Không tìm thấy cấu hình</div>
    </div>
    <div v-else class="text-center py-8 text-gray-600">Đang tải...</div>

    <!-- ════════════════════════════════════════════════════════ -->
    <!-- MODAL: Add / Edit Prebuilt Config                        -->
    <!-- ════════════════════════════════════════════════════════ -->
    <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @mousedown="handleClickOutside" @scroll.stop>
      <!-- Prevent background scroll when modal is open -->
      <div class="bg-white rounded-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-2xl"
           @click.stop
           @mousedown.stop>
        <!-- Modal header -->
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
          <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? '✏️ Sửa cấu hình' : '➕ Thêm cấu hình xây sẵn' }}</h3>
          <button @click="showForm = false" class="text-2xl text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <div class="p-6 space-y-5">
          <!-- Basic info -->
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

          <!-- Price / Sort order / Thumbnail URL -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Giá tính tự động</label>
              <div class="px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700 font-semibold">
                {{ formatPrice(totalPrice) }}
              </div>
              <p class="text-xs text-gray-500 mt-0.5">Dựa trên sản phẩm đã chọn</p>
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

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
            <textarea v-model="formData.description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black" placeholder="Mô tả chi tiết cấu hình..."></textarea>
          </div>

          <!-- ═══════════════════════════════════════════════════ -->
          <!-- Product Selection (Inline Dropdown Version)         -->
          <!-- ═══════════════════════════════════════════════════ -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Chọn sản phẩm cấu thành *</label>
            <div v-if="formErrors.components" class="mb-2 text-xs text-red-600 bg-red-50 px-3 py-2 rounded">{{ formErrors.components }}</div>
            <div v-if="selectedCount > 0" class="mb-2 text-xs text-gray-500">Đã chọn {{ selectedCount }}/{{ COMPONENTS.length }} linh kiện</div>

            <div class="space-y-3 border border-gray-200 rounded-xl p-4 bg-gray-50 max-h-[60vh] overflow-y-auto">
              <div v-for="comp in COMPONENTS" :key="comp.key" data-search-block
                   class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <!-- Block header -->
                <div class="flex items-center gap-2 border-b border-gray-100 bg-gray-50 px-4 py-2.5">
                  <span class="text-lg">{{ icons[comp.key] || '📦' }}</span>
                  <span class="font-semibold text-gray-900 text-sm">{{ comp.label }}</span>
                </div>

                <div class="p-4">
                  <!-- Selected badge -->
                  <div v-if="selectedComponents[comp.key]" class="mb-3">
                    <div class="flex items-center justify-between gap-2 p-3 rounded-lg bg-emerald-50 border border-emerald-200">
                      <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ selectedComponents[comp.key].name }}</p>
                        <p class="text-xs text-gray-500">{{ formatPrice(selectedComponents[comp.key].price) }}</p>
                      </div>
                      <button @click="deselectProduct(comp.key)"
                              class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full bg-red-100 text-red-600 hover:bg-red-200 text-sm font-bold transition"
                              title="Bỏ chọn">
                        ✕
                      </button>
                    </div>
                  </div>

                  <!-- Search area (only shown when nothing selected) -->
                  <div v-else>
                    <!-- Input -->
                    <input
                      v-model="searchState[comp.key].query"
                      @focus="toggleDropdown(comp.key)"
                      @input="debouncedSearch(comp.key, $event.target.value)"
                      type="text"
                      :placeholder="comp.placeholder"
                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-black text-sm"
                    />

                    <!-- Loading indicator inside input area -->
                    <div v-if="searchState[comp.key].loading" class="mt-1 text-xs text-gray-400 pl-1">
                      Đang tìm kiếm...
                    </div>

                    <!-- Inline dropdown results (NOT absolute — avoids overflow clipping) -->
                    <div v-if="searchState[comp.key].open && searchState[comp.key].results.length > 0 && !searchState[comp.key].loading"
                         class="mt-1 border border-gray-200 rounded-lg bg-white shadow-sm max-h-48 overflow-y-auto">
                      <div
                        v-for="product in searchState[comp.key].results"
                        :key="product.id"
                        @click="selectProduct(comp.key, product)"
                        class="flex items-center justify-between gap-2 px-4 py-2.5 cursor-pointer hover:bg-gray-50 transition border-b border-gray-100 last:border-b-0"
                      >
                        <div class="min-w-0 flex-1">
                          <p class="text-sm text-gray-900 truncate">{{ product.name }}</p>
                          <p class="text-xs text-gray-500">{{ formatPrice(product.price) }}</p>
                        </div>
                        <span class="shrink-0 text-xs text-gray-400">Chọn</span>
                      </div>
                    </div>

                    <!-- No results message -->
                    <div v-if="searchState[comp.key].open && !searchState[comp.key].loading && searchState[comp.key].query && searchState[comp.key].results.length === 0"
                         class="mt-1 border border-gray-200 rounded-lg bg-white px-4 py-3 text-sm text-gray-500 text-center">
                      Không tìm thấy kết quả nào cho "{{ searchState[comp.key].query }}"
                    </div>

                    <!-- Hint when input is focused but no query yet -->
                    <div v-if="searchState[comp.key].open && !searchState[comp.key].loading && !searchState[comp.key].query"
                         class="mt-1 text-xs text-gray-400 px-1">
                      Gõ từ khóa để tìm kiếm sản phẩm {{ comp.label }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- ═══════════════════════════════════════════════════ -->

          <!-- Toggle flags -->
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

          <!-- Actions -->
          <div class="flex gap-4 pt-4 border-t border-gray-200">
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
