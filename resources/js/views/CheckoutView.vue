<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCartStore } from '../stores/cartStore'
import { useRouter } from 'vue-router'

const router = useRouter()
const cartStore = useCartStore()

const loading = ref(false)
const error = ref(null)
const success = ref(false)
const orderData = ref(null)

const customerData = reactive({
  name: '',
  phone: '',
  address: '',
  email: '',
})

const formData = reactive({
  payment_method: 'cod',
  customer: customerData,
})

// Validate form
const isFormValid = computed(() => {
  return customerData.name.trim() && 
         customerData.phone.trim() && 
         customerData.address.trim() &&
         customerData.email.trim()
})

// Format price
function formatPrice(price) {
  return new Intl.NumberFormat('vi-VN', { 
    style: 'currency', 
    currency: 'VND' 
  }).format(price)
}

// Handle checkout
async function handleCheckout() {
  if (!isFormValid.value) {
    error.value = 'Please fill in all customer information'
    return
  }

  if (cartStore.items.length === 0) {
    error.value = 'Your cart is empty'
    return
  }

  loading.value = true
  error.value = null

  try {
    const payload = {
      user_id: 1, // Placeholder: should be from auth
      payment_method: formData.payment_method,
      customer: {
        name: customerData.name,
        phone: customerData.phone,
        address: customerData.address,
        email: customerData.email,
      },
      items: cartStore.items.map(item => ({
        product_id: item.product_id,
        quantity: item.quantity,
      })),
    }

    const response = await fetch('/api/orders', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    })

    const result = await response.json()

    if (result.status === 'success') {
      success.value = true
      orderData.value = result.data
      cartStore.clearCart()
      
      // Redirect to home after 3 seconds
      setTimeout(() => {
        router.push('/home')
      }, 3000)
    } else {
      error.value = result.message || 'Failed to create order'
    }
  } catch (err) {
    error.value = err.message || 'An error occurred during checkout'
    console.error('Checkout error:', err)
  } finally {
    loading.value = false
  }
}

// Watch cart changes
onMounted(() => {
  if (cartStore.items.length === 0) {
    router.push('/catalog')
  }
})

import { reactive } from 'vue'
</script>

<template>
  <div class="min-h-screen bg-white font-system">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white border-b border-gray-100">
      <div class="max-w-6xl mx-auto px-8 py-6">
        <h1 class="text-xl font-semibold text-gray-900">Checkout</h1>
      </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-8 py-12">
      <!-- Success Message -->
      <transition name="fade">
        <div v-if="success" class="mb-8 bg-green-50 border border-green-200 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-green-900 mb-2">Order Created Successfully!</h3>
          <p class="text-sm text-green-700">
            Order ID: <span class="font-semibold">#{{ orderData.order_id }}</span>
          </p>
          <p class="text-sm text-green-700 mt-2">
            Estimated Delivery: {{ orderData.estimated_delivery }}
          </p>
          <p class="text-sm text-green-700 mt-4">
            Redirecting to home page...
          </p>
        </div>
      </transition>

      <!-- Error Message -->
      <transition name="fade">
        <div v-if="error && !success" class="mb-8 bg-red-50 border border-red-200 rounded-lg p-6">
          <p class="text-sm text-red-700">{{ error }}</p>
        </div>
      </transition>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Checkout Form -->
        <div class="lg:col-span-2 space-y-12">
          <!-- Customer Information -->
          <div class="space-y-6">
            <div class="space-y-2">
              <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-widest">Customer Information</h2>
              <div class="w-8 h-px bg-gray-900"></div>
            </div>

            <div class="space-y-4">
              <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                  Full Name *
                </label>
                <input 
                  v-model="customerData.name"
                  type="text"
                  placeholder="Enter your full name"
                  class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-gray-900 text-sm"
                  :disabled="loading"
                >
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                  Email *
                </label>
                <input 
                  v-model="customerData.email"
                  type="email"
                  placeholder="Enter your email"
                  class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-gray-900 text-sm"
                  :disabled="loading"
                >
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                  Phone *
                </label>
                <input 
                  v-model="customerData.phone"
                  type="tel"
                  placeholder="Enter your phone number"
                  class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-gray-900 text-sm"
                  :disabled="loading"
                >
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                  Address *
                </label>
                <textarea 
                  v-model="customerData.address"
                  placeholder="Enter your delivery address"
                  rows="3"
                  class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-gray-900 text-sm"
                  :disabled="loading"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Payment Method -->
          <div class="space-y-6">
            <div class="space-y-2">
              <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-widest">Payment Method</h2>
              <div class="w-8 h-px bg-gray-900"></div>
            </div>

            <div class="space-y-3">
              <label class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-gray-900 transition">
                <input 
                  v-model="formData.payment_method" 
                  value="cod" 
                  type="radio"
                  class="w-4 h-4"
                  :disabled="loading"
                >
                <div>
                  <p class="text-sm font-medium text-gray-900">Cash on Delivery (COD)</p>
                  <p class="text-xs text-gray-600">Pay when your order is delivered</p>
                </div>
              </label>
            </div>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-1">
          <div class="space-y-6 bg-gray-50 rounded-lg p-8">
            <div class="space-y-2">
              <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-widest">Order Summary</h3>
              <div class="w-8 h-px bg-gray-900"></div>
            </div>

            <!-- Order Items -->
            <div class="space-y-4 max-h-96 overflow-y-auto">
              <div 
                v-for="item in cartStore.items" 
                :key="item.product_id"
                class="flex items-justify-between justify-between text-sm"
              >
                <div>
                  <p class="font-medium text-gray-900">{{ item.name }}</p>
                  <p class="text-xs text-gray-600">Qty: {{ item.quantity }}</p>
                </div>
                <p class="font-semibold text-gray-900">
                  {{ formatPrice(item.price * item.quantity) }}
                </p>
              </div>
            </div>

            <div class="border-t border-gray-200"></div>

            <!-- Totals -->
            <div class="space-y-3">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Subtotal</span>
                <span class="font-medium text-gray-900">{{ formatPrice(cartStore.cartTotal) }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Shipping</span>
                <span class="font-medium text-gray-900">Free</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Tax</span>
                <span class="font-medium text-gray-900">{{ formatPrice(cartStore.cartTotal * 0.1) }}</span>
              </div>
            </div>

            <div class="border-t border-gray-200"></div>

            <!-- Total -->
            <div class="flex items-center justify-between">
              <span class="text-sm font-semibold text-gray-900">Total</span>
              <span class="text-lg font-bold text-gray-900">
                {{ formatPrice(cartStore.cartTotal * 1.1) }}
              </span>
            </div>

            <!-- Checkout Button -->
            <button 
              @click="handleCheckout"
              :disabled="loading || !isFormValid || success"
              class="w-full py-3 bg-black text-white font-medium rounded-lg hover:bg-gray-900 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading" class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
              </span>
              <span v-else>Place Order</span>
            </button>

            <p class="text-xs text-gray-600 text-center">
              By placing this order, you agree to our terms and conditions.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.font-system {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
