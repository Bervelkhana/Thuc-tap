<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cartStore'
import axios from 'axios'

const cart = useCartStore()
const router = useRouter()

// Thông tin khách hàng nhập ở form cột trái
const form = reactive({
  name: '',
  phone: '',
  address: '',
})

const loading = ref(false)
const error = ref('')
// Lưu kết quả trả về từ backend (mã đơn, ngày giao dự kiến do Carbon xử lý)
const result = ref(null)

function formatPrice(value) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
}

// Gửi đơn hàng lên API /api/orders
async function submitOrder() {
  error.value = ''

  // Kiểm tra hợp lệ cơ bản phía client
  if (!form.name || !form.phone || !form.address) {
    error.value = 'Vui lòng nhập đầy đủ thông tin giao hàng.'
    return
  }
  if (cart.items.length === 0) {
    error.value = 'Giỏ hàng đang trống.'
    return
  }

  loading.value = true
  try {
    // POST giỏ hàng + thông tin khách. Backend nhận items[{product_id, quantity}]
    const { data } = await axios.post('/api/orders', {
      user_id: 1, // demo: gắn với tài khoản demo (sẽ thay bằng auth ở bước phân quyền)
      customer: { ...form },
      items: cart.items.map((i) => ({
        product_id: i.product_id,
        quantity: i.quantity,
      })),
    })

    // Thành công: lưu kết quả để hiển thị, rồi làm sạch giỏ hàng
    result.value = data.data
    cart.clearCart()
  } catch (e) {
    // Ưu tiên hiển thị message lỗi nghiệp vụ từ backend (vd: hết hàng)
    error.value = e.response?.data?.message || 'Đặt hàng thất bại, vui lòng thử lại.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-5xl mx-auto p-4">
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Thanh toán</h1>

    <!-- THÔNG BÁO THÀNH CÔNG (hiển thị sau khi đặt hàng) -->
    <div
      v-if="result"
      class="bg-green-50 border border-green-300 rounded-lg p-6 text-center"
    >
      <h2 class="text-xl font-semibold text-green-700 mb-2">Đặt hàng thành công!</h2>
      <p class="text-gray-700">Mã đơn hàng: <strong>#{{ result.order_id }}</strong></p>
      <p class="text-gray-700">Ngày tạo đơn: <strong>{{ result.created_at }}</strong></p>
      <p class="text-gray-700">
        Ngày giao hàng dự kiến: <strong>{{ result.estimated_delivery }}</strong>
      </p>
      <p class="text-gray-700 mt-2">Tổng tiền: <strong>{{ formatPrice(result.total) }}</strong></p>
      <button
        @click="router.push('/')"
        class="mt-4 bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition"
      >
        Tiếp tục mua sắm
      </button>
    </div>

    <!-- FORM + TÓM TẮT GIỎ HÀNG -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Cột trái: thông tin khách hàng -->
      <div class="border rounded-lg p-6 bg-white">
        <h2 class="text-lg font-medium text-gray-800 mb-4">Thông tin giao hàng</h2>

        <label class="block mb-3">
          <span class="text-sm text-gray-600">Họ và tên</span>
          <input v-model="form.name" type="text" class="mt-1 w-full border rounded px-3 py-2" />
        </label>
        <label class="block mb-3">
          <span class="text-sm text-gray-600">Số điện thoại</span>
          <input v-model="form.phone" type="text" class="mt-1 w-full border rounded px-3 py-2" />
        </label>
        <label class="block mb-3">
          <span class="text-sm text-gray-600">Địa chỉ giao hàng</span>
          <textarea v-model="form.address" rows="3" class="mt-1 w-full border rounded px-3 py-2"></textarea>
        </label>

        <p v-if="error" class="text-red-500 text-sm mb-3">{{ error }}</p>

        <button
          @click="submitOrder"
          :disabled="loading"
          class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition disabled:opacity-50"
        >
          {{ loading ? 'Đang xử lý...' : 'Đặt hàng' }}
        </button>
      </div>

      <!-- Cột phải: tóm tắt giỏ hàng lấy từ Pinia store -->
      <div class="border rounded-lg p-6 bg-white">
        <h2 class="text-lg font-medium text-gray-800 mb-4">Đơn hàng của bạn</h2>

        <p v-if="cart.items.length === 0" class="text-gray-600">Giỏ hàng đang trống.</p>

        <ul v-else class="divide-y">
          <li v-for="i in cart.items" :key="i.product_id" class="py-3 flex justify-between items-center">
            <div>
              <p class="text-gray-800">{{ i.name }}</p>
              <div class="flex items-center gap-2 mt-1">
                <button @click="cart.updateQuantity(i.product_id, i.quantity - 1)" class="px-2 border rounded">-</button>
                <span>{{ i.quantity }}</span>
                <button @click="cart.updateQuantity(i.product_id, i.quantity + 1)" class="px-2 border rounded">+</button>
                <button @click="cart.removeFromCart(i.product_id)" class="ml-2 text-red-500 text-sm">Xóa</button>
              </div>
            </div>
            <span class="text-gray-700">{{ formatPrice(i.price * i.quantity) }}</span>
          </li>
        </ul>

        <div class="border-t mt-4 pt-4 flex justify-between text-lg font-semibold">
          <span>Tổng cộng</span>
          <span class="text-blue-600">{{ formatPrice(cart.cartTotal) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

