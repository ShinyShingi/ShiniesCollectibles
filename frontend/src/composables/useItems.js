import { ref } from 'vue'
import api from '@/services/api'

export function useItems() {
  const items = ref([])
  const loading = ref(false)
  const error = ref(null)

  // Search items in user's library
  const searchItems = async (query) => {
    if (!query || query.length < 2) {
      return { data: [] }
    }

    loading.value = true
    error.value = null
    try {
      // Search through user's items
      const response = await api.get('/items', {
        params: {
          search: query,
          limit: 20
        }
      })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to search items'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Get all user items
  const fetchItems = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/items', { params })
      items.value = response.data.data || response.data
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch items'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Get specific item
  const fetchItem = async (id) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/items/${id}`)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch item'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    items,
    loading,
    error,

    // Actions
    searchItems,
    fetchItems,
    fetchItem
  }
}