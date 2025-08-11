<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click="closeModal"
  >
    <div
      class="bg-white rounded-lg max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto"
      @click.stop
    >
      <div class="p-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold">Search External APIs</h2>
          <button
            @click="closeModal"
            class="text-gray-500 hover:text-gray-700 text-xl"
          >
            ×
          </button>
        </div>

        <!-- Search Type Selection -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Search Type
          </label>
          <select
            v-model="searchType"
            class="w-full border border-gray-300 rounded-md px-3 py-2"
            @change="resetSearch"
          >
            <option value="book-isbn">Book by ISBN</option>
            <option value="music-barcode">Music by Barcode</option>
            <option value="music-catalog">Music by Catalog Number</option>
          </select>
        </div>

        <!-- Search Form -->
        <form @submit.prevent="performSearch" class="mb-6">
          <div v-if="searchType === 'book-isbn'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                ISBN (10 or 13 digits)
              </label>
              <input
                v-model="searchQuery.isbn"
                type="text"
                placeholder="Enter ISBN (e.g., 9781234567890)"
                class="w-full border border-gray-300 rounded-md px-3 py-2"
                required
              />
            </div>
          </div>

          <div v-else-if="searchType === 'music-barcode'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Barcode (8-14 digits)
              </label>
              <input
                v-model="searchQuery.barcode"
                type="text"
                placeholder="Enter barcode (e.g., 123456789012)"
                class="w-full border border-gray-300 rounded-md px-3 py-2"
                required
              />
            </div>
          </div>

          <div v-else-if="searchType === 'music-catalog'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Catalog Number
              </label>
              <input
                v-model="searchQuery.catalogNumber"
                type="text"
                placeholder="Enter catalog number"
                class="w-full border border-gray-300 rounded-md px-3 py-2"
                required
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Label (Optional)
              </label>
              <input
                v-model="searchQuery.label"
                type="text"
                placeholder="Enter record label"
                class="w-full border border-gray-300 rounded-md px-3 py-2"
              />
            </div>
          </div>

          <button
            type="submit"
            :disabled="isSearching"
            class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            {{ isSearching ? 'Searching...' : 'Search' }}
          </button>
        </form>

        <!-- Error Message -->
        <div v-if="error" class="mb-4 p-3 bg-red-100 border border-red-300 rounded-md text-red-700">
          {{ error }}
        </div>

        <!-- Search Results -->
        <div v-if="searchResult" class="border-t pt-6">
          <h3 class="text-lg font-semibold mb-4">Search Result</h3>
          
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex gap-4">
              <!-- Cover Image -->
              <div class="flex-shrink-0">
                <img
                  :src="searchResult.data.cover_url || getFallbackCover()"
                  :alt="searchResult.data.title"
                  class="w-24 h-24 object-cover rounded-md bg-gray-200"
                  @error="handleImageError"
                />
              </div>

              <!-- Item Details -->
              <div class="flex-grow">
                <h4 class="font-semibold text-lg">{{ searchResult.data.title }}</h4>
                <p v-if="searchResult.data.year" class="text-gray-600 mb-2">{{ searchResult.data.year }}</p>
                
                <!-- Contributors -->
                <div v-if="searchResult.data.contributors?.length" class="mb-2">
                  <span class="text-sm font-medium text-gray-700">Contributors:</span>
                  <div class="flex flex-wrap gap-1 mt-1">
                    <span
                      v-for="contributor in searchResult.data.contributors.slice(0, 3)"
                      :key="contributor.name"
                      class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded"
                    >
                      {{ contributor.name }} ({{ formatRole(contributor.role) }})
                    </span>
                    <span
                      v-if="searchResult.data.contributors.length > 3"
                      class="text-xs text-gray-500"
                    >
                      +{{ searchResult.data.contributors.length - 3 }} more
                    </span>
                  </div>
                </div>

                <!-- Identifiers -->
                <div v-if="searchResult.data.identifiers?.length" class="mb-2">
                  <span class="text-sm font-medium text-gray-700">Identifiers:</span>
                  <div class="text-xs text-gray-600 mt-1">
                    <span
                      v-for="identifier in searchResult.data.identifiers"
                      :key="identifier.type + identifier.value"
                      class="mr-3"
                    >
                      {{ identifier.type.toUpperCase() }}: {{ identifier.value }}
                    </span>
                  </div>
                </div>

                <!-- Source -->
                <p class="text-xs text-gray-500 mt-2">
                  Source: {{ searchResult.source === 'openlibrary' ? 'Open Library' : 'Discogs' }}
                </p>
              </div>
            </div>

            <!-- Add Item Form -->
            <div class="mt-4 pt-4 border-t">
              <h5 class="font-medium mb-3">Add to Collection</h5>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Condition
                  </label>
                  <select
                    v-model="itemData.condition"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                  >
                    <option value="">Not specified</option>
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
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Purchase Price
                  </label>
                  <input
                    v-model.number="itemData.purchase_price"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Purchase Date
                  </label>
                  <input
                    v-model="itemData.purchase_date"
                    type="date"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                  />
                </div>

                <div>
                  <label class="flex items-center">
                    <input
                      v-model="itemData.owned"
                      type="checkbox"
                      class="mr-2"
                    />
                    <span class="text-sm font-medium text-gray-700">I own this item</span>
                  </label>
                </div>
              </div>

              <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Notes
                </label>
                <textarea
                  v-model="itemData.notes"
                  rows="2"
                  class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                  placeholder="Optional notes about this item"
                ></textarea>
              </div>

              <div class="flex gap-3 mt-4">
                <button
                  @click="addToCollection"
                  :disabled="isAdding"
                  class="flex-1 bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 disabled:opacity-50"
                >
                  {{ isAdding ? 'Adding...' : 'Add to Collection' }}
                </button>
                <button
                  @click="resetSearch"
                  class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                >
                  New Search
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import externalApiService from '@/services/externalApi'
import { useItemsStore } from '@/stores/items'

