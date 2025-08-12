<template>
  <div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 p-4">
    <div class="max-w-7xl mx-auto">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <ProgressSpinner />
        <p class="text-gray-600 mt-4">Loading wishlist...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-12">
          <i class="pi pi-exclamation-circle text-6xl text-red-400 mb-4"></i>
          <h3 class="text-2xl font-semibold text-gray-800 mb-2">Wishlist Not Found</h3>
          <p class="text-gray-600 mb-6">This wishlist doesn't exist or is no longer public.</p>
        </div>
      </div>

      <!-- Wishlist Content -->
      <div v-else-if="wishlist">
        <!-- Header -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6 mb-8">
          <div class="text-center">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent mb-2">
              {{ wishlist.name }}
            </h1>
            <p v-if="wishlist.description" class="text-gray-600 mb-4 text-lg">
              {{ wishlist.description }}
            </p>
            <div class="flex justify-center items-center space-x-6 text-sm text-gray-500 mb-4">
              <span>👤 {{ wishlist.user.name }}</span>
              <span>📚 {{ wishlist.items?.length || 0 }} items</span>
              <span>📅 Created {{ formatDate(wishlist.created_at) }}</span>
            </div>
            <Badge value="Public Wishlist" severity="success" class="text-sm" />
          </div>
        </div>

        <!-- Items Grid -->
        <div v-if="wishlist.items?.length > 0">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <div 
              v-for="item in wishlist.items" 
              :key="item.id"
              class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6 hover:shadow-2xl transition-all duration-300"
            >
              <!-- Item Header -->
              <div class="mb-4">
                <h3 class="font-semibold text-gray-800 mb-1 line-clamp-2">{{ item.title }}</h3>
                <p v-if="getAuthors(item)" class="text-sm text-gray-600 mb-2">
                  by {{ getAuthors(item) }}
                </p>
                <div class="flex items-center space-x-2">
                  <Badge :value="item.type" severity="info" class="text-xs" />
                  <div v-if="getItemWishlistData(item)?.priority" class="flex">
                    <i 
                      v-for="n in 5" 
                      :key="n"
                      class="pi pi-star-fill text-xs"
                      :class="n <= getItemWishlistData(item).priority ? 'text-yellow-400' : 'text-gray-300'"
                    ></i>
                  </div>
                </div>
              </div>

              <!-- Price Information -->
              <div class="space-y-3">
                <!-- Current Lowest Price -->
                <div v-if="item.current_lowest_price" class="bg-green-50 p-3 rounded-lg">
                  <div class="flex justify-between items-center">
                    <div>
                      <div class="text-xs text-green-600 font-medium mb-1">Best Price</div>
                      <div class="text-lg font-bold text-green-700">
                        €{{ item.current_lowest_price }}
                      </div>
                      <div v-if="item.best_price_source" class="text-xs text-green-600">
                        at {{ item.best_price_source }}
                      </div>
                    </div>
                    <div v-if="isTargetMet(item)" class="text-green-600">
                      <i class="pi pi-check-circle text-xl"></i>
                    </div>
                  </div>
                </div>

                <!-- Target Price (if set) -->
                <div v-if="getItemWishlistData(item)?.target_price" class="bg-purple-50 p-3 rounded-lg">
                  <div class="text-xs text-purple-600 font-medium mb-1">Target Price</div>
                  <div class="text-lg font-bold text-purple-700">
                    €{{ getItemWishlistData(item).target_price }}
                  </div>
                  <div v-if="item.current_lowest_price" class="mt-2">
                    <div v-if="isTargetMet(item)" class="text-xs text-green-600 font-medium">
                      🎯 Target price met! Save €{{ getSavings(item) }}
                    </div>
                    <div v-else class="text-xs text-orange-600">
                      💰 €{{ (getItemWishlistData(item).target_price - item.current_lowest_price).toFixed(2) }} over target
                    </div>
                  </div>
                </div>

                <!-- No Price Available -->
                <div v-if="!item.current_lowest_price" class="bg-gray-50 p-3 rounded-lg">
                  <div class="text-xs text-gray-500 text-center">
                    No current price data available
                  </div>
                </div>

                <!-- Notes -->
                <div v-if="getItemWishlistData(item)?.notes" class="bg-blue-50 p-3 rounded-lg">
                  <div class="text-xs text-blue-600 font-medium mb-1">Notes</div>
                  <div class="text-sm text-blue-700">{{ getItemWishlistData(item).notes }}</div>
                </div>
              </div>

              <!-- Item Details -->
              <div class="pt-4 mt-4 border-t border-gray-200">
                <div class="text-xs text-gray-500 space-y-1">
                  <div v-if="getISBN(item)">ISBN: {{ getISBN(item) }}</div>
                  <div v-if="item.publication_year">Year: {{ item.publication_year }}</div>
                  <div v-if="item.pages">Pages: {{ item.pages }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Summary Card -->
          <div class="mt-8 bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Wishlist Summary</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="text-center">
                <div class="text-3xl font-bold text-purple-600 mb-2">{{ wishlist.items.length }}</div>
                <div class="text-sm text-gray-600">Total Items</div>
              </div>
              <div class="text-center">
                <div class="text-3xl font-bold text-green-600 mb-2">{{ itemsWithPrices }}</div>
                <div class="text-sm text-gray-600">With Current Prices</div>
              </div>
              <div class="text-center">
                <div class="text-3xl font-bold text-blue-600 mb-2">{{ itemsAtTarget }}</div>
                <div class="text-sm text-gray-600">At Target Price</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-12">
            <i class="pi pi-heart text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-2xl font-semibold text-gray-800 mb-2">No items in this wishlist</h3>
            <p class="text-gray-600">This wishlist doesn't have any items yet.</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
          <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6">
            <p class="text-gray-600 mb-4">
              Create your own wishlist to track book prices and get alerts when they drop!
            </p>
            <Button 
              label="Create Account" 
              @click="$router.push('/register')"
              class="bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 border-0 shadow-lg"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useWishlists } from '@/composables/useWishlists'

// PrimeVue components
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'
import Badge from 'primevue/badge'

const route = useRoute()

// Composables
const { fetchPublicWishlist, loading, error } = useWishlists()

// Component state
const wishlist = ref(null)

// Computed properties
const itemsWithPrices = computed(() => {
  if (!wishlist.value?.items) return 0
  return wishlist.value.items.filter(item => item.current_lowest_price).length
})

const itemsAtTarget = computed(() => {
  if (!wishlist.value?.items) return 0
  return wishlist.value.items.filter(item => isTargetMet(item)).length
})

// Methods
const getAuthors = (item) => {
  return item.contributors?.filter(c => c.role === 'author').map(c => c.name).join(', ')
}

const getItemWishlistData = (item) => {
  return item.pivot || {}
}

const getISBN = (item) => {
  return item.identifiers?.find(id => id.type === 'isbn')?.value
}

const isTargetMet = (item) => {
  const wishlistData = getItemWishlistData(item)
  if (!wishlistData.target_price || !item.current_lowest_price) return false
  return item.current_lowest_price <= wishlistData.target_price
}

const getSavings = (item) => {
  const wishlistData = getItemWishlistData(item)
  if (!wishlistData.target_price || !item.current_lowest_price) return 0
  return Math.max(0, wishlistData.target_price - item.current_lowest_price).toFixed(2)
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

// Lifecycle
onMounted(async () => {
  try {
    wishlist.value = await fetchPublicWishlist(route.params.shareToken)
  } catch (err) {
    console.error('Failed to load public wishlist:', err)
  }
})
</script>