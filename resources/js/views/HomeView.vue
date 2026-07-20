<script setup>
import { ref } from 'vue'

// ===== MOCK DATA (tạm thời, chưa gọi API) =====
const categories = ref([
  { id: 1, name: 'CPU', icon: '🧠' },
  { id: 2, name: 'RAM', icon: '📊' },
  { id: 3, name: 'Mainboard', icon: '🔲' },
  { id: 4, name: 'VGA', icon: '🎮' },
  { id: 5, name: 'SSD', icon: '💾' },
  { id: 6, name: 'PSU', icon: '⚡' },
  { id: 7, name: 'Case', icon: '🖥️' },
])

const flashSale = ref([
  { id: 1, name: 'Intel Core i5-13400F', price: 3290000, oldPrice: 3690000, thumb: '' },
  { id: 2, name: 'NVIDIA RTX 4060 8GB', price: 7290000, oldPrice: 7990000, thumb: '' },
  { id: 3, name: 'Corsair Vengeance DDR5 16GB', price: 1290000, oldPrice: 1490000, thumb: '' },
  { id: 4, name: 'Samsung 980 PRO 1TB', price: 1990000, oldPrice: 2290000, thumb: '' },
  { id: 5, name: 'ASUS ROG STRIX B760-A', price: 5490000, oldPrice: 5990000, thumb: '' },
])

function formatPrice(v) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v)
}
function discount(item) {
  return Math.round((1 - item.price / item.oldPrice) * 100)
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- ===== HEADER ===== -->
    <header class="bg-white shadow-sm sticky top-0 z-10">
      <div class="max-w-6xl mx-auto px-4 py-3 flex items-center gap-4">
        <h1 class="text-xl font-bold text-blue-700 whitespace-nowrap">PC Store</h1>

        <!-- Search -->
        <div class="flex-1">
          <input
            type="text"
            placeholder="Tìm linh kiện, sản phẩm..."
            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Cart + User -->
        <nav class="flex items-center gap-3 text-gray-600">
          <button class="relative hover:text-blue-700">
            🛒
            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full px-1.5">0</span>
          </button>
          <button class="hover:text-blue-700">👤</button>
        </nav>
      </div>
    </header>

    <!-- ===== HERO SECTION ===== -->
    <section class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white">
      <div class="max-w-6xl mx-auto px-4 py-16 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Xây dựng cấu hình PC trong mơ của bạn</h2>
        <p class="text-blue-100 mb-8 max-w-2xl mx-auto">
          Linh kiện chính hãng, giá tốt, tư vấn cấu hình thông minh cùng công nghệ AI.
        </p>
        <button class="bg-white text-blue-700 font-semibold px-8 py-3 rounded-lg hover:bg-blue-50 transition">
          🤖 Trải nghiệm AI PC Builder
        </button>
      </div>
    </section>

    <!-- ===== BODY ===== -->
    <main class="max-w-6xl mx-auto px-4 py-10 space-y-12">
      <!-- Lưới danh mục icon -->
      <section>
        <h3 class="text-xl font-semibold text-gray-800 mb-5">Danh mục linh kiện</h3>
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-7 gap-4">
          <a
            v-for="c in categories"
            :key="c.id"
            href="#"
            class="flex flex-col items-center gap-2 bg-white border rounded-xl p-4 hover:shadow-md hover:border-blue-300 transition"
          >
            <span class="text-3xl">{{ c.icon }}</span>
            <span class="text-sm font-medium text-gray-700 text-center">{{ c.name }}</span>
          </a>
        </div>
      </section>

      <!-- Băng chuyền Flash sale -->
      <section>
        <div class="flex items-center justify-between mb-5">
          <h3 class="text-xl font-semibold text-red-600">⚡ Flash Sale</h3>
          <a href="#" class="text-sm text-blue-600 hover:underline">Xem tất cả</a>
        </div>

        <!-- Carousel: cuộn ngang -->
        <div class="flex gap-4 overflow-x-auto pb-2 snap-x">
          <article
            v-for="item in flashSale"
            :key="item.id"
            class="snap-start shrink-0 w-52 bg-white border rounded-xl p-4 hover:shadow-md transition"
          >
            <div class="h-28 bg-gray-100 rounded-lg mb-3 flex items-center justify-center text-gray-400">
              Ảnh
            </div>
            <span class="inline-block bg-red-100 text-red-600 text-xs font-semibold px-2 py-0.5 rounded mb-2">
              -{{ discount(item) }}%
            </span>
            <h4 class="text-sm font-medium text-gray-800 line-clamp-2 h-10">{{ item.name }}</h4>
            <p class="text-red-600 font-bold mt-2">{{ formatPrice(item.price) }}</p>
            <p class="text-gray-400 text-xs line-through">{{ formatPrice(item.oldPrice) }}</p>
          </article>
        </div>
      </section>
    </main>
  </div>
</template>
