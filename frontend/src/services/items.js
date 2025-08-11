import api from './api';

export default {
  async getItems(params = {}) {
    const response = await api.get('/items', { params });
    return response.data;
  },

  async getItem(id) {
    const response = await api.get(`/items/${id}`);
    return response.data;
  },

  async createItem(item) {
    const response = await api.post('/items', item);
    return response.data;
  },

  async updateItem(id, item) {
    const response = await api.put(`/items/${id}`, item);
    return response.data;
  },

  async deleteItem(id) {
    const response = await api.delete(`/items/${id}`);
    return response.data;
  },
};