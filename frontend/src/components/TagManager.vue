<template>
  <Dialog
    :visible="visible"
    @hide="$emit('close')"
    header="Tag Management"
    modal
    :style="{ width: '90vw', maxWidth: '600px' }"
    class="tag-manager"
  >
    <div class="space-y-6">
      <!-- Tag Search/Filter -->
      <div>
        <div class="relative">
          <InputText
            v-model="searchTerm"
            placeholder="Search tags..."
            class="w-full pl-10"
          />
          <i class="pi pi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
        </div>
      </div>

      <!-- Tags Statistics -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 p-4 rounded-lg text-center">
          <div class="text-2xl font-bold text-blue-600">{{ totalTags }}</div>
          <div class="text-sm text-gray-600">Total Tags</div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg text-center">
          <div class="text-2xl font-bold text-green-600">{{ usedTags }}</div>
          <div class="text-sm text-gray-600">Used Tags</div>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg text-center">
          <div class="text-2xl font-bold text-yellow-600">{{ unusedTags }}</div>
          <div class="text-sm text-gray-600">Unused Tags</div>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg text-center">
          <div class="text-2xl font-bold text-purple-600">{{ mostUsedTag?.count || 0 }}</div>
          <div class="text-sm text-gray-600">Most Used</div>
        </div>
      </div>

      <!-- Popular Tags -->
      <div v-if="popularTags.length > 0">
        <h3 class="text-lg font-medium text-gray-900 mb-3">Popular Tags</h3>
        <div class="flex flex-wrap gap-2">
          <Chip
            v-for="tag in popularTags.slice(0, 10)"
            :key="tag.name"
            :label="`${tag.name} (${tag.count})`"
            class="cursor-pointer hover:bg-blue-100"
            @click="selectTag(tag.name)"
          />
        </div>
      </div>

      <!-- All Tags List -->
      <div>
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">All Tags</h3>
          <div class="flex gap-2">
            <Button
              @click="sortBy = sortBy === 'name' ? 'name-desc' : 'name'"
              :severity="sortBy.startsWith('name') ? 'primary' : 'secondary'"
              icon="pi pi-sort-alpha-down"
              size="small"
              text
              v-tooltip.top="'Sort by Name'"
            />
            <Button
              @click="sortBy = sortBy === 'count' ? 'count-desc' : 'count'"
              :severity="sortBy.startsWith('count') ? 'primary' : 'secondary'"
              icon="pi pi-sort-numeric-down"
              size="small"
              text
              v-tooltip.top="'Sort by Usage'"
            />
            <Button
              @click="showUnusedOnly = !showUnusedOnly"
              :severity="showUnusedOnly ? 'warning' : 'secondary'"
              icon="pi pi-filter"
              size="small"
              text
              v-tooltip.top="'Show Unused Only'"
            />
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-8">
          <i class="pi pi-spinner pi-spin text-2xl text-gray-400"></i>
          <p class="text-gray-500 mt-2">Loading tags...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredTags.length === 0" class="text-center py-8">
          <i class="pi pi-tags text-4xl text-gray-300 mb-4 block"></i>
          <p class="text-gray-500 mb-2">
            {{ searchTerm ? 'No tags match your search' : 'No tags found' }}
          </p>
          <p class="text-sm text-gray-400">
            Tags are automatically created when you add them to items
          </p>
        </div>

        <!-- Tags List -->
        <div v-else class="space-y-2 max-h-96 overflow-y-auto">
          <div
            v-for="tag in paginatedTags"
            :key="tag.name"
            class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <div
                :class="[
                  'w-3 h-3 rounded-full',
                  tag.count > 0 ? 'bg-green-400' : 'bg-gray-300'
                ]"
              ></div>
              
              <div class="flex-grow">
                <span class="font-medium text-gray-900">{{ tag.name }}</span>
                <div class="text-sm text-gray-500 mt-1">
                  Used in {{ tag.count }} item{{ tag.count !== 1 ? 's' : '' }}
                  <span v-if="tag.items?.length > 0" class="ml-2">
                    ({{ tag.items.slice(0, 2).map(item => item.title).join(', ') }}{{ tag.items.length > 2 ? `... +${tag.items.length - 2}` : '' }})
                  </span>
                </div>
              </div>
            </div>
            
            <div class="flex items-center gap-2">
              <Badge :value="tag.count" :severity="tag.count > 0 ? 'success' : 'secondary'" />
              
              <Button
                @click="viewTagItems(tag.name)"
                icon="pi pi-eye"
                severity="info"
                text
                size="small"
                v-tooltip.top="'View Items'"
                :disabled="tag.count === 0"
              />
              
              <Button
                @click="deleteTag(tag.name)"
                icon="pi pi-trash"
                severity="danger"
                text
                size="small"
                v-tooltip.top="'Delete Tag'"
                :disabled="tag.count > 0"
              />
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="mt-4 flex justify-center">
          <div class="flex items-center gap-2">
            <Button
              @click="currentPage = Math.max(1, currentPage - 1)"
              :disabled="currentPage <= 1"
              icon="pi pi-chevron-left"
              severity="secondary"
              text
              size="small"
            />
            
            <span class="text-sm text-gray-500">
              Page {{ currentPage }} of {{ totalPages }}
            </span>
            
            <Button
              @click="currentPage = Math.min(totalPages, currentPage + 1)"
              :disabled="currentPage >= totalPages"
              icon="pi pi-chevron-right"
              severity="secondary"
              text
              size="small"
            />
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-between">
        <Button
          @click="refreshTags"
          icon="pi pi-refresh"
          label="Refresh"
          severity="secondary"
          text
          :loading="loading"
        />
        <Button
          @click="$emit('close')"
          icon="pi pi-times"
          label="Close"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useItemsStore } from '@/stores/items'

