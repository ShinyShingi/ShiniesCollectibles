<template>
  <AppLayout>
    <div class="p-4">
    <div class="max-w-7xl mx-auto">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <ProgressSpinner />
        <p class="text-gray-600 mt-4">Loading wishlist...</p>
      </div>

      <!-- Wishlist Content -->
      <div v-else-if="currentWishlist">
        <!-- Header -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6 mb-8">
          <div class="flex justify-between items-start">
            <div>
              <div class="flex items-center space-x-3 mb-2">
                <Button 
                  icon="pi pi-arrow-left" 
                  text 
                  @click="$router.push('/wishlists')"
                  class="text-gray-500 hover:text-gray-700"
                />
                <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                  {{ currentWishlist.name }}
                </h1>
                <Badge 
                  :value="currentWishlist.is_public ? 'Public' : 'Private'" 
                  :severity="currentWishlist.is_public ? 'success' : 'secondary'"
                />
              </div>
              <p v-if="currentWishlist.description" class="text-gray-600 mb-4">
                {{ currentWishlist.description }}
              </p>
              <div class="flex items-center space-x-6 text-sm text-gray-500">
                <span>{{ currentWishlist.items?.length || 0 }} items</span>
                <span>Created {{ formatDate(currentWishlist.created_at) }}</span>
              </div>
            </div>
            
            <div class="flex items-center space-x-2">
              <Button 
                v-if="currentWishlist.is_public"
                icon="pi pi-share-alt" 
                label="Share"
                @click="shareWishlist"
                severity="secondary"
              />
              <Button 
                icon="pi pi-plus" 
                label="Add Item"
                @click="showAddItemDialog = true"
                class="bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 border-0 shadow-lg"
              />
              <Button 
                icon="pi pi-cog" 
                @click="showSettingsMenu"
                severity="secondary"
              />
            </div>
          </div>
        </div>

        <!-- Items Grid -->
        <div v-if="currentWishlist.items?.length > 0">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <div 
              v-for="item in currentWishlist.items" 
              :key="item.id"
              class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6 hover:shadow-2xl transition-all duration-300"
            >
              <!-- Item Header -->
              <div class="flex justify-between items-start mb-4">
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-800 mb-1 line-clamp-2">{{ item.title }}</h3>
                  <p v-if="getAuthors(item)" class="text-sm text-gray-600 mb-2">
                    by {{ getAuthors(item) }}
                  </p>
                  <Badge :value="item.type" severity="info" class="text-xs" />
                </div>
                <Button 
                  icon="pi pi-ellipsis-v" 
                  text 
                  size="small"
                  @click="showItemMenu($event, item)"
                  class="text-gray-400 hover:text-gray-600"
                />
              </div>

              <!-- Price Information -->
              <div class="space-y-3">
                <!-- Target Price -->
                <div v-if="getItemWishlistData(item)?.target_price" class="bg-purple-50 p-3 rounded-lg">
                  <div class="text-xs text-purple-600 font-medium mb-1">Target Price</div>
                  <div class="text-lg font-bold text-purple-700">
                    €{{ getItemWishlistData(item).target_price }}
                  </div>
                </div>

                <!-- Current Lowest Price -->
                <div v-if="item.current_lowest_price" class="bg-green-50 p-3 rounded-lg">
                  <div class="text-xs text-green-600 font-medium mb-1">Current Lowest</div>
                  <div class="text-lg font-bold text-green-700">
                    €{{ item.current_lowest_price }}
                  </div>
                  <div v-if="isTargetMet(item)" class="text-xs text-green-600 font-medium mt-1">
                    🎯 Target price met!
                  </div>
                </div>

                <!-- No Price Available -->
                <div v-else class="bg-gray-50 p-3 rounded-lg">
                  <div class="text-xs text-gray-500">No prices available</div>
                  <Button 
                    label="Check Prices" 
                    size="small" 
                    text
                    @click="checkItemPrices(item)"
                    class="mt-2 text-purple-600 hover:text-purple-700"
                  />
                </div>

                <!-- Priority -->
                <div v-if="getItemWishlistData(item)?.priority" class="flex items-center space-x-2">
                  <span class="text-xs text-gray-500">Priority:</span>
                  <div class="flex">
                    <i 
                      v-for="n in 5" 
                      :key="n"
                      class="pi pi-star-fill text-xs"
                      :class="n <= getItemWishlistData(item).priority ? 'text-yellow-400' : 'text-gray-300'"
                    ></i>
                  </div>
                </div>

                <!-- Notes -->
                <div v-if="getItemWishlistData(item)?.notes" class="text-xs text-gray-600">
                  <span class="font-medium">Notes:</span> {{ getItemWishlistData(item).notes }}
                </div>
              </div>

              <!-- Actions -->
              <div class="flex justify-between items-center pt-4 mt-4 border-t border-gray-200">
                <Button 
                  icon="pi pi-external-link" 
                  label="View"
                  text 
                  size="small"
                  @click="viewItem(item)"
                  class="text-purple-500 hover:text-purple-700"
                />
                <Button 
                  icon="pi pi-pencil" 
                  text 
                  size="small"
                  @click="editWishlistItem(item)"
                  class="text-blue-500 hover:text-blue-700"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-12">
            <i class="pi pi-heart text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-2xl font-semibold text-gray-800 mb-2">No items in this wishlist</h3>
            <p class="text-gray-600 mb-6">Add items to start tracking prices and get alerts</p>
            <Button 
              @click="showAddItemDialog = true"
              icon="pi pi-plus" 
              label="Add Your First Item"
              class="bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 border-0 shadow-lg"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Add Item Dialog -->
    <Dialog 
      v-model:visible="showAddItemDialog" 
      header="Add Item to Wishlist"
      modal 
      class="w-full max-w-md"
    >
      <div class="space-y-4">
        <p class="text-gray-600 mb-4">Search for an item from your library to add to this wishlist:</p>
        
        <AutoComplete
          v-model="selectedItem"
          :suggestions="itemSuggestions"
          @complete="searchItems"
          option-label="title"
          placeholder="Search for items..."
          class="w-full"
        />

        <div v-if="selectedItem" class="space-y-4 pt-4 border-t">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Target Price (€)</label>
            <InputNumber 
              v-model="itemForm.target_price" 
              mode="decimal" 
              :min-fraction-digits="2"
              :max-fraction-digits="2"
              placeholder="Optional target price"
              class="w-full"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
            <Rating v-model="itemForm.priority" />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <Textarea 
              v-model="itemForm.notes" 
              placeholder="Optional notes" 
              rows="2"
              class="w-full"
            />
          </div>
        </div>
        
        <div class="flex justify-end space-x-2 pt-4">
          <Button 
            type="button" 
            label="Cancel" 
            severity="secondary" 
            @click="cancelAddItem"
          />
          <Button 
            label="Add Item"
            :disabled="!selectedItem"
            :loading="loading"
            @click="addItemToWishlist"
            class="bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 border-0"
          />
        </div>
      </div>
    </Dialog>

    <!-- Item Menu -->
    <Menu ref="itemMenu" :model="itemMenuItems" :popup="true" />
    </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useWishlists } from '@/composables/useWishlists'
