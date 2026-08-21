import { createRouter, createWebHistory } from 'vue-router'
import AiBuildView from '../views/AiBuildView.vue'
import HomeView from '../views/HomeView.vue'
import CatalogView from '../views/CatalogView.vue'
import CategoryProductsView from '../views/CategoryProductsView.vue'
import ProductDetailView from '../views/ProductDetailView.vue'
import CheckoutView from '../views/CheckoutView.vue'
import AdminOrderView from '../views/AdminOrderView.vue'
import AdminProductsView from '../views/AdminProductsView.vue'
import AdminPrebuiltView from '../views/AdminPrebuiltView.vue'
import PCBuilderView from '../views/PCBuilderView.vue'
import ProductBrowserView from '../views/ProductBrowserView.vue'
import NewestProductsView from '../views/NewestProductsView.vue'
import PrebuiltConfigsView from '../views/PrebuiltConfigsView.vue'
import PrebuiltConfigDetailView from '../views/PrebuiltConfigDetailView.vue'
import LoginBackendView from '../views/LoginBackendView.vue'
import AdminDashboardView from '../views/AdminDashboardView.vue'
import OrderSuccessView from '../views/OrderSuccessView.vue'
import AdminLayout from '../layouts/AdminLayout.vue'
import { useAdminStore } from '../stores/adminStore'

const routes = [
  { path: '/', name: 'start', component: NewestProductsView },
  
  { path: '/home', name: 'home-view', component: HomeView },
  { path: '/catalog', name: 'catalog', component: CatalogView },
  { path: '/category/:categoryId', name: 'category-products', component: CategoryProductsView },
  { path: '/product', name: 'product-detail', component: ProductDetailView },
  { path: '/checkout-new', name: 'checkout-view', component: CheckoutView },
  { path: '/order-success/:id', name: 'order-success', component: OrderSuccessView },
  { path: '/browser-:slug', name: 'browser-category', component: ProductBrowserView, props: true },
  { path: '/browse', name: 'product-browser', component: NewestProductsView },
  { path: '/browse-prebuilt', name: 'browse-prebuilt', component: PrebuiltConfigsView },
  { path: '/prebuilt-config/:id', name: 'prebuilt-config-detail', component: PrebuiltConfigDetailView, props: true },
  { path: '/browser', redirect: '/browse' },
  
  { path: '/login-backend', name: 'login-backend', component: LoginBackendView },
  { path: '/ai-build', name: 'ai-build', component: AiBuildView },
  { path: '/pc-builder', name: 'pc-builder', component: PCBuilderView },

  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true },
    children: [
      { path: 'dashboard', name: 'admin-dashboard', component: AdminDashboardView },
      { path: 'orders', name: 'admin-orders', component: AdminOrderView },
      { path: 'products', name: 'admin-products', component: AdminProductsView },
      { path: 'prebuilt', name: 'admin-prebuilt', component: AdminPrebuiltView },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const adminStore = useAdminStore()

  const requiresAuth = to.matched.some(record => record.meta && record.meta.requiresAuth)

  if (requiresAuth && !adminStore.isAuthenticated) {
    next({ name: 'login-backend' })
  } else {
    next()
  }
})

export default router
