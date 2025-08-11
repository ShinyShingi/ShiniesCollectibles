<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <h1 class="text-xl font-bold text-gray-900">Shinies Collectibles</h1>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <router-link
                to="/dashboard"
                class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
              >
                Dashboard
              </router-link>
              <router-link
                to="/books"
                class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
              >
                Books
              </router-link>
              <router-link
                to="/music"
                class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
              >
                Music
              </router-link>
            </div>
          </div>
          <div class="flex items-center">
            <span class="text-gray-700 mr-4">{{ user?.name }}</span>
            <button
              @click="handleLogout"
              class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- Add/Edit Form Modal -->
        <div
          v-if="showForm"
          class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
          @click="handleModalClick"
        >
          <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
            <ItemForm
              :item="editingItem"
              @saved="handleItemSaved"
              @cancel="handleFormCancel"
            />
          </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
          v-if="showDeleteModal"
          class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        >
          <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
              <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Album</h3>
              <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                  Are you sure you want to delete "{{ itemToDelete?.title }}"? This action cannot be undone.
                </p>
              </div>
              <div class="items-center px-4 py-3">
                <button
                  @click="confirmDelete"
                  :disabled="deleting"
                  class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300 mr-2 disabled:opacity-50"
                >
                  {{ deleting ? 'Deleting...' : 'Delete' }}
                </button>
                <button
                  @click="cancelDelete"
                  :disabled="deleting"
                  class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:opacity-50"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
          <div class="flex">
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">{{ error }}</h3>
            </div>
            <div class="ml-auto">
              <button
                @click="error = ''"
                class="text-red-400 hover:text-red-600 focus:outline-none"
              >
                <span class="sr-only">Dismiss</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Music Albums List -->
        <ItemList
          :items="musicAlbums"
          :loading="loading"
          :pagination="pagination"
          media-type="music"
          @add-item="handleAddItem"
          @edit-item="handleEditItem"
          @delete-item="handleDeleteItem"
          @filters-changed="handleFiltersChanged"
          @page-changed="handlePageChanged"
        />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useItemsStore } from '../stores/items';
import ItemList from '../components/ItemList.vue';
import ItemForm from '../components/ItemForm.vue';

const router = useRouter();
const authStore = useAuthStore();
const itemsStore = useItemsStore();

const user = computed(() => authStore.user);
const musicAlbums = computed(() => itemsStore.musicAlbums);
const loading = computed(() => itemsStore.loading);
const pagination = computed(() => itemsStore.pagination);

const showForm = ref(false);
const showDeleteModal = ref(false);
const editingItem = ref(null);
const itemToDelete = ref(null);
const deleting = ref(false);
const error = ref('');

const currentFilters = ref({ media_type: 'music' });
const currentPage = ref(1);

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};

const handleAddItem = () => {
  editingItem.value = null;
  showForm.value = true;
};

const handleEditItem = (item) => {
  editingItem.value = item;
  showForm.value = true;
};

const handleDeleteItem = (item) => {
  itemToDelete.value = item;
  showDeleteModal.value = true;
};

const handleItemSaved = () => {
  showForm.value = false;
  editingItem.value = null;
  loadItems();
};

const handleFormCancel = () => {
  showForm.value = false;
  editingItem.value = null;
};

const handleModalClick = (event) => {
  if (event.target === event.currentTarget) {
    handleFormCancel();
  }
};

const confirmDelete = async () => {
  if (!itemToDelete.value) return;
  
  deleting.value = true;
  try {
    await itemsStore.deleteItem(itemToDelete.value.id);
    showDeleteModal.value = false;
    itemToDelete.value = null;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete item';
  } finally {
    deleting.value = false;
  }
};

const cancelDelete = () => {
  showDeleteModal.value = false;
  itemToDelete.value = null;
};

const handleFiltersChanged = (filters) => {
  currentFilters.value = { ...filters, media_type: 'music' };
  currentPage.value = 1;
  loadItems();
};

const handlePageChanged = (page) => {
  currentPage.value = page;
  loadItems();
};

const loadItems = async () => {
  try {
    error.value = '';
    await itemsStore.fetchItems({
      ...currentFilters.value,
      page: currentPage.value,
    });
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load music albums';
  }
};

onMounted(() => {
  loadItems();
});
</script>