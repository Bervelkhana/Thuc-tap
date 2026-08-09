<script setup>
import { ref, computed, onMounted } from 'vue'

const orders = ref([])
const products = ref([])
const loading = ref(false)
const selectedStatus = ref(null)
const searchQuery = ref('')
const selectedOrder = ref(null)
const showDetails = ref(false)

const statusOptions = [
  { value: 'pending', label: '⏳ Đang chờ', color: 'yellow' },
  { value: 'confirmed', label: '✅ Đã xác nhận', color: 'blue' },
  { value: 'shipped', label: '🚚 Đang giao', color: 'purple' },
  { value: 'delivered', label: '📦 Đã giao', color: 'green' },
  { value: 'cancelled', label: '❌ Đã huỷ', color: 'red' },
]

const filteredOrders = computed(() => {
  let result = orders.value

  if (selectedStatus.value) {
    result = result.filter(o => o.status === selectedStatus.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(o =>
      o.id.toString().includes(query) ||
      (o.customer_name && o.customer_name.toLowerCase().includes(query))
    )
  }

  return result.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

async function fetchOrders() {
  loading.value = true
  try {
    const response = await fetch('/api/admin/orders?per_page=100')
    const result = await response.json()
    if (result.status === 'success') {
      orders.value = result.data
    }
  } catch (err) {
    console.error('Error fetching orders:', err)
    alert('Lỗi khi tải đơn hàng')
  } finally {
    loading.value = false
  }
}

function viewDetails(order) {
  selectedOrder.value = order
  showDetails.value = true
}

function getStatusInfo(status) {
  return statusOptions.find(s => s.value === status)
}

function getStatusBadgeClass(status) {
  const colorMap = {
    yellow: 'bg-yellow-100 text-yellow-800',
    blue: 'bg-blue-100 text-blue-800',
    purple: 'bg-purple-100 text-purple-800',
    green: 'bg-green-100 text-green-800',
    red: 'bg-red-100 text-red-800',
  }
  return colorMap[getStatusInfo(status)?.color] || 'bg-gray-100 text-gray-800'
}

async function updateStatus(orderId, newStatus) {
   if (!confirm(`Bạn chắc chắn muốn đổi trạng thái sang "${newStatus}"?`)) return

   loading.value = true
   try {
     const response = await fetch(`/api/admin/orders/${orderId}/status`, {
       method: 'PATCH',
       headers: { 'Content-Type': 'application/json' },
       body: JSON.stringify({ status: newStatus })
     })

     const result = await response.json()

     if (result.status === 'success') {
       alert('Cập nhật trạng thái thành công')
       await fetchOrders()
       if (selectedOrder.value?.id === orderId) {
         selectedOrder.value = result.data
       }
     } else {
       alert(result.message || 'Lỗi khi cập nhật trạng thái')
     }
   } catch (err) {
     console.error('Error updating status:', err)
     alert('Lỗi khi cập nhật trạng thái')
   } finally {
     loading.value = false
   }
}

async function deleteOrder(orderId) {
   if (!confirm('Bạn chắc chắn muốn xóa đơn hàng này? Hành động này không thể hoàn tác.')) return

   loading.value = true
   try {
     const response = await fetch(`/api/admin/orders/${orderId}`, {
       method: 'DELETE',
       headers: { 'Content-Type': 'application/json' }
     })

     const result = await response.json()

     if (result.status === 'success') {
       alert('Xóa đơn hàng thành công')
       showDetails.value = false
       await fetchOrders()
     } else {
       alert(result.message || 'Lỗi khi xóa đơn hàng')
     }
   } catch (err) {
     console.error('Error deleting order:', err)
     alert('Lỗi khi xóa đơn hàng')
   } finally {
     loading.value = false
   }
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleString('vi-VN')
}

function formatPrice(price) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
}

const stats = computed(() => ({
  total: orders.value.length,
  pending: orders.value.filter(o => o.status === 'pending').length,
  confirmed: orders.value.filter(o => o.status === 'confirmed').length,
  shipped: orders.value.filter(o => o.status === 'shipped').length,
  delivered: orders.value.filter(o => o.status === 'delivered').length,
  cancelled: orders.value.filter(o => o.status === 'cancelled').length,
}))

onMounted(() => {
  fetchOrders()
})
</script>

<template>
  <div class="space-y-6">
    <!-- HEADER -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900">📋 Quản lý đơn hàng</h2>
    </div>

    <!-- STATS -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
      <div class="bg-white rounded-lg p-4 border border-gray-200 text-center">
        <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
        <p class="text-xs text-gray-600">Tổng đơn</p>
      </div>
      <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200 text-center">
        <p class="text-2xl font-bold text-yellow-700">{{ stats.pending }}</p>
        <p class="text-xs text-yellow-600">Đang chờ</p>
      </div>
      <div class="bg-blue-50 rounded-lg p-4 border border-blue-200 text-center">
        <p class="text-2xl font-bold text-blue-700">{{ stats.confirmed }}</p>
        <p class="text-xs text-blue-600">Đã xác nhận</p>
      </div>
      <div class="bg-purple-50 rounded-lg p-4 border border-purple-200 text-center">
        <p class="text-2xl font-bold text-purple-700">{{ stats.shipped }}</p>
        <p class="text-xs text-purple-600">Đang giao</p>
      </div>
      <div class="bg-green-50 rounded-lg p-4 border border-green-200 text-center">
        <p class="text-2xl font-bold text-green-700">{{ stats.delivered }}</p>
        <p class="text-xs text-green-600">Đã giao</p>
      </div>
      <div class="bg-red-50 rounded-lg p-4 border border-red-200 text-center">
        <p class="text-2xl font-bold text-red-700">{{ stats.cancelled }}</p>
        <p class="text-xs text-red-600">Đã huỷ</p>
      </div>
    </div>

    <!-- FILTERS -->
    <div class="bg-white rounded-lg p-4 border border-gray-200 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tìm kiếm theo ID hoặc tên khách hàng..."
          class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black"
        />
        <select
          v-model="selectedStatus"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-black"
        >
          <option :value="null">Tất cả trạng thái</option>
          <option v-for="status in statusOptions" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>
      </div>
      <p class="text-sm text-gray-600">Tìm thấy {{ filteredOrders.length }} đơn hàng</p>
    </div>

    <!-- ORDERS TABLE -->
    <div v-if="!loading" class="overflow-x-auto">
      <table class="w-full border-collapse">
        <thead>
          <tr class="bg-gray-100 border-b-2 border-gray-300">
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">ID</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Khách hàng</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Ngày đặt</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Tổng tiền</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Trạng thái</th>
            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-900">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="order in filteredOrders"
            :key="order.id"
            class="border-b border-gray-200 hover:bg-gray-50 transition"
          >
            <td class="px-4 py-3 text-sm font-semibold text-gray-900">#{{ order.id }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">
              <div class="font-medium">{{ order.customer_name || 'N/A' }}</div>
              <div class="text-xs text-gray-500">{{ order.customer_email || 'N/A' }}</div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
              {{ formatDate(order.created_at) }}
            </td>
            <td class="px-4 py-3 text-sm font-semibold">
              {{ formatPrice(order.total_amount) }}
            </td>
            <td class="px-4 py-3">
              <span :class="['px-3 py-1 rounded-lg text-xs font-semibold inline-block', getStatusBadgeClass(order.status)]">
                {{ getStatusInfo(order.status)?.label }}
              </span>
            </td>
            <td class="px-4 py-3 text-center space-x-2">
              <button
                @click="viewDetails(order)"
                class="px-3 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 transition"
              >
                👁️ Xem
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="filteredOrders.length === 0" class="text-center py-8 text-gray-600">
        Không tìm thấy đơn hàng
      </div>
    </div>

    <div v-else class="text-center py-8 text-gray-600">
      Đang tải...
    </div>

    <!-- ORDER DETAILS MODAL -->
    <div v-if="showDetails && selectedOrder" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
          <h3 class="text-lg font-bold text-gray-900">
            📦 Chi tiết đơn hàng #{{ selectedOrder.id }}
          </h3>
          <button @click="showDetails = false" class="text-2xl text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <div class="p-6 space-y-6">
          <!-- ORDER INFO -->
          <div class="border-b border-gray-200 pb-4">
            <h4 class="font-semibold text-gray-900 mb-3">Thông tin đơn hàng</h4>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <p class="text-gray-600">Khách hàng</p>
                <p class="font-medium text-gray-900">{{ selectedOrder.customer_name }}</p>
              </div>
              <div>
                <p class="text-gray-600">Email</p>
                <p class="font-medium text-gray-900">{{ selectedOrder.customer_email }}</p>
              </div>
              <div>
                <p class="text-gray-600">Ngày đặt</p>
                <p class="font-medium text-gray-900">{{ formatDate(selectedOrder.created_at) }}</p>
              </div>
              <div>
                <p class="text-gray-600">Tổng tiền</p>
                <p class="font-medium text-lg text-gray-900">{{ formatPrice(selectedOrder.total_amount) }}</p>
              </div>
            </div>
          </div>

          <!-- ITEMS -->
          <div v-if="selectedOrder.items && selectedOrder.items.length > 0" class="border-b border-gray-200 pb-4">
            <h4 class="font-semibold text-gray-900 mb-3">Sản phẩm</h4>
            <div class="space-y-2">
              <div
                v-for="item in selectedOrder.items"
                :key="item.id"
                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
              >
                <div>
                  <p class="font-medium text-gray-900">{{ item.product_name || item.product?.name }}</p>
                  <p class="text-xs text-gray-600">SL: {{ item.quantity }} x {{ formatPrice(item.price) }}</p>
                </div>
                <p class="font-semibold text-gray-900">{{ formatPrice(item.price * item.quantity) }}</p>
              </div>
            </div>
          </div>

          <!-- STATUS UPDATE -->
          <div class="border-b border-gray-200 pb-4">
            <h4 class="font-semibold text-gray-900 mb-3">
              <span :class="['px-3 py-1 rounded-lg text-xs font-semibold inline-block mb-3', getStatusBadgeClass(selectedOrder.status)]">
                {{ getStatusInfo(selectedOrder.status)?.label }}
              </span>
            </h4>
            <div class="space-y-2">
              <button
                v-for="status in statusOptions"
                :key="status.value"
                @click="updateStatus(selectedOrder.id, status.value)"
                :disabled="loading || selectedOrder.status === status.value"
                :class="[
                  'block w-full px-4 py-2 rounded-lg text-sm font-medium transition text-left',
                  selectedOrder.status === status.value
                    ? 'bg-gray-200 text-gray-600 cursor-not-allowed'
                    : 'bg-gray-100 text-gray-900 hover:bg-gray-200'
                ]"
              >
                {{ status.label }}
              </button>
            </div>
          </div>

          <!-- CLOSE BUTTON -->
          <div class="flex gap-3">
            <button
              @click="deleteOrder(selectedOrder.id)"
              :disabled="loading"
              class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium disabled:opacity-50"
            >
              🗑️ Xóa đơn hàng
            </button>
            <button
              @click="showDetails = false"
              class="flex-1 px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-900 transition font-medium"
            >
              Đóng
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
