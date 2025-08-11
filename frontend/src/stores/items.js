import { defineStore } from 'pinia';
import itemsService from '../services/items';

export const useItemsStore = defineStore('items', {
  state: () => ({
    items: [],
    currentItem: null,
    loading: false,
    error: null,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0,
    },
  }),

  getters: {
    books: (state) => state.items.filter(item => item.media_type === 'book'),
    musicAlbums: (state) => state.items.filter(item => item.media_type === 'music'),
    ownedItems: (state) => state.items.filter(item => item.owned),
  },

  actions: {
    async fetchItems(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await itemsService.getItems(params);
        this.items = response.data;
        this.pagination = {
          current_page: response.current_page,
          last_page: response.last_page,
          per_page: response.per_page,
          total: response.total,
        };
        return response;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch items';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchItem(id) {
      this.loading = true;
      this.error = null;
      try {
        const item = await itemsService.getItem(id);
        this.currentItem = item;
        return item;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch item';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createItem(itemData) {
      this.loading = true;
      this.error = null;
      try {
        const item = await itemsService.createItem(itemData);
        this.items.unshift(item);
        return item;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create item';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateItem(id, itemData) {
      this.loading = true;
      this.error = null;
      try {
        const item = await itemsService.updateItem(id, itemData);
        const index = this.items.findIndex(i => i.id === id);
        if (index !== -1) {
          this.items[index] = item;
        }
        if (this.currentItem?.id === id) {
          this.currentItem = item;
        }
        return item;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update item';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteItem(id) {
      this.loading = true;
      this.error = null;
      try {
        await itemsService.deleteItem(id);
        this.items = this.items.filter(item => item.id !== id);
        if (this.currentItem?.id === id) {
          this.currentItem = null;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete item';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    clearCurrentItem() {
      this.currentItem = null;
    },

    clearError() {
      this.error = null;
    },
  },
});