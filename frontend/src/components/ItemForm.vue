<template>
  <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-900">
        {{ isEditing ? 'Edit Item' : 'Add New Item' }}
      </h2>
      
      <button
        v-if="!isEditing"
        type="button"
        @click="showApiSearchModal = true"
        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
      >
        Search APIs
      </button>
    </div>

    <form @submit.prevent="handleSubmit">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Basic Information -->
        <div class="space-y-4">
          <h3 class="text-lg font-semibold text-gray-800">Basic Information</h3>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Media Type</label>
            <select
              v-model="form.media_type"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">Select Type</option>
              <option value="book">Book</option>
              <option value="music">Music Album</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
            <input
              v-model="form.title"
              type="text"
              required
              placeholder="Item title"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
            <input
              v-model.number="form.year"
              type="number"
              min="1000"
              :max="new Date().getFullYear() + 10"
              placeholder="Release/Publication year"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Cover Image URL</label>
            <input
              v-model="form.cover_url"
              type="url"
              placeholder="https://example.com/cover.jpg"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>

          <div class="flex items-center space-x-4">
            <label class="flex items-center">
              <input
                v-model="form.owned"
                type="checkbox"
                class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
              />
              <span class="ml-2 text-sm text-gray-700">I own this item</span>
            </label>
          </div>
        </div>

        <!-- Collection Details -->
        <div class="space-y-4">
          <h3 class="text-lg font-semibold text-gray-800">Collection Details</h3>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Condition</label>
            <select
              v-model="form.condition"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">Select Condition</option>
              <option value="mint">Mint</option>
              <option value="near_mint">Near Mint</option>
              <option value="excellent">Excellent</option>
              <option value="very_good">Very Good</option>
              <option value="good">Good</option>
              <option value="fair">Fair</option>
              <option value="poor">Poor</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Price</label>
            <input
              v-model.number="form.purchase_price"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Date</label>
            <input
              v-model="form.purchase_date"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <textarea
              v-model="form.notes"
              rows="3"
              placeholder="Additional notes about this item"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Contributors -->
      <div class="mt-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-800">Contributors</h3>
          <button
            type="button"
            @click="addContributor"
            class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
          >
            Add Contributor
          </button>
        </div>
        
        <div class="space-y-3">
          <div
            v-for="(contributor, index) in form.contributors"
            :key="index"
            class="flex items-center space-x-3"
          >
            <input
              v-model="contributor.name"
              type="text"
              placeholder="Name"
              class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            />
            <select
              v-model="contributor.role"
              class="w-40 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">Role</option>
              <option value="author">Author</option>
              <option value="artist">Artist</option>
              <option value="label">Label</option>
              <option value="publisher">Publisher</option>
              <option value="producer">Producer</option>
              <option value="composer">Composer</option>
              <option value="performer">Performer</option>
            </select>
            <button
              type="button"
              @click="removeContributor(index)"
              class="px-3 py-2 text-red-600 hover:text-red-800 focus:outline-none"
            >
              Remove
            </button>
          </div>
        </div>
      </div>

      <!-- Identifiers -->
      <div class="mt-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-800">Identifiers</h3>
          <button
            type="button"
            @click="addIdentifier"
            class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
          >
            Add Identifier
          </button>
        </div>
        
        <div class="space-y-3">
          <div
            v-for="(identifier, index) in form.identifiers"
            :key="index"
            class="flex items-center space-x-3"
          >
            <select
              v-model="identifier.type"
              class="w-48 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">Type</option>
              <option value="isbn13">ISBN-13</option>
              <option value="discogs_release_id">Discogs Release ID</option>
              <option value="upc">UPC</option>
              <option value="ean">EAN</option>
              <option value="catalog_no">Catalog Number</option>
            </select>
            <input
              v-model="identifier.value"
              type="text"
              placeholder="Value"
              class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            />
            <button
              type="button"
              @click="removeIdentifier(index)"
              class="px-3 py-2 text-red-600 hover:text-red-800 focus:outline-none"
            >
              Remove
            </button>
          </div>
        </div>
      </div>

      <!-- Tags -->
      <div class="mt-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Tags (comma-separated)</label>
        <input
          v-model="tagsInput"
          type="text"
          placeholder="fiction, sci-fi, hardcover"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        />
      </div>

      <!-- Error Display -->
      <div v-if="error" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
        <p class="text-sm text-red-600">{{ error }}</p>
      </div>

      <!-- Submit Buttons -->
      <div class="mt-8 flex items-center justify-end space-x-4">
        <button
          type="button"
          @click="$emit('cancel')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
        >
          Cancel
        </button>
        <button
          type="submit"
          :disabled="loading"
          class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          {{ loading ? 'Saving...' : (isEditing ? 'Update Item' : 'Create Item') }}
        </button>
      </div>
    </form>

    <!-- API Search Modal -->
    <ApiSearchModal
      :is-open="showApiSearchModal"
      @close="showApiSearchModal = false"
      @item-added="handleApiItemAdded"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useItemsStore } from '../stores/items';