const emit = defineEmits(['close', 'item-added'])

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  }
})

const itemsStore = useItemsStore()

// Reactive state
const searchType = ref('book-isbn')
const isSearching = ref(false)
const isAdding = ref(false)
const error = ref('')
const searchResult = ref(null)

const searchQuery = ref({
  isbn: '',
  barcode: '',
  catalogNumber: '',
  label: ''
})

const itemData = ref({
  condition: '',
  purchase_price: null,
  purchase_date: '',
  owned: true,
  notes: ''
})

// Computed properties
const mediaType = computed(() => {
  return searchType.value.startsWith('book') ? 'book' : 'music'
})

// Watchers
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    resetSearch()
  }
})

// Methods
const closeModal = () => {
  emit('close')
}

const resetSearch = () => {
  error.value = ''
  searchResult.value = null
  isSearching.value = false
  isAdding.value = false
  
  searchQuery.value = {
    isbn: '',
    barcode: '',
    catalogNumber: '',
    label: ''
  }
  
  itemData.value = {
    condition: '',
    purchase_price: null,
    purchase_date: '',
    owned: true,
    notes: ''
  }
}

const performSearch = async () => {
  error.value = ''
  isSearching.value = true
  
  try {
    let result = null
    
    if (searchType.value === 'book-isbn') {
      const cleanIsbn = externalApiService.validateIsbn(searchQuery.value.isbn)
      result = await externalApiService.searchBookByIsbn(cleanIsbn)
    } else if (searchType.value === 'music-barcode') {
      const cleanBarcode = externalApiService.validateBarcode(searchQuery.value.barcode)
      result = await externalApiService.searchMusicByBarcode(cleanBarcode)
    } else if (searchType.value === 'music-catalog') {
      result = await externalApiService.searchMusicByCatalog(
        searchQuery.value.catalogNumber,
        searchQuery.value.label
      )
    }
    
    searchResult.value = result
  } catch (err) {
    error.value = err.message
  } finally {
    isSearching.value = false
  }
}

const addToCollection = async () => {
  isAdding.value = true
  error.value = ''
  
  try {
    const newItem = await externalApiService.createFromApiData(
      searchResult.value,
      mediaType.value,
      itemData.value
    )
    
    await itemsStore.fetchItems()
    emit('item-added', newItem)
    closeModal()
  } catch (err) {
    error.value = err.message
  } finally {
    isAdding.value = false
  }
}

const getFallbackCover = () => {
  return externalApiService.getFallbackCoverUrl(mediaType.value)
}

const formatRole = (role) => {
  return externalApiService.formatRole(role)
}

const handleImageError = (event) => {
  event.target.src = getFallbackCover()
}
</script>