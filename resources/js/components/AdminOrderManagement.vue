<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAdminStore } from '../stores/adminStore'

const adminStore = useAdminStore()
const orders = ref([])
const selectedOrder = ref(null)
const filterStatus = ref('all')
const loading = ref(false)
const error = ref(null)
const isAuthenticated = ref(false)
const authChecking = ref(true)

const statusColors = {
  pending: 'bg-yellow-100 text-yellow-800',
  confirmed: 'bg-blue-100 text-blue-800',
  processing: 'bg-blue-100 text-blue-800',
  shipped: 'bg-purple-100 text-purple-800',
  delivered: 'bg-green-100 text-green-800',
  cancelled: 'bg-red-100 text-red-800',
}

const filteredOrders = computed(() => {
  if (filterStatus.value === 'all') {
    return orders.value
  }
  return orders.value.filter((o) => o.status === filterStatus.value)
})

async function checkAuth() {
  authChecking.value = true
  error.value = null
  try {
    const response = await fetch('/api/admin/me', {
      credentials: 'same-origin',
    })
    if (response.ok) {
      const result = await response.json()
      isAuthenticated.value = result.status === 'success'
    } else {
      isAuthenticated.value = false
    }
  } catch {
    isAuthenticated.value = false
  } finally {
    authChecking.value = false
  }
}

async function fetchOrders() {
  if (!isAuthenticated.value) return
  loading.value = true
  error.value = null
  try {
    const url = filterStatus.value === 'all'
      ? '/api/admin/orders'
      : `/api/admin/orders?status=${filterStatus.value}`

    const headers = { 'Content-Type': 'application/json' }
    if (adminStore.token) {
      headers['Authorization'] = `Bearer ${adminStore.token}`
    }

    const response = await fetch(url, {
      headers,
      credentials: 'same-origin',
    })

    if (response.status === 401 || response.status === 403) {
      adminStore.logout()
      window.location.href = '/login-backend'
      return
    }

    const result = await response.json()
    if (result.status === 'success') {
      orders.value = result.data
    }
  } catch (err) {
    error.value = err.message
    console.error('Lỗi khi tải orders:', err)
  } finally {
    loading.value = false
  }
}

async function updateOrderStatus(orderId, newStatus) {
  try {
    const headers = { 'Content-Type': 'application/json' }
    if (adminStore.token) {
      headers['Authorization'] = `Bearer ${adminStore.token}`
    }

    const response = await fetch(`/api/admin/orders/${orderId}`, {
      method: 'PATCH',
      headers,
      body: JSON.stringify({ status: newStatus }),
      credentials: 'same-origin',
    })

    if (response.status === 401 || response.status === 403) {
      adminStore.logout()
      window.location.href = '/login-backend'
      return
    }

    const result = await response.json()
    if (result.status === 'success') {
      const orderIndex = orders.value.findIndex((o) => o.id === orderId)
      if (orderIndex >= 0) {
        orders.value[orderIndex].status = newStatus
      }
      if (selectedOrder.value?.id === orderId) {
        selectedOrder.value.status = newStatus
      }
    }
  } catch (err) {
    console.error('Lỗi khi cập nhật status:', err)
  }
}

async function cancelOrder(orderId) {
  if (!confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')) return

  try {
    const headers = { 'Content-Type': 'application/json' }
    if (adminStore.token) {
      headers['Authorization'] = `Bearer ${adminStore.token}`
    }

    const response = await fetch(`/api/admin/orders/${orderId}/cancel`, {
      method: 'POST',
      headers,
      credentials: 'same-origin',
    })

    if (response.status === 401 || response.status === 403) {
      adminStore.logout()
      window.location.href = '/login-backend'
      return
    }

    const result = await response.json()
    if (result.status === 'success') {
      const orderIndex = orders.value.findIndex((o) => o.id === orderId)
      if (orderIndex >= 0) {
        orders.value[orderIndex].status = 'cancelled'
      }
      if (selectedOrder.value?.id === orderId) {
        selectedOrder.value.status = 'cancelled'
      }
    }
  } catch (err) {
    console.error('Lỗi khi hủy order:', err)
  }
}

function formatPrice(v) {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
  }).format(v)
}

function formatDate(date) {
  return new Date(date).toLocaleString('vi-VN')
}

function viewOrderDetails(order) {
  selectedOrder.value = order
}

function closeDetails() {
  selectedOrder.value = null
}

onMounted(async () => {
  await checkAuth()
  if (isAuthenticated.value) {
    fetchOrders()
  }
})

