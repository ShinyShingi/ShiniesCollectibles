<template>
  <Dialog
    :visible="visible"
    @hide="$emit('close')"
    :header="`Add '${item?.title}' to Collections`"
    modal
    :style="{ width: '500px' }"
    class="add-to-collection-modal"
  >
    <div v-if="item" class="space-y-6">
      <!-- Item Preview -->
      <div class="flex gap-4 p-4 bg-gray-50 rounded-lg">
        <img
          :src="item.cover_url || getFallbackImage(item.media_type)"
          :alt="item.title"
          class="w-16 h-16 object-cover rounded-md flex-shrink-0"
          @error="handleImageError"
        />
        <div class="flex-grow min-w-0">
          <h3 class="font-medium text-gray-900 truncate">{{ item.title }}</h3>
          <p v-if="item.contributors?.length" class="text-sm text-gray-500">
            {{ item.contributors[0].name }}{{ item.contributors.length > 1 ? ` +${item.contributors.length - 1}` : '' }}
          </p>
          <div class="flex items-center gap-2 mt-1">
            <PrimeTag 
              :value="item.media_type" 
              :severity="item.media_type === 'book' ? 'info' : 'success'" 
              size="small"
            />
            <span v-if="item.year" class="text-sm text-gray-400">{{ item.year }}</span>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-8">
        <i class="pi pi-spinner pi-spin text-2xl text-gray-400"></i>
        <p class="text-gray-500 mt-2">Loading collections...</p>
      </div>

      <!-- No Collections State -->
      <div v-else-if="collections.length === 0" class="text-center py-8">
        <i class="pi pi-folder-open text-4xl text-gray-300 mb-4 block"></i>
        <p class="text-gray-500 mb-2">No collections found</p>
        <p class="text-sm text-gray-400 mb-4">Create your first collection to organize your items</p>
        <Button
          @click="showCreateCollection = true"
          icon="pi pi-plus"
          label="Create Collection"
          size="small"
        />
      </div>

      <!-- Collections List -->
      <div v-else class="space-y-4">
        <div class="flex justify-between items-center">
          <h4 class="font-medium text-gray-900">Select Collections</h4>
          <Button
            @click="showCreateCollection = true"
            icon="pi pi-plus"
            label="New Collection"
            size="small"
            text
          />
        </div>

        <div class="space-y-2 max-h-64 overflow-y-auto">
          <div
            v-for="collection in collections"
            :key="collection.id"
            class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <Checkbox
                :model-value="selectedCollections.includes(collection.id)"
                @change="toggleCollection(collection.id, $event)"
                :input-id="`collection-${collection.id}`"
                binary
              />
              
              <div
                :style="{ backgroundColor: collection.color }"
                class="w-3 h-3 rounded-full flex-shrink-0"
              ></div>
              
              <div class="flex-grow min-w-0">
                <label
                  :for="`collection-${collection.id}`"
                  class="font-medium text-gray-900 cursor-pointer"
                >
                  {{ collection.name }}
                </label>
                <p v-if="collection.description" class="text-sm text-gray-500 line-clamp-1">
                  {{ collection.description }}
                </p>
              </div>
            </div>
            
            <div class="text-sm text-gray-400">
              {{ collection.items_count || 0 }} items
            </div>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-md">
        <div class="flex">
          <i class="pi pi-exclamation-triangle text-red-400 mr-2"></i>
          <p class="text-sm text-red-800">{{ error }}</p>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-between">
        <Button
          @click="$emit('close')"
          label="Cancel"
          severity="secondary"
          text
        />
        <Button
          @click="addToCollections"
          :disabled="selectedCollections.length === 0 || adding"
          :loading="adding"
          icon="pi pi-plus"
          :label="`Add to ${selectedCollections.length} Collection${selectedCollections.length !== 1 ? 's' : ''}`"
        />
      </div>
    </template>

    <!-- Create Collection Dialog -->
    <Dialog
      v-model:visible="showCreateCollection"
      header="Create New Collection"
      modal
      :style="{ width: '400px' }"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Collection Name *
          </label>
          <InputText
            v-model="newCollectionName"
            placeholder="Enter collection name"
            class="w-full"
            :class="{ 'p-invalid': createError }"
            @keyup.enter="createCollection"
          />
          <small v-if="createError" class="p-error">{{ createError }}</small>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Color
          </label>
          <div class="flex gap-2">
            <input
              v-model="newCollectionColor"
              type="color"
              class="w-12 h-10 rounded border border-gray-300 cursor-pointer"
            />
            <InputText
              v-model="newCollectionColor"
              placeholder="#3B82F6"
              class="flex-1"
            />
          </div>
        </div>
      </div>
      
      <template #footer>
        <div class="flex gap-2">
          <Button
            @click="cancelCreateCollection"
            label="Cancel"
            severity="secondary"
            text
          />
          <Button
            @click="createCollection"
            :loading="creating"
            :disabled="!newCollectionName"
            icon="pi pi-plus"
            label="Create & Add Item"
          />
        </div>
      </template>
    </Dialog>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useCollectionsStore } from '@/stores/collections'
