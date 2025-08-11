<template>
  <div class="bg-white shadow rounded-lg">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900">{{ title }}</h2>
        <button
          @click="$emit('add-item')"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Add {{ mediaType === 'book' ? 'Book' : 'Album' }}
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <input
            v-model="search"
            type="text"
            placeholder="Search items..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            @input="debouncedSearch"
          />
        </div>
        <div>
          <select
            v-model="ownedFilter"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            @change="applyFilters"
          >
            <option value="">All Items</option>
            <option value="true">Owned Items</option>
            <option value="false">Wishlist Items</option>
          </select>
        </div>
        <div>
          <select
            v-model="conditionFilter"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            @change="applyFilters"
          >
            <option value="">Any Condition</option>
            <option value="mint">Mint</option>
            <option value="near_mint">Near Mint</option>
            <option value="excellent">Excellent</option>
            <option value="very_good">Very Good</option>
            <option value="good">Good</option>
            <option value="fair">Fair</option>
            <option value="poor">Poor</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-8 text-center">
      <div class="text-gray-500">Loading items...</div>
    </div>

    <!-- Empty State -->
    <div v-else-if="items.length === 0" class="p-8 text-center">
      <div class="text-gray-500 mb-4">
        {{ search ? 'No items found matching your search.' : `No ${mediaType}s in your collection yet.` }}
      </div>
      <button
        @click="$emit('add-item')"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-600 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        Add your first {{ mediaType === 'book' ? 'book' : 'album' }}
      </button>
    </div>

    <!-- Items Grid -->
    <div v-else class="p-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div
          v-for="item in items"
          :key="item.id"
          class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200"
        >
          <!-- Cover Image -->
          <div class="aspect-w-3 aspect-h-4 bg-gray-100 rounded-t-lg overflow-hidden">
            <img
              v-if="item.cover_url"
              :src="item.cover_url"
              :alt="item.title"
              class="w-full h-48 object-cover"
              @error="$event.target.style.display = 'none'"
            />
            <div
              v-else
              class="w-full h-48 flex items-center justify-center bg-gray-200 text-gray-400"
            >
              <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
              </svg>
            </div>
          </div>

          <!-- Item Info -->
          <div class="p-4">
            <h3 class="font-medium text-gray-900 truncate" :title="item.title">
              {{ item.title }}
            </h3>
            
            <div v-if="item.contributors && item.contributors.length > 0" class="mt-1">
              <p class="text-sm text-gray-600 truncate">
                by {{ item.contributors.map(c => c.name).join(', ') }}
              </p>
            </div>

            <div class="mt-2 flex items-center justify-between">
              <span v-if="item.year" class="text-sm text-gray-500">{{ item.year }}</span>
              <span
                v-if="item.condition"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="getConditionClass(item.condition)"
              >
                {{ formatCondition(item.condition) }}
              </span>
            </div>

            <div class="mt-2 flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <span
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                  :class="item.owned ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                >
                  {{ item.owned ? 'Owned' : 'Wishlist' }}
                </span>
              </div>
              
              <div class="flex items-center space-x-2">
                <button
                  @click="$emit('edit-item', item)"
                  class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                >
                  Edit
                </button>
                <button
                  @click="$emit('delete-item', item)"
                  class="text-red-600 hover:text-red-800 text-sm font-medium"
                >
                  Delete
                </button>
              </div>
            </div>

            <div v-if="item.purchase_price" class="mt-2">
              <p class="text-sm text-gray-600">${{ item.purchase_price }}</p>
            </div>

            <div v-if="item.tags && item.tags.length > 0" class="mt-2">
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="tag in item.tags.slice(0, 3)"
                  :key="tag.id"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                >
                  {{ tag.name }}
                </span>
                <span
                  v-if="item.tags.length > 3"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                >
                  +{{ item.tags.length - 3 }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="mt-8 flex items-center justify-center">
        <nav class="flex items-center space-x-2">
          <button
            :disabled="pagination.current_page === 1"
            @click="loadPage(pagination.current_page - 1)"
            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          
          <span class="px-4 py-2 text-sm font-medium text-gray-700">
            Page {{ pagination.current_page }} of {{ pagination.last_page }}
          </span>
          
          <button
            :disabled="pagination.current_page === pagination.last_page"
            @click="loadPage(pagination.current_page + 1)"
            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { debounce } from 'lodash-es';

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  pagination: {
    type: Object,
    default: () => ({
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0,
    }),
  },
  mediaType: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['add-item', 'edit-item', 'delete-item', 'filters-changed', 'page-changed']);

const title = computed(() => {
  if (props.mediaType === 'book') return 'Books';
  if (props.mediaType === 'music') return 'Music Albums';
  return 'All Items';
});

const search = ref('');
const ownedFilter = ref('');
const conditionFilter = ref('');

const debouncedSearch = debounce(() => {
  applyFilters();
}, 300);

const applyFilters = () => {
  const filters = {
    search: search.value,
    owned: ownedFilter.value,
    condition: conditionFilter.value,
    media_type: props.mediaType,
  };

  // Remove empty filters
  Object.keys(filters).forEach(key => {
    if (!filters[key]) {
      delete filters[key];
    }
  });

  emit('filters-changed', filters);
};

const loadPage = (page) => {
  emit('page-changed', page);
};

const getConditionClass = (condition) => {
  const classes = {
    mint: 'bg-green-100 text-green-800',
    near_mint: 'bg-green-100 text-green-700',
    excellent: 'bg-blue-100 text-blue-800',
    very_good: 'bg-blue-100 text-blue-700',
    good: 'bg-yellow-100 text-yellow-800',
    fair: 'bg-orange-100 text-orange-800',
    poor: 'bg-red-100 text-red-800',
  };
  return classes[condition] || 'bg-gray-100 text-gray-800';
};

const formatCondition = (condition) => {
  return condition.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};
</script>