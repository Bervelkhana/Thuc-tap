<script setup>
import { ref } from 'vue'

// ===== MOCK DATA (mô phỏng dữ liệu 1 sản phẩm lấy từ EAV) =====
const product = ref({
  id: 3,
  name: 'AMD Ryzen 5 7600',
  sku: 'AMD-RYZEN-5-7600',
  price: 4590000,
  stock_quantity: 21,
  category: 'CPU',
  description:
    'AMD Ryzen 5 7600 là bộ vi xử lý 6 nhân 12 luồng trên nền tảng AM5, hiệu năng mạnh mẽ cho gaming và làm việc, hỗ trợ RAM DDR5 và PCIe 5.0.',
  datasheet_pdf_url: '/files/ryzen-5-7600-datasheet.pdf',
  gallery: ['', '', '', ''], // placeholder ảnh
  // Thông số kỹ thuật dạng EAV: mảng { name, value }
  specs: [
    { name: 'Socket', value: 'AM5' },
    { name: 'Số nhân', value: '6' },
    { name: 'Số luồng', value: '12' },
    { name: 'Xung nhịp', value: '3.8 / 5.1 GHz' },
    { name: 'TDP', value: '65W' },
  ],
})

const activeImage = ref(0)
const quantity = ref(1)
const activeTab = ref('desc') // desc | specs | docs

function formatPrice(v) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v)
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 py-6">
      <!-- ===== BREADCRUMB ===== -->
      <nav class="text-sm text-gray-600 mb-6">
        <a href="#" class="hover:text-blue-600">Trang chủ</a>
        <span class="mx-2">/</span>
        <a href="#" class="hover:text-blue-600">{{ product.category }}</a>
        <span class="mx-2">/</span>
        <span class="text-gray-800">{{ product.name }}</span>
      </nav>

      <!-- ===== TOP: GALLERY (trái) + INFO (phải) ===== -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Gallery ảnh -->
        <div>
          <div class="aspect-square bg-white border rounded-xl flex items-center justify-center text-gray-500 mb-3">
            Ảnh chính #{{ activeImage + 1 }}
          </div>
          <div class="flex gap-3">
            <button
              v-for="(img, i) in product.gallery"
              :key="i"
              @click="activeImage = i"
              :class="[
                'w-16 h-16 bg-gray-100 border rounded-lg flex items-center justify-center text-xs text-gray-500',
                activeImage === i ? 'ring-2 ring-blue-500' : '',
              ]"
            >
              {{ i + 1 }}
            </button>
          </div>
        </div>

        <!-- Thông tin & Add to cart -->
        <div>
          <h1 class="text-2xl font-bold text-gray-800">{{ product.name }}</h1>
          <p class="text-sm text-gray-600 mt-1">SKU: {{ product.sku }}</p>

          <p class="text-3xl font-bold text-blue-700 my-4">{{ formatPrice(product.price) }}</p>

          <p class="text-sm mb-4" :class="product.stock_quantity > 0 ? 'text-green-600' : 'text-red-600'">
            {{ product.stock_quantity > 0 ? `Còn hàng (${product.stock_quantity})` : 'Hết hàng' }}
          </p>

          <!-- Chọn số lượng -->
          <div class="flex items-center gap-3 mb-6">
            <span class="text-gray-600">Số lượng:</span>
            <div class="flex items-center border rounded-lg">
              <button @click="quantity > 1 && quantity--" class="px-3 py-1 hover:bg-gray-100">-</button>
              <span class="px-4">{{ quantity }}</span>
              <button @click="quantity++" class="px-3 py-1 hover:bg-gray-100">+</button>
            </div>
          </div>

          <div class="flex gap-3">
            <button class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
              🛒 Thêm vào giỏ
            </button>
            <button class="flex-1 bg-orange-500 text-white py-3 rounded-lg font-semibold hover:bg-orange-600 transition">
              Mua ngay
            </button>
          </div>
        </div>
      </div>

      <!-- ===== TABS ===== -->
      <div class="mt-12 bg-white border rounded-xl overflow-hidden">
        <div class="flex border-b">
          <button
            v-for="tab in [
              { key: 'desc', label: 'Mô tả' },
              { key: 'specs', label: 'Thông số kỹ thuật' },
              { key: 'docs', label: 'Tài liệu' },
            ]"
            :key="tab.key"
            @click="activeTab = tab.key"
            :class="[
              'px-6 py-3 text-sm font-medium transition',
              activeTab === tab.key
                ? 'border-b-2 border-blue-600 text-blue-600'
                : 'text-gray-600 hover:text-gray-700',
            ]"
          >
            {{ tab.label }}
          </button>
        </div>

        <div class="p-6">
          <!-- Tab: Mô tả -->
          <div v-if="activeTab === 'desc'" class="text-gray-700 leading-relaxed">
            {{ product.description }}
          </div>

          <!-- Tab: Thông số kỹ thuật (bảng EAV, xen kẽ màu) -->
          <table v-else-if="activeTab === 'specs'" class="w-full text-sm">
            <tbody>
              <tr
                v-for="(spec, i) in product.specs"
                :key="spec.name"
                :class="i % 2 === 0 ? 'bg-gray-50' : 'bg-white'"
              >
                <td class="py-3 px-4 font-medium text-gray-600 w-1/3">{{ spec.name }}</td>
                <td class="py-3 px-4 text-gray-800">{{ spec.value }}</td>
              </tr>
            </tbody>
          </table>

          <!-- Tab: Tài liệu -->
          <div v-else class="text-gray-700">
            <p class="mb-4">Tải tài liệu kỹ thuật (datasheet) của sản phẩm:</p>
            <a
              :href="product.datasheet_pdf_url"
              class="inline-flex items-center gap-2 bg-red-50 text-red-600 border border-red-200 px-5 py-3 rounded-lg hover:bg-red-100 transition"
            >
              📄 Tải Datasheet (PDF)
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

