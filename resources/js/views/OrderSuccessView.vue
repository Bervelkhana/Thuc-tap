<script setup>
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const orderId = route.params.id
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto">
      <div class="bg-white rounded-lg shadow p-8 text-center space-y-4">
        <div class="text-4xl">✅</div>
        <h1 class="text-2xl font-bold text-gray-900">Đặt hàng thành công</h1>
        <p class="text-sm text-gray-600">
          Mã đơn hàng: <span class="font-semibold">#{{ orderId }}</span>
        </p>
        <p class="text-sm text-gray-600">
          Cảm ơn bạn đã đặt hàng. Chúng tôi sẽ liên hệ với bạn sớm nhất có thể.
        </p>
        <button
          @click="router.push('/browse')"
          class="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-900 transition"
        >
          Tiếp tục mua sắm
        </button>
      </div>
    </div>
  </div>
</template>
