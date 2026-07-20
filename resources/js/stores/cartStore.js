import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'

// Pinia store quản lý giỏ hàng toàn cục (setup style + Composition API)
export const useCartStore = defineStore('cart', () => {
  // --- STATE ---
  // Khôi phục giỏ hàng từ localStorage khi khởi tạo (giữ dữ liệu sau khi F5)
  const items = ref(JSON.parse(localStorage.getItem('cart') || '[]'))

  // --- PERSIST ---
  // Mỗi khi items thay đổi (deep watch), ghi lại xuống localStorage
  watch(
    items,
    (val) => localStorage.setItem('cart', JSON.stringify(val)),
    { deep: true }
  )

  // --- GETTERS ---
  // Tổng tiền = tổng (đơn giá * số lượng) của mọi món
  const cartTotal = computed(() =>
    items.value.reduce((sum, i) => sum + i.price * i.quantity, 0)
  )
  // Tổng số lượng sản phẩm (dùng cho badge giỏ hàng)
  const cartCount = computed(() =>
    items.value.reduce((sum, i) => sum + i.quantity, 0)
  )

  // --- ACTIONS ---
  // Thêm sản phẩm: nếu đã có thì tăng số lượng, chưa có thì thêm mới
  function addToCart(product, quantity = 1) {
    const found = items.value.find((i) => i.product_id === product.id)
    if (found) {
      found.quantity += quantity
    } else {
      items.value.push({
        product_id: product.id,
        name: product.name,
        price: Number(product.price),
        quantity,
      })
    }
  }

  // Xoá 1 sản phẩm khỏi giỏ theo product_id
  function removeFromCart(productId) {
    items.value = items.value.filter((i) => i.product_id !== productId)
  }

  // Cập nhật số lượng; nếu <= 0 thì loại bỏ luôn khỏi giỏ
  function updateQuantity(productId, quantity) {
    const found = items.value.find((i) => i.product_id === productId)
    if (!found) return
    if (quantity <= 0) {
      removeFromCart(productId)
    } else {
      found.quantity = quantity
    }
  }

  // Làm sạch giỏ hàng (gọi sau khi đặt hàng thành công)
  function clearCart() {
    items.value = []
  }

  return {
    items,
    cartTotal,
    cartCount,
    addToCart,
    removeFromCart,
    updateQuantity,
    clearCart,
  }
})