defineExpose({
  orders,
  filteredOrders,
  selectedOrder,
  filterStatus,
  loading,
  error,
  isAuthenticated,
  authChecking,
  statusColors,
  fetchOrders,
  updateOrderStatus,
  cancelOrder,
  viewOrderDetails,
  closeDetails,
  formatPrice,
  formatDate,
})
</script>

<template>
  <div class="min-h-screen bg-white font-system">
    <!-- Auth Check -->
    <div v-if="authChecking" class="flex items-center justify-center min-h-screen">
      <p class="text-gray-600">Đang kiểm tra quyền truy cập...</p>
    </div>

    <div v-else-if="!isAuthenticated" class="flex items-center justify-center min-h-screen">
      <div class="text-center space-y-4">
        <p class="text-gray-900 text-xl font-semibold">Bạn cần đăng nhập để truy cập trang này</p>
        <a
          href="/admin/login"
          class="inline-block px-6 py-3 bg-black text-white rounded-lg hover:bg-gray-900 transition"
        >
          Đăng nhập admin
        </a>
      </div>
    </div>

    <!-- Header -->
    <header v-else class="sticky top-0 z-40 bg-white border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-8 py-6">
        <h1 class="text-2xl font-semibold text-gray-900">Quản lý đơn hàng</h1>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-8 py-12">
      <!-- Filter Bar -->
      <div class="mb-8">
        <div class="flex gap-2 flex-wrap">
          <button
            v-for="status in ['all', 'pending', 'confirmed', 'shipped', 'delivered', 'cancelled']"
            :key="status"
            @click="filterStatus = status; fetchOrders()"
            :class="[
              'px-4 py-2 rounded-lg font-medium transition-all duration-200',
              filterStatus === status
                ? 'bg-black text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            {{ status === 'all' ? 'Tất cả' : status }}
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="space-y-4">
        <div
          v-for="i in 5"
          :key="i"
          class="bg-gray-50 rounded-lg h-20 animate-pulse"
        ></div>
      </div>

      <!-- Error State -->
      <div
        v-else-if="error"
        class="bg-red-50 border border-red-200 rounded-lg p-6 text-center"
      >
        <p class="text-red-600">{{ error }}</p>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="filteredOrders.length === 0"
        class="bg-gray-50 border border-gray-200 rounded-lg p-12 text-center"
      >
        <p class="text-gray-600">Không có đơn hàng nào</p>
      </div>

      <!-- Orders Table -->
      <div
        v-else
        class="overflow-x-auto"
      >
        <table class="w-full border-collapse">
          <thead>
            <tr class="border-b-2 border-gray-200">
              <th class="text-left py-4 px-4 font-semibold text-gray-900">Mã đơn</th>
              <th class="text-left py-4 px-4 font-semibold text-gray-900">Khách hàng</th>
              <th class="text-left py-4 px-4 font-semibold text-gray-900">Số điện thoại</th>
              <th class="text-right py-4 px-4 font-semibold text-gray-900">Tổng tiền</th>
              <th class="text-left py-4 px-4 font-semibold text-gray-900">Trạng thái</th>
              <th class="text-left py-4 px-4 font-semibold text-gray-900">Ngày tạo</th>
              <th class="text-center py-4 px-4 font-semibold text-gray-900">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="order in filteredOrders"
              :key="order.id"
              class="border-b border-gray-100 hover:bg-gray-50 transition"
            >
              <td class="py-4 px-4 font-medium text-gray-900">#{{ order.id }}</td>
              <td class="py-4 px-4 text-gray-700">{{ order.customer_name }}</td>
              <td class="py-4 px-4 text-gray-700">{{ order.customer_phone }}</td>
              <td class="py-4 px-4 text-right font-semibold text-gray-900">
                {{ formatPrice(order.total_amount) }}
              </td>
              <td class="py-4 px-4">
                <span
                  :class="['px-3 py-1 rounded-full text-xs font-medium', statusColors[order.status] || 'bg-gray-100']"
                >
                  {{ order.status }}
                </span>
              </td>
              <td class="py-4 px-4 text-gray-600 text-sm">{{ formatDate(order.created_at) }}</td>
              <td class="py-4 px-4 text-center">
                <button
                  @click="viewOrderDetails(order)"
                  class="text-black hover:underline font-medium"
                >
                  Chi tiết
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>

    <!-- Order Details Modal -->
    <transition name="fade">
      <div
        v-if="selectedOrder"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
      >
        <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-8 space-y-6">
          <!-- Header -->
          <div class="flex items-center justify-between border-b border-gray-200 pb-4">
            <div>
              <h3 class="text-2xl font-semibold text-gray-900">Đơn hàng #{{ selectedOrder.id }}</h3>
              <p class="text-sm text-gray-600 mt-1">{{ formatDate(selectedOrder.created_at) }}</p>
            </div>
            <button
              @click="closeDetails"
              class="text-2xl text-gray-500 hover:text-gray-600"
            >
              ×
            </button>
          </div>

          <!-- Order Info -->
          <div class="grid grid-cols-2 gap-6">
            <div>
              <h4 class="font-semibold text-gray-900 mb-3">Thông tin khách hàng</h4>
              <div class="space-y-2 text-sm text-gray-600">
                <p><span class="font-medium">Tên:</span> {{ selectedOrder.customer_name }}</p>
                <p><span class="font-medium">Email:</span> {{ selectedOrder.customer_email }}</p>
                <p><span class="font-medium">Điện thoại:</span> {{ selectedOrder.customer_phone }}</p>
                <p><span class="font-medium">Địa chỉ:</span> {{ selectedOrder.delivery_address }}</p>
              </div>
            </div>
            <div>
              <h4 class="font-semibold text-gray-900 mb-3">Thông tin đơn hàng</h4>
              <div class="space-y-2 text-sm text-gray-600">
                <p><span class="font-medium">Phương thức thanh toán:</span> {{ selectedOrder.payment_method }}</p>
                <p><span class="font-medium">Tổng tiền:</span> {{ formatPrice(selectedOrder.total_amount) }}</p>
                <p>
                  <span class="font-medium">Trạng thái:</span>
                  <span
                    :class="['ml-2 px-2 py-1 rounded text-xs font-medium', statusColors[selectedOrder.status] || 'bg-gray-100']"
                  >
                    {{ selectedOrder.status }}
                  </span>
                </p>
              </div>
            </div>
          </div>

          <!-- Order Items -->
          <div>
            <h4 class="font-semibold text-gray-900 mb-3">Chi tiết sản phẩm</h4>
            <div class="bg-gray-50 rounded-lg overflow-hidden">
              <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b border-gray-200">
                  <tr>
                    <th class="text-left py-2 px-3 font-medium text-gray-900">Sản phẩm</th>
                    <th class="text-right py-2 px-3 font-medium text-gray-900">Đơn giá</th>
                    <th class="text-right py-2 px-3 font-medium text-gray-900">Số lượng</th>
                    <th class="text-right py-2 px-3 font-medium text-gray-900">Tổng</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="item in selectedOrder.items"
                    :key="item.id"
                    class="border-b border-gray-200 last:border-0"
                  >
                    <td class="py-2 px-3 text-gray-700">{{ item.product_name }}</td>
                    <td class="text-right py-2 px-3 text-gray-700">{{ formatPrice(item.product_price) }}</td>
                    <td class="text-right py-2 px-3 text-gray-700">{{ item.quantity }}</td>
                    <td class="text-right py-2 px-3 font-medium text-gray-900">{{ formatPrice(item.subtotal) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Status Update -->
          <div
            v-if="selectedOrder.status !== 'cancelled'"
            class="bg-gray-50 rounded-lg p-4"
          >
            <label class="block text-sm font-medium text-gray-900 mb-2">Cập nhật trạng thái</label>
            <div class="flex gap-2 flex-wrap">
              <button
                v-for="status in ['processing', 'shipped', 'delivered', 'cancelled']"
                :key="status"
                @click="updateOrderStatus(selectedOrder.id, status)"
                :disabled="status === selectedOrder.status"
                class="px-3 py-1 rounded text-sm font-medium transition-all"
                :class="[
                  status === selectedOrder.status
                    ? 'bg-gray-300 text-gray-600 cursor-not-allowed'
                    : status === 'cancelled'
                      ? 'bg-red-100 text-red-700 hover:bg-red-200'
                      : 'bg-black text-white hover:bg-gray-900'
                ]"
              >
                {{ status }}
              </button>
            </div>
          </div>

          <!-- Cancel Button -->
          <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <button
              @click="closeDetails"
              class="px-6 py-2 bg-gray-100 text-gray-900 font-medium rounded-lg hover:bg-gray-200 transition"
            >
              Đóng
            </button>
            <button
              v-if="selectedOrder.status !== 'cancelled'"
              @click="cancelOrder(selectedOrder.id)"
              class="px-6 py-2 bg-red-100 text-red-700 font-medium rounded-lg hover:bg-red-200 transition"
            >
              Hủy đơn
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.font-system {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>

