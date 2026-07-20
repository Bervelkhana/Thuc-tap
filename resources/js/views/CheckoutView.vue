<script setup>
import { reactive, ref, computed } from 'vue'

// ===== MOCK DATA: danh sách sản phẩm trong giỏ =====
const items = ref([
  { product_id: 3, name: 'AMD Ryzen 5 7600', price: 4590000, quantity: 1 },
  { product_id: 13, name: 'NVIDIA RTX 4060 8GB', price: 7990000, quantity: 1 },
  { product_id: 5, name: 'Corsair Vengeance DDR5 16GB', price: 1490000, quantity: 2 },
])

// Thông tin giao hàng
const form = reactive({ name: '', phone: '', address: '' })

// Phương thức thanh toán: cod | vietqr
const paymentMethod = ref('cod')

const SHIPPING_FEE = 30000

function formatPrice(v) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v)
}

// ===== TÓM TẮT CHI PHÍ =====
const subtotal = computed(() =>
  items.value.reduce((sum, i) => sum + i.price * i.quantity, 0)
)
const grandTotal = computed(() => subtotal.value + SHIPPING_FEE)
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 py-8">
      <h1 class="text-2xl font-bold text-gray-800 mb-6">Thanh toán</h1>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- ===== CỘT TRÁI: DANH SÁCH SẢN PHẨM ===== -->
        <section class="lg:col-span-2 bg-white border rounded-xl p-6">
          <h2 class="text-lg font-semibold text-gray-800 mb-4">Sản phẩm trong đơn</h2>

          <ul class="divide-y">
            <li v-for="i in items" :key="i.product_id" class="py-4 flex items-center gap-4">
              <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 text-xs">
                Ảnh
              </div>
              <div class="flex-1">
                <p class="font-medium text-gray-800">{{ i.name }}</p>
                <p class="text-sm text-gray-500">Số lượng: {{ i.quantity }}</p>
              </div>
              <p class="font-semibold text-gray-800">{{ formatPrice(i.price * i.quantity) }}</p>
            </li>
          </ul>
        </section>

        <!-- ===== CỘT PHẢI: FORM + CHI PHÍ + THANH TOÁN ===== -->
        <aside class="space-y-6">
          <!-- Form giao hàng -->
          <div class="bg-white border rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Thông tin giao hàng</h2>
            <label class="block mb-3">
              <span class="text-sm text-gray-600">Họ và tên</span>
              <input v-model="form.name" type="text" class="mt-1 w-full border rounded-lg px-3 py-2" />
            </label>
            <label class="block mb-3">
              <span class="text-sm text-gray-600">Số điện thoại</span>
              <input v-model="form.phone" type="text" class="mt-1 w-full border rounded-lg px-3 py-2" />
            </label>
            <label class="block">
              <span class="text-sm text-gray-600">Địa chỉ</span>
              <textarea v-model="form.address" rows="2" class="mt-1 w-full border rounded-lg px-3 py-2"></textarea>
            </label>
          </div>

          <!-- Phương thức thanh toán -->
          <div class="bg-white border rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Phương thức thanh toán</h2>

            <label
              :class="[
                'flex items-center gap-3 border rounded-lg p-3 mb-3 cursor-pointer',
                paymentMethod === 'cod' ? 'border-blue-500 bg-blue-50' : '',
              ]"
            >
              <input type="radio" value="cod" v-model="paymentMethod" />
              <span class="text-gray-700">💵 Thanh toán khi nhận hàng (COD)</span>
            </label>

            <label
              :class="[
                'flex items-center gap-3 border rounded-lg p-3 cursor-pointer',
                paymentMethod === 'vietqr' ? 'border-blue-500 bg-blue-50' : '',
              ]"
            >
              <input type="radio" value="vietqr" v-model="paymentMethod" />
              <span class="text-gray-700">🏦 Chuyển khoản VietQR</span>
            </label>

            <!-- UI mã QR khi chọn VietQR -->
            <div v-if="paymentMethod === 'vietqr'" class="mt-4 text-center">
              <div class="inline-block bg-white border-2 border-dashed border-gray-300 rounded-xl p-6">
                <div class="w-40 h-40 bg-gray-100 mx-auto flex items-center justify-center text-gray-400 rounded">
                  [ QR CODE ]
                </div>
                <p class="text-sm text-gray-600 mt-3">Quét mã để thanh toán</p>
                <p class="text-lg font-bold text-blue-700">{{ formatPrice(grandTotal) }}</p>
              </div>
            </div>
          </div>

          <!-- Tóm tắt chi phí -->
          <div class="bg-white border rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Tóm tắt đơn hàng</h2>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between text-gray-600">
                <span>Tạm tính</span><span>{{ formatPrice(subtotal) }}</span>
              </div>
              <div class="flex justify-between text-gray-600">
                <span>Phí vận chuyển</span><span>{{ formatPrice(SHIPPING_FEE) }}</span>
              </div>
              <div class="border-t pt-3 flex justify-between text-lg font-bold text-gray-800">
                <span>Tổng cộng</span>
                <span class="text-blue-700">{{ formatPrice(grandTotal) }}</span>
              </div>
            </div>

            <button class="w-full mt-5 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
              Đặt hàng
            </button>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>