import externalApiService from '@/services/externalApi'

const emit = defineEmits(['close', 'added'])

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  item: {
    type: Object,
    default: null
  }
})

const collectionsStore = useCollectionsStore()

// Reactive state
const loading = ref(false)
const adding = ref(false)
const creating = ref(false)
const error = ref('')
const createError = ref('')
const selectedCollections = ref([])
const showCreateCollection = ref(false)
const newCollectionName = ref('')
const newCollectionColor = ref('#3B82F6')

// Computed properties
const collections = computed(() => {
  return collectionsStore.sortedCollections
})

// Watchers
watch(() => props.visible, (visible) => {
  if (visible) {
    loadCollections()
    resetState()
  }
})

// Methods
const resetState = () => {
  selectedCollections.value = []
  error.value = ''
  createError.value = ''
  showCreateCollection.value = false
  newCollectionName.value = ''
  newCollectionColor.value = '#3B82F6'
}

const loadCollections = async () => {
  loading.value = true
  error.value = ''
  
  try {
    await collectionsStore.fetchCollections()
  } catch (err) {
    error.value = 'Failed to load collections'
  } finally {
    loading.value = false
  }
}

const toggleCollection = (collectionId, event) => {
  if (event.checked) {
    selectedCollections.value.push(collectionId)
  } else {
    selectedCollections.value = selectedCollections.value.filter(id => id !== collectionId)
  }
}

const addToCollections = async () => {
  if (!props.item || selectedCollections.value.length === 0) return
  
  adding.value = true
  error.value = ''
  
  try {
    const promises = selectedCollections.value.map(collectionId =>
      collectionsStore.addItemsToCollection(collectionId, [props.item.id])
    )
    
    await Promise.all(promises)
    emit('added')
  } catch (err) {
    error.value = 'Failed to add item to collections'
  } finally {
    adding.value = false
  }
}

const createCollection = async () => {
  if (!newCollectionName.value.trim()) {
    createError.value = 'Collection name is required'
    return
  }
  
  creating.value = true
  createError.value = ''
  
  try {
    const collectionData = {
      name: newCollectionName.value.trim(),
      color: newCollectionColor.value,
      is_public: false
    }
    
    const newCollection = await collectionsStore.createCollection(collectionData)
    
    // Add the item to the new collection
    if (props.item) {
      await collectionsStore.addItemsToCollection(newCollection.id, [props.item.id])
    }
    
    showCreateCollection.value = false
    emit('added')
  } catch (err) {
    if (err.message.includes('unique')) {
      createError.value = 'A collection with this name already exists'
    } else {
      createError.value = err.message
    }
  } finally {
    creating.value = false
  }
}

const cancelCreateCollection = () => {
  showCreateCollection.value = false
  newCollectionName.value = ''
  newCollectionColor.value = '#3B82F6'
  createError.value = ''
}

const getFallbackImage = (mediaType) => {
  return externalApiService.getFallbackCoverUrl(mediaType)
}

const handleImageError = (event) => {
  const mediaType = event.target.alt?.toLowerCase().includes('music') ? 'music' : 'book'
  event.target.src = getFallbackImage(mediaType)
}
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.add-to-collection-modal :deep(.p-dialog-content) {
  padding: 1.5rem;
}
</style>