import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
  {
    path: '/',
    redirect: '/dashboard',
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/Login.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/Register.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('../views/Dashboard.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/books',
    name: 'Books',
    component: () => import('../views/Books.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/music',
    name: 'Music',
    component: () => import('../views/Music.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/library',
    name: 'Library',
    component: () => import('../components/LibraryInterface.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/wishlists',
    name: 'Wishlists',
    component: () => import('../views/Wishlists.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/wishlists/:id',
    name: 'WishlistDetail',
    component: () => import('../views/WishlistDetail.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/wishlists/public/:shareToken',
    name: 'PublicWishlist',
    component: () => import('../views/PublicWishlist.vue'),
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, _from, next) => {
  const authStore = useAuthStore();
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login');
  } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
    next('/dashboard');
  } else {
    next();
  }
});

export default router;