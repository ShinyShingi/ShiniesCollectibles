import { defineStore } from 'pinia'
import collectionsService from '@/services/collections'

export const useCollectionsStore = defineStore('collections', {
  state: () => ({
    collections: [],
    currentCollection: null,
    loading: false,
    error: null
  }),

  getters: {
    getCollectionById: (state) => (id) => {
      return state.collections.find(collection => collection.id === id)
    },

    totalItems: (state) => {
      return state.collections.reduce((total, collection) => {
        return total + (collection.items_count || 0)
      }, 0)
    },

    sortedCollections: (state) => {
      return [...state.collections].sort((a, b) => a.name.localeCompare(b.name))
    }
  },

  actions: {
    async fetchCollections() {
      this.loading = true
      this.error = null
      
      try {
        this.collections = await collectionsService.getCollections()
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchCollection(id) {
      this.loading = true
      this.error = null
      
      try {
        this.currentCollection = await collectionsService.getCollection(id)
        
        // Update the collection in the collections array if it exists
        const index = this.collections.findIndex(c => c.id === id)
        if (index !== -1) {
          this.collections[index] = {
            ...this.currentCollection,
            items_count: this.currentCollection.items?.length || 0
          }
        }
        
        return this.currentCollection
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async createCollection(collectionData) {
      this.loading = true
      this.error = null
      
      try {
        const newCollection = await collectionsService.createCollection(collectionData)
        this.collections.push(newCollection)
        return newCollection
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateCollection(id, collectionData) {
      this.loading = true
      this.error = null
      
      try {
        const updatedCollection = await collectionsService.updateCollection(id, collectionData)
        
        const index = this.collections.findIndex(c => c.id === id)
        if (index !== -1) {
          this.collections[index] = updatedCollection
        }
        
        if (this.currentCollection?.id === id) {
          this.currentCollection = { ...this.currentCollection, ...updatedCollection }
        }
        
        return updatedCollection
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteCollection(id) {
      this.loading = true
      this.error = null
      
      try {
        await collectionsService.deleteCollection(id)
        
        this.collections = this.collections.filter(c => c.id !== id)
        
        if (this.currentCollection?.id === id) {
          this.currentCollection = null
        }
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async addItemsToCollection(collectionId, itemIds) {
      this.error = null
      
      try {
        const result = await collectionsService.addItemsToCollection(collectionId, itemIds)
        
        // Update the collection's item count
        const collection = this.collections.find(c => c.id === collectionId)
        if (collection) {
          collection.items_count = (collection.items_count || 0) + (result.added_count || 0)
        }
        
        return result
      } catch (error) {
        this.error = error.message
        throw error
      }
    },

    async removeItemsFromCollection(collectionId, itemIds) {
      this.error = null
      
      try {
        const result = await collectionsService.removeItemsFromCollection(collectionId, itemIds)
        
        // Update the collection's item count
        const collection = this.collections.find(c => c.id === collectionId)
        if (collection) {
          collection.items_count = Math.max(0, (collection.items_count || 0) - itemIds.length)
        }
        
        return result
      } catch (error) {
        this.error = error.message
        throw error
      }
    },

    clearError() {
      this.error = null
    },

    clearCurrentCollection() {
      this.currentCollection = null
    }
  }
})