import ApiSearchModal from './ApiSearchModal.vue';

const props = defineProps({
  item: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['saved', 'cancel']);

const itemsStore = useItemsStore();

const isEditing = computed(() => !!props.item);
const loading = ref(false);
const error = ref('');
const tagsInput = ref('');
const showApiSearchModal = ref(false);

const form = ref({
  media_type: '',
  title: '',
  year: null,
  cover_url: '',
  owned: true,
  condition: '',
  purchase_price: null,
  purchase_date: '',
  notes: '',
  contributors: [],
  identifiers: [],
  tags: [],
});

const addContributor = () => {
  form.value.contributors.push({ name: '', role: '' });
};

const removeContributor = (index) => {
  form.value.contributors.splice(index, 1);
};

const addIdentifier = () => {
  form.value.identifiers.push({ type: '', value: '' });
};

const removeIdentifier = (index) => {
  form.value.identifiers.splice(index, 1);
};

const handleSubmit = async () => {
  loading.value = true;
  error.value = '';

  try {
    // Parse tags from input
    form.value.tags = tagsInput.value
      .split(',')
      .map(tag => tag.trim())
      .filter(tag => tag.length > 0);

    // Filter out empty contributors and identifiers
    form.value.contributors = form.value.contributors.filter(
      c => c.name.trim() && c.role.trim()
    );
    form.value.identifiers = form.value.identifiers.filter(
      i => i.type.trim() && i.value.trim()
    );

    if (isEditing.value) {
      await itemsStore.updateItem(props.item.id, form.value);
    } else {
      await itemsStore.createItem(form.value);
    }

    emit('saved');
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save item';
  } finally {
    loading.value = false;
  }
};

// Initialize form when editing
watch(() => props.item, (newItem) => {
  if (newItem) {
    form.value = {
      media_type: newItem.media_type || '',
      title: newItem.title || '',
      year: newItem.year || null,
      cover_url: newItem.cover_url || '',
      owned: newItem.owned !== undefined ? newItem.owned : true,
      condition: newItem.condition || '',
      purchase_price: newItem.purchase_price || null,
      purchase_date: newItem.purchase_date || '',
      notes: newItem.notes || '',
      contributors: newItem.contributors?.map(c => ({
        name: c.name,
        role: c.pivot.role,
      })) || [],
      identifiers: newItem.identifiers?.map(i => ({
        type: i.type,
        value: i.value,
      })) || [],
      tags: newItem.tags?.map(t => t.name) || [],
    };

    tagsInput.value = form.value.tags.join(', ');
  }
}, { immediate: true });

onMounted(() => {
  if (!isEditing.value) {
    // Initialize empty form for new items
    form.value = {
      media_type: '',
      title: '',
      year: null,
      cover_url: '',
      owned: true,
      condition: '',
      purchase_price: null,
      purchase_date: '',
      notes: '',
      contributors: [],
      identifiers: [],
      tags: [],
    };
  }
});

// Handle item added via API search
const handleApiItemAdded = (item) => {
  emit('saved', item);
};
</script>