import { useItems } from '@/composables/useItems'

// PrimeVue components
import AppLayout from '@/components/AppLayout.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'
import Badge from 'primevue/badge'
import AutoComplete from 'primevue/autocomplete'
import InputNumber from 'primevue/inputnumber'
import Rating from 'primevue/rating'
import Textarea from 'primevue/textarea'
import Menu from 'primevue/menu'

const route = useRoute()
const router = useRouter()
const toast = useToast()

// Composables
const {
  currentWishlist,
  loading,
  error,
  fetchWishlist,
  addItemToWishlist: addItemToWishlistComposable,
  removeItemFromWishlist,
  updateWishlistItem
} = useWishlists()

const { searchItems: searchLibraryItems } = useItems()

// Component state
const showAddItemDialog = ref(false)
const selectedItem = ref(null)
const itemSuggestions = ref([])
const itemMenu = ref()
const currentMenuItem = ref(null)

// Form data
const itemForm = ref({
  target_price: null,
  priority: 3,
  notes: ''
})

// Item menu
const itemMenuItems = ref([
  {
    label: 'Edit',
    icon: 'pi pi-pencil',
    command: () => editWishlistItem(currentMenuItem.value)
  },
  {
    label: 'Check Prices',
    icon: 'pi pi-refresh',
    command: () => checkItemPrices(currentMenuItem.value)
  },
  {
    separator: true
  },
  {
    label: 'Remove from Wishlist',
    icon: 'pi pi-trash',
    command: () => removeItem(currentMenuItem.value),
    class: 'text-red-500'
  }
])

// Methods
const getAuthors = (item) => {
  return item.contributors?.filter(c => c.role === 'author').map(c => c.name).join(', ')
}

const getItemWishlistData = (item) => {
  return item.pivot || {}
}

const isTargetMet = (item) => {
  const wishlistData = getItemWishlistData(item)
  if (!wishlistData.target_price || !item.current_lowest_price) return false
  return item.current_lowest_price <= wishlistData.target_price
}

const searchItems = async (event) => {
  try {
    const results = await searchLibraryItems(event.query)
    itemSuggestions.value = results.data || []
  } catch (err) {
    console.error('Search failed:', err)
  }
}

const addItemToWishlist = async () => {
  if (!selectedItem.value) return
  
  try {
    await addItemToWishlistComposable(route.params.id, {
      item_id: selectedItem.value.id,
      ...itemForm.value
    })
    toast.add({ severity: 'success', summary: 'Success', detail: 'Item added to wishlist' })
    cancelAddItem()
    // Refresh wishlist
    await fetchWishlist(route.params.id)
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.value })
  }
}

const cancelAddItem = () => {
  showAddItemDialog.value = false
  selectedItem.value = null
  itemForm.value = {
    target_price: null,
    priority: 3,
    notes: ''
  }
}

const showItemMenu = (event, item) => {
  currentMenuItem.value = item
  itemMenu.value.toggle(event)
}

const viewItem = (item) => {
  router.push(`/library/${item.id}`)
}

const editWishlistItem = (item) => {
  // TODO: Implement edit item functionality
  toast.add({ severity: 'info', summary: 'Info', detail: 'Edit functionality coming soon' })
}

const checkItemPrices = async (item) => {
  try {
    // TODO: Trigger price check for this item
    toast.add({ severity: 'info', summary: 'Info', detail: 'Price check initiated' })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to check prices' })
  }
}

const removeItem = async (item) => {
  if (confirm(`Remove "${item.title}" from this wishlist?`)) {
    try {
      await removeItemFromWishlist(route.params.id, item.id)
      toast.add({ severity: 'success', summary: 'Success', detail: 'Item removed from wishlist' })
      // Refresh wishlist
      await fetchWishlist(route.params.id)
    } catch (err) {
      toast.add({ severity: 'error', summary: 'Error', detail: error.value })
    }
  }
}

const shareWishlist = () => {
  if (currentWishlist.value?.public_url) {
    navigator.clipboard.writeText(currentWishlist.value.public_url)
    toast.add({ severity: 'success', summary: 'Copied', detail: 'Share URL copied to clipboard' })
  }
}

const showSettingsMenu = () => {
  router.push(`/wishlists/${route.params.id}/settings`)
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
  fetchWishlist(route.params.id)
})
</script>