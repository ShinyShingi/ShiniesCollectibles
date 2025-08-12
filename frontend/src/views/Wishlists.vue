<template>
  <AppLayout>
    <div class="p-4">
    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-8">
      <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
              My Wishlists
            </h1>
            <p class="text-gray-600 mt-2">
              Manage your wishlists and track price alerts for your favorite items
            </p>
          </div>
          <Button 
            @click="showCreateDialog = true"
            icon="pi pi-plus" 
            label="Create Wishlist"
            class="bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 border-0 shadow-lg"
          />
        </div>
      </div>
    </div>

    <!-- Wishlist Grid -->
    <div class="max-w-7xl mx-auto">
      <div v-if="loading" class="text-center py-12">
        <ProgressSpinner />
        <p class="text-gray-600 mt-4">Loading wishlists...</p>
      </div>

      <div v-else-if="wishlists.length === 0" class="text-center py-12">
        <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-12">
          <i class="pi pi-heart text-6xl text-gray-300 mb-4"></i>
          <h3 class="text-2xl font-semibold text-gray-800 mb-2">No wishlists yet</h3>
          <p class="text-gray-600 mb-6">Create your first wishlist to start tracking items and price alerts</p>
          <Button 
            @click="showCreateDialog = true"
            icon="pi pi-plus" 
            label="Create Your First Wishlist"
            class="bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 border-0 shadow-lg"
          />
        </div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="wishlist in wishlists" 
          :key="wishlist.id"
          class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 p-6 hover:shadow-2xl transition-all duration-300 cursor-pointer transform hover:-translate-y-1"
          @click="viewWishlist(wishlist.id)"
        >
          <!-- Wishlist Header -->
          <div class="flex justify-between items-start mb-4">
            <div class="flex-1">
              <h3 class="text-xl font-semibold text-gray-800 mb-1">{{ wishlist.name }}</h3>
              <p v-if="wishlist.description" class="text-gray-600 text-sm line-clamp-2">
                {{ wishlist.description }}
              </p>
            </div>
            <div class="flex items-center space-x-2 ml-4">
              <Badge 
                v-if="wishlist.is_public" 
                value="Public" 
                severity="success" 
                class="text-xs"
              />
              <Badge 
                v-else 
                value="Private" 
                severity="secondary" 
                class="text-xs"
              />
              <Button 
                icon="pi pi-ellipsis-v" 
                text 
                size="small"
                @click.stop="showWishlistMenu($event, wishlist)"
                class="text-gray-400 hover:text-gray-600"
              />
            </div>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="text-center">
              <div class="text-2xl font-bold text-purple-600">{{ wishlist.items_count || 0 }}</div>
              <div class="text-xs text-gray-500">Items</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-pink-600">
                <i class="pi pi-heart-fill"></i>
              </div>
              <div class="text-xs text-gray-500">Wishlist</div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-between items-center pt-4 border-t border-gray-200">
            <span class="text-xs text-gray-500">
              Created {{ formatDate(wishlist.created_at) }}
            </span>
            <div class="flex space-x-2">
              <Button 
                icon="pi pi-eye" 
                text 
                size="small"
                class="text-purple-500 hover:text-purple-700"
                @click.stop="viewWishlist(wishlist.id)"
              />
              <Button 
                icon="pi pi-share-alt" 
                text 
                size="small"
                class="text-blue-500 hover:text-blue-700"
                @click.stop="shareWishlist(wishlist)"
                v-if="wishlist.is_public"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Wishlist Dialog -->
    <Dialog 
      v-model:visible="showCreateDialog" 
      :header="editingWishlist ? 'Edit Wishlist' : 'Create Wishlist'"
      modal 
      class="w-full max-w-md"
    >
      <form @submit.prevent="saveWishlist" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
          <InputText 
            v-model="wishlistForm.name" 
            placeholder="Enter wishlist name" 
            class="w-full"
            required
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
          <Textarea 
            v-model="wishlistForm.description" 
            placeholder="Optional description" 
            rows="3"
            class="w-full"
          />
        </div>
        
        <div class="flex items-center space-x-2">
          <Checkbox v-model="wishlistForm.is_public" inputId="public" binary />
          <label for="public" class="text-sm text-gray-700">Make this wishlist public</label>
        </div>
        
        <div class="flex justify-end space-x-2 pt-4">
          <Button 
            type="button" 
            label="Cancel" 
            severity="secondary" 
            @click="cancelEdit"
          />
          <Button 
            type="submit" 
            :label="editingWishlist ? 'Update' : 'Create'"
            :loading="loading"
            class="bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 border-0"
          />
        </div>
      </form>
    </Dialog>

    <!-- Wishlist Context Menu -->
    <Menu ref="wishlistMenu" :model="menuItems" :popup="true" />

    <!-- Share Dialog -->
    <Dialog 
      v-model:visible="showShareDialog" 
      header="Share Wishlist"
      modal 
      class="w-full max-w-md"
    >
      <div v-if="shareWishlistData" class="space-y-4">
        <p class="text-gray-600">Share this public wishlist with others:</p>
        
        <div class="bg-gray-50 p-3 rounded-lg">
          <label class="block text-sm font-medium text-gray-700 mb-2">Public URL</label>
          <div class="flex">
            <InputText 
              :model-value="shareWishlistData.public_url" 
              readonly 
              class="flex-1 text-sm"
            />
            <Button 
              icon="pi pi-copy" 
              @click="copyToClipboard(shareWishlistData.public_url)"
              class="ml-2"
              severity="secondary"
            />
          </div>
        </div>

        <div class="flex justify-end">
          <Button 
            label="Close" 
            @click="showShareDialog = false"
          />
        </div>
      </div>
    </Dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useWishlists } from '@/composables/useWishlists'

