<script setup lang="ts">
import { computed } from 'vue'

interface CategoryProduct {
  id: number
  name: string
  price: string | number
  thumbnail_url?: string | null
  category?: {
    name: string
  } | null
}

const buildData = (window as typeof window & { __BUILD_DATA__?: Record<string, CategoryProduct[]> }).__BUILD_DATA__ ?? {}

const categories = ['CPU', 'Mainboard', 'VGA', 'RAM', 'SSD', 'PSU', 'Case']

const formattedPrice = (price: string | number): string => {
  const value = typeof price === 'string' ? Number(price) : price
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
}

const sections = computed(() =>
  categories.map((category) => ({
    name: category,
    products: buildData[category] ?? [],
  })),
)
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <header class="border-b border-gray-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <a href="/" class="text-sm font-semibold uppercase tracking-[0.3em] text-gray-500">TechGear</a>
        <h1 class="mt-3 text-3xl font-bold text-gray-900">Xây dựng cấu hình</h1>
        <p class="mt-2 text-sm text-gray-600">Chọn linh kiện theo từng danh mục để bắt đầu build máy.</p>
      </div>
    </header>

    <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
      <section
        v-for="section in sections"
        :key="section.name"
        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
      >
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">{{ section.name }}</h2>
          <span class="text-sm text-gray-500">{{ section.products.length }} sản phẩm</span>
        </div>

        <div v-if="section.products.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <article
            v-for="product in section.products"
            :key="product.id"
            class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50"
          >
            <div class="aspect-square bg-white">
              <img
                v-if="product.thumbnail_url"
                :src="product.thumbnail_url"
                :alt="product.name"
                class="h-full w-full object-cover"
              />
              <div v-else class="flex h-full items-center justify-center text-5xl text-gray-300">🖥️</div>
            </div>
            <div class="space-y-2 p-4">
              <h3 class="line-clamp-2 text-sm font-medium text-gray-900">{{ product.name }}</h3>
              <p class="text-base font-semibold text-gray-900">{{ formattedPrice(product.price) }}</p>
            </div>
          </article>
        </div>

        <p v-else class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-sm text-gray-500">
          Đang cập nhật sản phẩm
        </p>
      </section>
    </main>
  </div>
</template>
