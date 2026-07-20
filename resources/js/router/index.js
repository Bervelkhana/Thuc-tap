import { createRouter, createWebHistory } from 'vue-router'
import ProductList from '../components/ProductList.vue'
import Checkout from '../components/Checkout.vue'
import HomeView from '../views/HomeView.vue'
import ProductDetailView from '../views/ProductDetailView.vue'
import CheckoutView from '../views/CheckoutView.vue'

const routes = [
  // Route cũ (Bước 3-4) — giữ song song
  { path: '/', name: 'home', component: ProductList },
  { path: '/checkout', name: 'checkout', component: Checkout },

  // Route mới (Bước 2 - thiết kế mockup, dùng mock data)
  { path: '/home', name: 'home-view', component: HomeView },
  { path: '/product', name: 'product-detail', component: ProductDetailView },
  { path: '/checkout-new', name: 'checkout-view', component: CheckoutView },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
