import { createRouter, createWebHistory } from 'vue-router'
import StartView from '../views/StartView.vue'
import ProductList from '../components/ProductList.vue'
import Checkout from '../components/Checkout.vue'
import HomeView from '../views/HomeView.vue'
import CatalogView from '../views/CatalogView.vue'
import CategoryProductsView from '../views/CategoryProductsView.vue'
import ProductDetailView from '../views/ProductDetailView.vue'
import CheckoutView from '../views/CheckoutView.vue'
import AdminOrderView from '../views/AdminOrderView.vue'
import PCBuilderView from '../views/PCBuilderView.vue'
import ProductBrowserView from '../views/ProductBrowserView.vue'
import NewestProductsView from '../views/NewestProductsView.vue'
import PrebuiltConfigsView from '../views/PrebuiltConfigsView.vue'
import PrebuiltConfigDetailView from '../views/PrebuiltConfigDetailView.vue'
import BackendLayout from '../views/BackendLayout.vue'
import LoginBackendView from '../views/LoginBackendView.vue'
import AdminDashboardView from '../views/AdminDashboardView.vue'
import { useAdminStore } from '../stores/adminStore'

const routes = [
  // Landing page - mặc định hiển thị sản phẩm mới
  { path: '/', name: 'start', component: NewestProductsView },
  { path: '/build', name: 'build', component: () => import('../views/BuildView.vue') },
  
  // Route cũ (Bước 3-4) — giữ song song
  { path: '/old-home', name: 'home', component: ProductList },,
  { path: '/checkout', name: 'checkout', component: Checkout },

  // Route mới (Bước 2 - thiết kế mockup, dùng mock data)
  { path: '/home', name: 'home-view', component: HomeView },
  { path: '/catalog', name: 'catalog', component: CatalogView },
  { path: '/category/:categoryId', name: 'category-products', component: CategoryProductsView },
  { path: '/product', name: 'product-detail', component: ProductDetailView },
  { path: '/checkout-new', name: 'checkout-view', component: CheckoutView },
  { path: '/browser-:slug', name: 'browser-category', component: ProductBrowserView, props: true },
  { path: '/browse', name: 'product-browser', component: NewestProductsView },
  { path: '/browse-prebuilt', name: 'browse-prebuilt', component: PrebuiltConfigsView },
  { path: '/prebuilt-config/:id', name: 'prebuilt-config-detail', component: PrebuiltConfigDetailView, props: true },
  { path: '/browser', redirect: '/browse' },
  
  // Admin routes
  { path: '/admin/orders', name: 'admin-orders', component: AdminOrderView },
  
  // PC Builder route
  { path: '/pc-builder', name: 'pc-builder', component: PCBuilderView },

   // Login Backend route
   { path: '/login-backend', name: 'login-backend', component: LoginBackendView },

   // Admin Dashboard route
   { path: '/admin/dashboard', name: 'admin-dashboard', component: AdminDashboardView, meta: { requiresAuth: true } },

   // Backend routes (deprecated, redirect to dashboard)
   {
     path: '/backend',
     component: BackendLayout,
     meta: { requiresAuth: true },
     children: [
       { path: '', redirect: 'products' },
       { path: 'products', name: 'backend-products', component: () => import('../components/backend/ProductManagement.vue') },
       { path: 'orders', name: 'backend-orders', component: () => import('../components/backend/OrderManagement.vue') },
     ],
   },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Navigation guard to check authentication for protected routes
router.beforeEach((to, from, next) => {
  const adminStore = useAdminStore()
  
  if (to.meta.requiresAuth && !adminStore.isAuthenticated) {
    next({ name: 'login-backend' })
  } else {
    next()
  }
})

export default router

