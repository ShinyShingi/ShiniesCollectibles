<template>
  <Dialog
    :visible="visible"
    @hide="$emit('close')"
    header="Collection Management"
    modal
    :style="{ width: '90vw', maxWidth: '800px' }"
    class="collection-manager"
  >
    <div class="space-y-6">
      <!-- Create New Collection -->
      <div class="border-b pb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Collection</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Collection Name *
            </label>
            <InputText
              v-model="newCollection.name"
              placeholder="Enter collection name"
              class="w-full"
              :class="{ 'p-invalid': errors.name }"
            />
            <small v-if="errors.name" class="p-error">{{ errors.name }}</small>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Color
            </label>
            <div class="flex gap-2">
              <input
                v-model="newCollection.color"
                type="color"
                class="w-12 h-10 rounded border border-gray-300 cursor-pointer"
              />
              <InputText
                v-model="newCollection.color"
                placeholder="#3B82F6"
                class="flex-1"
                pattern="^#[a-fA-F0-9]{6}$"
              />
            </div>
          </div>
        </div>
        
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Description
          </label>
          <Textarea
            v-model="newCollection.description"
            placeholder="Optional description for this collection"
            rows="2"
            class="w-full"
          />
        </div>
        
        <div class="mt-4 flex items-center">
          <Checkbox
            v-model="newCollection.is_public"
            input-id="public-checkbox"
            binary
          />
          <label for="public-checkbox" class="ml-2 text-sm text-gray-700">
            Make this collection public
          </label>
        </div>
        
        <div class="mt-4 flex gap-2">
          <Button
            @click="createCollection"
            :loading="creating"
            :disabled="!newCollection.name"
            icon="pi pi-plus"
            label="Create Collection"
            size="small"
          />
          <Button
            @click="resetForm"
            icon="pi pi-times"
            label="Clear"
            severity="secondary"
            text
            size="small"
          />
        </div>
      </div>

      <!-- Existing Collections -->
      <div>
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">Your Collections</h3>
          <Badge :value="collectionsStore.collections.length" />
        </div>
        
        <!-- Loading State -->
        <div v-if="collectionsStore.loading" class="text-center py-8">
          <i class="pi pi-spinner pi-spin text-2xl text-gray-400"></i>
          <p class="text-gray-500 mt-2">Loading collections...</p>
        </div>
        
        <!-- Empty State -->
        <div v-else-if="collectionsStore.collections.length === 0" class="text-center py-8">
          <i class="pi pi-folder-open text-4xl text-gray-300 mb-2 block"></i>
          <p class="text-gray-500">No collections yet</p>
          <p class="text-sm text-gray-400">Create your first collection above</p>
        </div>
        
        <!-- Collections List -->
        <div v-else class="space-y-3 max-h-96 overflow-y-auto">
          <Card
            v-for="collection in sortedCollections"
            :key="collection.id"
            class="hover:shadow-md transition-shadow"
          >
            <template #content>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <!-- Color Indicator -->
                  <div
                    :style="{ backgroundColor: collection.color }"
                    class="w-4 h-4 rounded-full flex-shrink-0"
                  ></div>
                  
                  <div class="flex-grow min-w-0">
                    <h4 class="font-medium text-gray-900">{{ collection.name }}</h4>
                    <p v-if="collection.description" class="text-sm text-gray-500 mt-1 line-clamp-2">
                      {{ collection.description }}
                    </p>
                    <div class="flex items-center gap-4 mt-2">
                      <span class="text-xs text-gray-400">
                        {{ collection.items_count || 0 }} items
                      </span>
                      <PrimeTag
                        v-if="collection.is_public"
                        value="Public"
                        severity="success"
                        size="small"
                      />
                      <span class="text-xs text-gray-400">
                        Created {{ formatDate(collection.created_at) }}
                      </span>
                    </div>
                  </div>
                </div>
                
                <div class="flex gap-1 ml-4">
                  <Button
                    @click="editCollection(collection)"
                    icon="pi pi-pencil"
                    severity="secondary"
                    text
                    size="small"
                    v-tooltip.top="'Edit Collection'"
                  />
                  <Button
                    @click="viewCollection(collection)"
                    icon="pi pi-eye"
                    severity="info"
                    text
                    size="small"
                    v-tooltip.top="'View Items'"
                  />
                  <Button
                    @click="deleteCollection(collection)"
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    size="small"
                    v-tooltip.top="'Delete Collection'"
                  />
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>
    </div>

    <!-- Edit Collection Dialog -->
    <Dialog
      v-model:visible="showEditDialog"
      header="Edit Collection"
      modal
      :style="{ width: '500px' }"
    >
      <div v-if="editingCollection" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Collection Name *
          </label>
          <InputText
            v-model="editingCollection.name"
            placeholder="Enter collection name"
            class="w-full"
            :class="{ 'p-invalid': errors.editName }"
          />
          <small v-if="errors.editName" class="p-error">{{ errors.editName }}</small>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Color
          </label>
          <div class="flex gap-2">
            <input
              v-model="editingCollection.color"
              type="color"
              class="w-12 h-10 rounded border border-gray-300 cursor-pointer"
            />
            <InputText
              v-model="editingCollection.color"
              placeholder="#3B82F6"
              class="flex-1"
            />
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Description
          </label>
          <Textarea
            v-model="editingCollection.description"
            placeholder="Optional description for this collection"
            rows="3"
            class="w-full"
          />
        </div>
        
        <div class="flex items-center">
          <Checkbox
            v-model="editingCollection.is_public"
            input-id="edit-public-checkbox"
            binary
          />
          <label for="edit-public-checkbox" class="ml-2 text-sm text-gray-700">
            Make this collection public
          </label>
        </div>
      </div>
      
      <template #footer>
        <div class="flex gap-2">
          <Button
            @click="showEditDialog = false"
            label="Cancel"
            severity="secondary"
            text
          />
          <Button
            @click="updateCollection"
            :loading="updating"
            icon="pi pi-save"
            label="Save Changes"
          />
        </div>
      </template>
    </Dialog>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useCollectionsStore } from '@/stores/collections'

