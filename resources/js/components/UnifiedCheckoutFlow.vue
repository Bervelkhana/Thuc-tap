<script setup>
import { ref, computed } from 'vue'
import { useCartStore } from '../stores/cartStore'
import { useRouter } from 'vue-router'

const cart = useCartStore()
const router = useRouter()

const step = ref(1)
const shippingAddress = ref({
  name: '',
  phone: '',
  address: '',
  email: '',
})
const paymentMethod = ref('cod')
const submitting = ref(false)
const orderResult = ref(null)
const error = ref('')

const total = computed(() => cart.cartTotal)

const canProceed = computed(() => {
  if (step.value === 1) return cart.items.length > 0
  if (step.value === 2) {
    return (
      shippingAddress.value.name.trim() &&
      shippingAddress.value.phone.trim() &&
      shippingAddress.value.address.trim()
    )
  }
  if (step.value === 3) return paymentMethod.value
  return true
})

function nextStep() {
  if (canProceed.value) step.value++
}

function prevStep() {
  if (step.value > 1) step.value--
}

async function submitOrder() {
  submitting.value = true
  error.value = ''

  try {
    const payload = {
      user_id: 1,
      customer_name: shippingAddress.value.name,
      customer_email: shippingAddress.value.email,
      customer_phone: shippingAddress.value.phone,
      delivery_address: shippingAddress.value.address,
      payment_method: paymentMethod.value,
      items: cart.items.map((item) => ({
        product_id: item.product_id,
        quantity: item.quantity,
      })),
    }

    const response = await fetch('/api/orders', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify(payload),
    })

    const data = await response.json()

    if (data.status === 'success') {
      orderResult.value = data.data

      if (data.data.is_total_valid === false) {
        error.value = 'Có sự không khớp trong tổng giá. Vui lòng thử lại.'
        return
      }

      if (data.data.compatibility && !data.data.compatibility.is_compatible) {
        error.value = 'Cấu hình không tương thích. Vui lòng kiểm tra lại.'
        return
      }

      cart.clearCart()
      router.push({ name: 'order-success', params: { id: data.data.order_id } })
    } else {
      error.value = data.message || 'Đặt hàng thất bại, vui lòng thử lại.'
    }
  } catch (err) {
    error.value = err.message || 'Đặt hàng thất bại, vui lòng thử lại.'
    console.error('Order failed:', err)
  } finally {
    submitting.value = false
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
  }).format(Number(price || 0))
}
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <!-- Step indicator -->
    <div class="flex items-center justify-between">
      <div
        v-for="s in [1, 2, 3, 4]"
        :key="s"
        class="flex items-center"
      >
        <div
          :class="[
            'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors',
            step >= s ? 'bg-black text-white' : 'bg-gray-200 text-gray-600',
          ]"
        >
          {{ s }}
        </div>
        <span
          v-if="s < 4"
          class="mx-2 text-gray-500"
        >→</span>
      </div>
    </div>

    <!-- Error -->
    <div
      v-if="error"
      class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700"
    >
      {{ error }}
    </div>

    <!-- Step 1: Cart Review -->
    <div
      v-if="step === 1"
      class="bg-white rounded-lg shadow p-6"
    >
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Giỏ hàng</h2>
      <div
        v-if="cart.items.length === 0"
        class="text-gray-600 text-sm"
      >
        Giỏ hàng đang trống.
      </div>
      <div
        v-else
        class="space-y-3"
      >
        <div
          v-for="item in cart.items"
          :key="item.product_id"
          class="flex items-center justify-between border-b border-gray-100 pb-3"
        >
          <div>
            <p class="font-medium text-gray-900">{{ item.name }}</p>
            <p class="text-xs text-gray-600">Số lượng: {{ item.quantity }}</p>
          </div>
          <p class="font-semibold text-gray-900">{{ formatPrice(item.price * item.quantity) }}</p>
        </div>
        <div class="flex items-center justify-between pt-2">
          <span class="font-semibold text-gray-900">Tạm tính</span>
          <span class="font-bold text-gray-900">{{ formatPrice(total) }}</span>
        </div>
      </div>
      <div class="mt-6 flex justify-end">
        <button
          @click="nextStep"
          :disabled="!canProceed"
          class="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-900 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium"
        >
          Tiếp theo
        </button>
      </div>
    </div>

    <!-- Step 2: Address -->
    <div
      v-if="step === 2"
      class="bg-white rounded-lg shadow p-6"
    >
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Thông tin giao hàng</h2>
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-1">
            Họ và tên *
          </label>
          <input
            v-model="shippingAddress.name"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-gray-900 text-sm"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-1">
            Email *
          </label>
          <input
            v-model="shippingAddress.email"
            type="email"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-gray-900 text-sm"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-1">
            Số điện thoại *
          </label>
          <input
            v-model="shippingAddress.phone"
            type="tel"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-gray-900 text-sm"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-1">
            Địa chỉ *
          </label>
          <textarea
            v-model="shippingAddress.address"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-gray-900 text-sm"
          ></textarea>
        </div>
      </div>
      <div class="mt-6 flex justify-between">
        <button
          @click="prevStep"
          class="px-6 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50"
        >
          Quay lại
        </button>
        <button
          @click="nextStep"
          :disabled="!canProceed"
          class="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-900 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium"
        >
          Tiếp theo
        </button>
      </div>
    </div>

    <!-- Step 3: Payment -->
    <div
      v-if="step === 3"
      class="bg-white rounded-lg shadow p-6"
    >
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Phương thức thanh toán</h2>
      <div class="space-y-3">
        <label class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-gray-900 transition">
          <input
            v-model="paymentMethod"
            value="cod"
            type="radio"
            class="w-4 h-4"
          />
          <div>
            <p class="text-sm font-medium text-gray-900">Cash on Delivery (COD)</p>
            <p class="text-xs text-gray-600">Pay when your order is delivered</p>
          </div>
        </label>
      </div>
      <div class="mt-6 flex justify-between">
        <button
          @click="prevStep"
          class="px-6 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50"
        >
          Quay lại
        </button>
        <button
          @click="nextStep"
          :disabled="!canProceed"
          class="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-900 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium"
        >
          Tiếp theo
        </button>
      </div>
    </div>

    <!-- Step 4: Confirm -->
    <div
      v-if="step === 4"
      class="bg-white rounded-lg shadow p-6"
    >
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Xác nhận đơn hàng</h2>
      <div class="space-y-4 text-sm text-gray-700">
        <div>
          <p class="font-medium text-gray-900">Thông tin giao hàng</p>
          <p>{{ shippingAddress.name }} | {{ shippingAddress.phone }}</p>
          <p>{{ shippingAddress.address }}</p>
          <p>{{ shippingAddress.email }}</p>
        </div>
        <div>
          <p class="font-medium text-gray-900">Phương thức thanh toán</p>
          <p>{{ paymentMethod === 'cod' ? 'Cash on Delivery (COD)' : paymentMethod }}</p>
        </div>
        <div>
          <p class="font-medium text-gray-900">Tổng giá</p>
          <p class="text-lg font-bold text-gray-900">{{ formatPrice(total) }}</p>
        </div>
      </div>
      <div class="mt-6 flex justify-between">
        <button
          @click="prevStep"
          class="px-6 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50"
        >
          Quay lại
        </button>
        <button
          @click="submitOrder"
          :disabled="submitting"
          class="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-900 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium"
        >
          <span v-if="submitting">Đang xử lý...</span>
          <span v-else>Đặt hàng</span>
        </button>
      </div>
    </div>
  </div>
</template>