const emit = defineEmits(['close'])

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  }
})

const router = useRouter()
const itemsStore = useItemsStore()

// Reactive state
const loading = ref(false)
const searchTerm = ref('')
const sortBy = ref('count-desc') // name, name-desc, count, count-desc
const showUnusedOnly = ref(false)
const currentPage = ref(1)
const itemsPerPage = ref(20)

// Computed properties
const allTags = computed(() => {
  const tagMap = new Map()
  
  itemsStore.items.forEach(item => {
    if (item.tags?.length > 0) {
      item.tags.forEach(tag => {
        if (!tagMap.has(tag.name)) {
          tagMap.set(tag.name, {
            name: tag.name,
            count: 0,
            items: []
          })
        }
        
        const tagData = tagMap.get(tag.name)
        tagData.count++
        tagData.items.push(item)
      })
    }
  })
  
  return Array.from(tagMap.values())
})

const filteredTags = computed(() => {
  let tags = allTags.value
  
  // Search filter
  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase()
    tags = tags.filter(tag => tag.name.toLowerCase().includes(search))
  }
  
  // Unused filter
  if (showUnusedOnly.value) {
    tags = tags.filter(tag => tag.count === 0)
  }
  
  // Sort
  tags.sort((a, b) => {
    switch (sortBy.value) {
      case 'name':
        return a.name.localeCompare(b.name)
      case 'name-desc':
        return b.name.localeCompare(a.name)
      case 'count':
        return a.count - b.count
      case 'count-desc':
      default:
        return b.count - a.count
    }
  })
  
  return tags
})

const paginatedTags = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredTags.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(filteredTags.value.length / itemsPerPage.value)
})

const totalTags = computed(() => allTags.value.length)

const usedTags = computed(() => {
  return allTags.value.filter(tag => tag.count > 0).length
})

const unusedTags = computed(() => {
  return allTags.value.filter(tag => tag.count === 0).length
})

const popularTags = computed(() => {
  return [...allTags.value]
    .filter(tag => tag.count > 0)
    .sort((a, b) => b.count - a.count)
})

const mostUsedTag = computed(() => {
  return popularTags.value[0] || null
})

// Watchers
watch(() => props.visible, (visible) => {
  if (visible) {
    refreshTags()
  }
})

watch([searchTerm, showUnusedOnly], () => {
  currentPage.value = 1
})

// Methods
const refreshTags = async () => {
  loading.value = true
  
  try {
    await itemsStore.fetchItems()
  } catch (error) {
    console.error('Failed to refresh tags:', error)
  } finally {
    loading.value = false
  }
}

const selectTag = (tagName) => {
  searchTerm.value = tagName
}

const viewTagItems = (tagName) => {
  emit('close')
  // Navigate to library with tag filter
  router.push(`/library?tag=${encodeURIComponent(tagName)}`)
}

const deleteTag = async (tagName) => {
  if (confirm(`Are you sure you want to delete the tag "${tagName}"? This will remove it from all items.`)) {
    try {
      // Find all items with this tag and remove it
      const itemsWithTag = allTags.value.find(tag => tag.name === tagName)?.items || []
      
      for (const item of itemsWithTag) {
        const updatedTags = item.tags.filter(tag => tag.name !== tagName)
        await itemsStore.updateItem(item.id, {
          ...item,
          tags: updatedTags.map(tag => tag.name)
        })
      }
      
      refreshTags()
    } catch (error) {
      console.error('Failed to delete tag:', error)
    }
  }
}

// Lifecycle
onMounted(() => {
  if (props.visible) {
    refreshTags()
  }
})
</script>

<style scoped>
.tag-manager :deep(.p-dialog-content) {
  padding: 1.5rem;
}
</style>