<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
    <!-- Modern Navigation Header -->
    <header class="sticky top-0 z-40">
      <!-- Navigation Bar -->
      <nav class="glass-card border-0 shadow-sm backdrop-blur-lg bg-white/90">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between items-center h-16">
            <!-- Brand -->
            <div class="flex items-center">
              <router-link 
                to="/dashboard" 
                class="flex items-center space-x-3 group"
              >
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300">
                  <i class="pi pi-star text-white text-lg"></i>
                </div>
                <div class="hidden sm:block">
                  <h1 class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                    Shinies Collectibles
                  </h1>
                  <p class="text-xs text-slate-500 -mt-0.5">Your digital library</p>
                </div>
              </router-link>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-1">
              <router-link
                to="/dashboard"
                class="nav-item"
                active-class="nav-item active"
                exact-active-class="nav-item active"
              >
                <i class="pi pi-home"></i>
                <span>Library</span>
              </router-link>
              <router-link
                to="/items/new"
                class="nav-item"
              >
                <i class="pi pi-plus"></i>
                <span>Add Item</span>
              </router-link>
              <router-link
                to="/wishlists"
                class="nav-item"
              >
                <i class="pi pi-heart"></i>
                <span>Wishlists</span>
              </router-link>
              <button
                @click="showTagManager = true"
                class="nav-item"
              >
                <i class="pi pi-tags"></i>
                <span>Tags</span>
              </button>
            </div>

            <!-- Stats & User Menu -->
            <div class="flex items-center space-x-4">
              <!-- Quick Stats Cards -->
              <div class="hidden lg:flex items-center space-x-3">
                <div class="stat-card">
                  <i class="pi pi-book text-indigo-500"></i>
                  <div class="stat-content">
                    <span class="stat-number">{{ books.length }}</span>
                    <span class="stat-label">Books</span>
                  </div>
                </div>
                <div class="stat-card">
                  <i class="pi pi-volume-up text-purple-500"></i>
                  <div class="stat-content">
                    <span class="stat-number">{{ musicAlbums.length }}</span>
                    <span class="stat-label">Music</span>
                  </div>
                </div>
                <div class="stat-card">
                  <i class="pi pi-dollar text-emerald-500"></i>
                  <div class="stat-content">
                    <span class="stat-number">${{ totalValue.toFixed(0) }}</span>
                    <span class="stat-label">Value</span>
                  </div>
                </div>
              </div>
              
              <!-- User Profile -->
              <div class="flex items-center space-x-3">
                <div class="hidden sm:flex flex-col items-end">
                  <span class="text-sm font-medium text-slate-700">{{ user?.name }}</span>
                  <span class="text-xs text-slate-500">Collector</span>
                </div>
                <div class="relative">
                  <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold shadow-lg">
                    {{ user?.name?.charAt(0)?.toUpperCase() }}
                  </div>
                  <div class="status-dot online absolute -bottom-0.5 -right-0.5"></div>
                </div>
                <button
                  @click="handleLogout"
                  class="p-2 text-slate-500 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all duration-200"
                  v-tooltip.left="'Logout'"
                >
                  <i class="pi pi-sign-out"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </nav>
    </header>

    <!-- Main Content Area -->
    <main class="relative">
      <!-- Background Decoration -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-0 w-72 h-72 bg-purple-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-indigo-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse animation-delay-2000"></div>
        <div class="absolute bottom-0 left-1/2 w-72 h-72 bg-pink-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse animation-delay-4000"></div>
      </div>
      
      <!-- Slot for page content -->
      <div class="relative z-10">
        <slot />
      </div>
    </main>

    <!-- Floating Action Button -->
    <router-link to="/items/new" class="fab group">
      <i class="pi pi-plus text-xl group-hover:rotate-90 transition-transform duration-300"></i>
    </router-link>

    <!-- Modals -->
    <TagManager
      :visible="showTagManager"
      @close="showTagManager = false"
    />
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useItemsStore } from '../stores/items';
import TagManager from './TagManager.vue';

const router = useRouter();
const authStore = useAuthStore();
const itemsStore = useItemsStore();

// Component state
const showTagManager = ref(false);

// Computed properties
const user = computed(() => authStore.user);
const books = computed(() => itemsStore.items.filter(item => item.type === 'book'));
const musicAlbums = computed(() => itemsStore.items.filter(item => item.type === 'music'));
const totalValue = computed(() => {
  return itemsStore.items.reduce((total, item) => {
    return total + (parseFloat(item.estimated_value) || 0);
  }, 0);
});

// Methods
const handleLogout = async () => {
  try {
    await authStore.logout();
    router.push('/login');
  } catch (error) {
    console.error('Logout failed:', error);
  }
};

// Lifecycle
onMounted(() => {
  // Load items for stats
  itemsStore.fetchItems();
});
</script>