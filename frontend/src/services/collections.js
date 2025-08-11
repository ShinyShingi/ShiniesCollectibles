import api from './api'

class CollectionService {
  /**
   * Get all user collections
   */
  async getCollections() {
    try {
      const response = await api.get('/collections')
      return response.data
    } catch (error) {
      throw new Error('Failed to fetch collections')
    }
  }

  /**
   * Get a single collection with items
   */
  async getCollection(id) {
    try {
      const response = await api.get(`/collections/${id}`)
      return response.data
    } catch (error) {
      throw new Error('Failed to fetch collection')
    }
  }

  /**
   * Create a new collection
   */
  async createCollection(collection) {
    try {
      const response = await api.post('/collections', collection)
      return response.data
    } catch (error) {
      if (error.response?.status === 422) {
        throw new Error(error.response.data.message || 'Validation error')
      }
      throw new Error('Failed to create collection')
    }
  }

  /**
   * Update a collection
   */
  async updateCollection(id, collection) {
    try {
      const response = await api.put(`/collections/${id}`, collection)
      return response.data
    } catch (error) {
      if (error.response?.status === 422) {
        throw new Error(error.response.data.message || 'Validation error')
      }
      throw new Error('Failed to update collection')
    }
  }

  /**
   * Delete a collection
   */
  async deleteCollection(id) {
    try {
      const response = await api.delete(`/collections/${id}`)
      return response.data
    } catch (error) {
      throw new Error('Failed to delete collection')
    }
  }

  /**
   * Add items to a collection
   */
  async addItemsToCollection(collectionId, itemIds) {
    try {
      const response = await api.post(`/collections/${collectionId}/items`, {
        item_ids: itemIds
      })
      return response.data
    } catch (error) {
      throw new Error('Failed to add items to collection')
    }
  }

  /**
   * Remove items from a collection
   */
  async removeItemsFromCollection(collectionId, itemIds) {
    try {
      const response = await api.delete(`/collections/${collectionId}/items`, {
        data: { item_ids: itemIds }
      })
      return response.data
    } catch (error) {
      throw new Error('Failed to remove items from collection')
    }
  }
}

export default new CollectionService()