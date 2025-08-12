import { ref, computed } from 'vue'
import api from '@/services/api'

export function useWishlists() {
  const wishlists = ref([])
  const currentWishlist = ref(null)
  const loading = ref(false)
  const error = ref(null)

  // Get all user wishlists
  const fetchWishlists = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/wishlists')
      wishlists.value = response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch wishlists'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Get specific wishlist with items
  const fetchWishlist = async (id) => {
    loading.value = true
    error.value = null
    try {
      // For now, get from the list until we fix the show endpoint
      const response = await api.get('/wishlists')
      const wishlist = response.data.find(w => w.id == id)
      if (wishlist) {
        // Add empty items array for now
        wishlist.items = []
        currentWishlist.value = wishlist
        return wishlist
      } else {
        throw new Error('Wishlist not found')
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch wishlist'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Create new wishlist
  const createWishlist = async (wishlistData) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/wishlists', wishlistData)
      wishlists.value.unshift(response.data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to create wishlist'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Update wishlist
  const updateWishlist = async (id, wishlistData) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.put(`/wishlists/${id}`, wishlistData)
      const index = wishlists.value.findIndex(w => w.id === id)
      if (index !== -1) {
        wishlists.value[index] = response.data
      }
      if (currentWishlist.value?.id === id) {
        currentWishlist.value = { ...currentWishlist.value, ...response.data }
      }
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update wishlist'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Delete wishlist
  const deleteWishlist = async (id) => {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/wishlists/${id}`)
      wishlists.value = wishlists.value.filter(w => w.id !== id)
      if (currentWishlist.value?.id === id) {
        currentWishlist.value = null
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete wishlist'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Add item to wishlist
  const addItemToWishlist = async (wishlistId, itemData) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(`/wishlists/${wishlistId}/items`, itemData)
      if (currentWishlist.value?.id === wishlistId) {
        currentWishlist.value.items.push(response.data.item)
      }
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to add item to wishlist'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Remove item from wishlist
  const removeItemFromWishlist = async (wishlistId, itemId) => {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/wishlists/${wishlistId}/items`, { data: { item_id: itemId } })
      if (currentWishlist.value?.id === wishlistId) {
        currentWishlist.value.items = currentWishlist.value.items.filter(item => item.id !== itemId)
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to remove item from wishlist'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Update wishlist item
  const updateWishlistItem = async (wishlistId, itemId, itemData) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.put(`/wishlists/${wishlistId}/items`, {
        item_id: itemId,
        ...itemData
      })
      if (currentWishlist.value?.id === wishlistId) {
        const itemIndex = currentWishlist.value.items.findIndex(item => item.id === itemId)
        if (itemIndex !== -1) {
          currentWishlist.value.items[itemIndex] = response.data.item
        }
      }
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update wishlist item'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Toggle wishlist public/private
  const toggleWishlistPublic = async (wishlistId) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(`/wishlists/${wishlistId}/toggle-public`)
      const index = wishlists.value.findIndex(w => w.id === wishlistId)
      if (index !== -1) {
        wishlists.value[index] = { ...wishlists.value[index], ...response.data }
      }
      if (currentWishlist.value?.id === wishlistId) {
        currentWishlist.value = { ...currentWishlist.value, ...response.data }
      }
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to toggle wishlist visibility'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Get public wishlist (no auth required)
  const fetchPublicWishlist = async (shareToken) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/wishlists/public/${shareToken}`)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch public wishlist'
      throw err
    } finally {
      loading.value = false
    }
  }

  const wishlistsCount = computed(() => wishlists.value.length)
  const publicWishlists = computed(() => wishlists.value.filter(w => w.is_public))
  const privateWishlists = computed(() => wishlists.value.filter(w => !w.is_public))

  return {
    // State
    wishlists,
    currentWishlist,
    loading,
    error,

    // Actions
    fetchWishlists,
    fetchWishlist,
    createWishlist,
    updateWishlist,
    deleteWishlist,
    addItemToWishlist,
    removeItemFromWishlist,
    updateWishlistItem,
    toggleWishlistPublic,
    fetchPublicWishlist,

    // Computed
    wishlistsCount,
    publicWishlists,
    privateWishlists
  }
}