// PrimeVue components
import AppLayout from '@/components/AppLayout.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import ProgressSpinner from 'primevue/progressspinner'
import Badge from 'primevue/badge'
import Menu from 'primevue/menu'

const router = useRouter()
const toast = useToast()

// Wishlist composable
const {
  wishlists,
  loading,
  error,
  fetchWishlists,
  createWishlist,
  updateWishlist,
  deleteWishlist,
  toggleWishlistPublic
} = useWishlists()

// Component state
const showCreateDialog = ref(false)
const showShareDialog = ref(false)
const editingWishlist = ref(null)
const shareWishlistData = ref(null)
const wishlistMenu = ref()

// Form data
const wishlistForm = ref({
  name: '',
  description: '',
  is_public: false
})

// Menu items
const menuItems = ref([
  {
    label: 'View',
    icon: 'pi pi-eye',
    command: () => viewWishlist(menuWishlist.value.id)
  },
  {
    label: 'Edit',
    icon: 'pi pi-pencil',
    command: () => editWishlist(menuWishlist.value)
  },
  {
    label: 'Toggle Visibility',
    icon: 'pi pi-eye-slash',
    command: () => toggleVisibility(menuWishlist.value)
  },
  {
    label: 'Share',
    icon: 'pi pi-share-alt',
    command: () => shareWishlist(menuWishlist.value),
    visible: () => menuWishlist.value?.is_public
  },
  {
    separator: true
  },
  {
    label: 'Delete',
    icon: 'pi pi-trash',
    command: () => confirmDelete(menuWishlist.value),
    class: 'text-red-500'
  }
])

const menuWishlist = ref(null)

// Methods
const saveWishlist = async () => {
  try {
    if (editingWishlist.value) {
      await updateWishlist(editingWishlist.value.id, wishlistForm.value)
      toast.add({ severity: 'success', summary: 'Success', detail: 'Wishlist updated successfully' })
    } else {
      await createWishlist(wishlistForm.value)
      toast.add({ severity: 'success', summary: 'Success', detail: 'Wishlist created successfully' })
    }
    cancelEdit()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.value })
  }
}

const cancelEdit = () => {
  showCreateDialog.value = false
  editingWishlist.value = null
  wishlistForm.value = {
    name: '',
    description: '',
    is_public: false
  }
}

const editWishlist = (wishlist) => {
  editingWishlist.value = wishlist
  wishlistForm.value = {
    name: wishlist.name,
    description: wishlist.description || '',
    is_public: wishlist.is_public
  }
  showCreateDialog.value = true
}

const viewWishlist = (id) => {
  router.push(`/wishlists/${id}`)
}

const showWishlistMenu = (event, wishlist) => {
  menuWishlist.value = wishlist
  wishlistMenu.value.toggle(event)
}

const shareWishlist = (wishlist) => {
  if (wishlist.is_public) {
    shareWishlistData.value = wishlist
    showShareDialog.value = true
  }
}

const toggleVisibility = async (wishlist) => {
  try {
    await toggleWishlistPublic(wishlist.id)
    toast.add({ 
      severity: 'success', 
      summary: 'Success', 
      detail: `Wishlist is now ${wishlist.is_public ? 'private' : 'public'}` 
    })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.value })
  }
}

const confirmDelete = async (wishlist) => {
  if (confirm(`Are you sure you want to delete "${wishlist.name}"?`)) {
    try {
      await deleteWishlist(wishlist.id)
      toast.add({ severity: 'success', summary: 'Success', detail: 'Wishlist deleted successfully' })
    } catch (err) {
      toast.add({ severity: 'error', summary: 'Error', detail: error.value })
    }
  }
}

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text)
    toast.add({ severity: 'success', summary: 'Copied', detail: 'URL copied to clipboard' })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to copy URL' })
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
  fetchWishlists()
})
</script>