const emit = defineEmits(['close'])

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  }
})

const router = useRouter()
const collectionsStore = useCollectionsStore()

// Reactive state
const creating = ref(false)
const updating = ref(false)
const showEditDialog = ref(false)
const editingCollection = ref(null)
const errors = ref({})

const newCollection = ref({
  name: '',
  description: '',
  color: '#3B82F6',
  is_public: false
})

// Computed properties
const sortedCollections = computed(() => {
  return collectionsStore.sortedCollections
})

// Watch for dialog visibility to load collections
watch(() => props.visible, (visible) => {
  if (visible) {
    collectionsStore.fetchCollections()
  }
})

// Methods
const resetForm = () => {
  newCollection.value = {
    name: '',
    description: '',
    color: '#3B82F6',
    is_public: false
  }
  errors.value = {}
}

const validateForm = () => {
  errors.value = {}
  
  if (!newCollection.value.name.trim()) {
    errors.value.name = 'Collection name is required'
    return false
  }
  
  if (newCollection.value.name.length > 255) {
    errors.value.name = 'Collection name must be less than 255 characters'
    return false
  }
  
  return true
}

const validateEditForm = () => {
  errors.value = {}
  
  if (!editingCollection.value?.name.trim()) {
    errors.value.editName = 'Collection name is required'
    return false
  }
  
  return true
}

const createCollection = async () => {
  if (!validateForm()) return
  
  creating.value = true
  
  try {
    const collectionData = {
      name: newCollection.value.name.trim(),
      description: newCollection.value.description?.trim() || null,
      color: newCollection.value.color,
      is_public: newCollection.value.is_public
    }
    
    await collectionsStore.createCollection(collectionData)
    resetForm()
  } catch (error) {
    if (error.message.includes('unique')) {
      errors.value.name = 'A collection with this name already exists'
    } else {
      errors.value.name = error.message
    }
  } finally {
    creating.value = false
  }
}

const editCollection = (collection) => {
  editingCollection.value = { ...collection }
  showEditDialog.value = true
}

const updateCollection = async () => {
  if (!validateEditForm()) return
  
  updating.value = true
  
  try {
    await collectionsStore.updateCollection(editingCollection.value.id, {
      name: editingCollection.value.name.trim(),
      description: editingCollection.value.description?.trim() || null,
      color: editingCollection.value.color,
      is_public: editingCollection.value.is_public
    })
    
    showEditDialog.value = false
    editingCollection.value = null
  } catch (error) {
    if (error.message.includes('unique')) {
      errors.value.editName = 'A collection with this name already exists'
    } else {
      errors.value.editName = error.message
    }
  } finally {
    updating.value = false
  }
}

const deleteCollection = async (collection) => {
  if (confirm(`Are you sure you want to delete the collection "${collection.name}"?`)) {
    try {
      await collectionsStore.deleteCollection(collection.id)
    } catch (error) {
      console.error('Failed to delete collection:', error)
    }
  }
}

const viewCollection = (collection) => {
  emit('close')
  router.push(`/collections/${collection.id}`)
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString(undefined, { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.collection-manager :deep(.p-dialog-content) {
  padding: 1.5rem;
}
</style>