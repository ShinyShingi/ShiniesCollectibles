<template>
  <div class="library-interface px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    <!-- Modern Header Section -->
    <div class="glass-card p-6 mb-8">
      <!-- Title and Search Row -->
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-6">
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
              <i class="pi pi-book text-white text-xl"></i>
            </div>
            <div>
              <h1 class="text-3xl font-bold text-slate-800">My Library</h1>
              <p class="text-slate-500">Discover and organize your collection</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <div class="badge-primary">
              <i class="pi pi-star mr-1"></i>
              {{ filteredItems.length }} items
            </div>
          </div>
        </div>
        
        <!-- Search and Actions -->
        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
          <!-- Enhanced Search -->
          <div class="relative group">
            <input
              v-model="filters.search"
              placeholder="Search your collection..."
              class="input-modern pl-12 pr-4 py-3 w-full sm:w-80 bg-white/80 backdrop-blur-sm border-slate-200/60 focus:border-indigo-300 focus:ring-indigo-500/20 shadow-sm"
            />
            <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
              <i class="pi pi-search text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
            </div>
            <div v-if="filters.search" class="absolute right-3 top-1/2 transform -translate-y-1/2">
              <button @click="filters.search = ''" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="pi pi-times text-sm"></i>
              </button>
            </div>
          </div>
          
          <!-- Modern View Toggle -->
          <div class="view-toggle-modern">
            <button
              @click="viewMode = 'grid'"
              :class="['view-toggle-btn', { active: viewMode === 'grid' }]"
              v-tooltip.top="'Grid View'"
            >
              <i class="pi pi-th-large"></i>
            </button>
            <button
              @click="viewMode = 'list'"
              :class="['view-toggle-btn', { active: viewMode === 'list' }]"
              v-tooltip.top="'List View'"
            >
              <i class="pi pi-list"></i>
            </button>
            <button
              @click="viewMode = 'table'"
              :class="['view-toggle-btn', { active: viewMode === 'table' }]"
              v-tooltip.top="'Table View'"
            >
              <i class="pi pi-table"></i>
            </button>
          </div>
        </div>
      </div>
      
      <!-- Advanced Filters -->
      <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <div class="filter-section">
          <label class="filter-label">Media Type</label>
          <Dropdown
            v-model="filters.mediaType"
            :options="mediaTypeOptions"
            option-label="label"
            option-value="value"
            placeholder="All Types"
            class="w-full"
            show-clear
          />
        </div>
        
        <div class="filter-section">
          <label class="filter-label">Year Range</label>
          <div class="flex gap-2">
            <InputNumber
              v-model="filters.yearFrom"
              placeholder="From"
              :min="1800"
              :max="new Date().getFullYear() + 10"
              class="w-full"
            />
            <InputNumber
              v-model="filters.yearTo"
              placeholder="To"
              :min="1800"
              :max="new Date().getFullYear() + 10"
              class="w-full"
            />
          </div>
        </div>
        
        <div class="filter-section">
          <label class="filter-label">Condition</label>
          <MultiSelect
            v-model="filters.conditions"
            :options="conditionOptions"
            option-label="label"
            option-value="value"
            placeholder="All Conditions"
            class="w-full"
            display="chip"
          />
        </div>
        
        <div class="filter-section">
          <label class="filter-label">Collections</label>
          <MultiSelect
            v-model="filters.collections"
            :options="collectionsStore.collections"
            option-label="name"
            option-value="id"
            placeholder="All Collections"
            class="w-full"
            display="chip"
          />
        </div>
        
        <div class="filter-section">
          <label class="filter-label">Tags</label>
          <MultiSelect
            v-model="filters.tags"
            :options="availableTags"
            option-label="name"
            option-value="name"
            placeholder="All Tags"
            class="w-full"
            display="chip"
          />
        </div>
        
        <div class="filter-section">
          <label class="filter-label">Ownership</label>
          <Dropdown
            v-model="filters.owned"
            :options="ownershipOptions"
            option-label="label"
            option-value="value"
            placeholder="All Items"
            class="w-full"
            show-clear
          />
        </div>
      </div>
      
      <!-- Filter Actions -->
      <div class="mt-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <button
          @click="clearFilters"
          class="btn-ghost"
        >
          <i class="pi pi-filter-slash mr-2"></i>
          Clear Filters
        </button>
        
        <div class="flex gap-3">
          <SplitButton
            :model="sortOptions"
            @click="toggleSort"
            icon="pi pi-sort"
            :label="currentSortLabel"
            size="small"
            class="btn-secondary"
          />
          
          <button
            @click="showCollectionManager = true"
            class="btn-secondary"
          >
            <i class="pi pi-folder mr-2"></i>
            Collections
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-grid">
      <div v-for="n in 12" :key="n" class="loading-card">
        <div class="loading-card-image"></div>
        <div class="loading-card-title"></div>
        <div class="loading-card-subtitle"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredItems.length === 0 && !loading" class="empty-state">
      <div class="empty-state-icon">
        <i :class="`pi ${hasFilters ? 'pi-search' : 'pi-inbox'}`"></i>
      </div>
      <h3 class="empty-state-title">
        {{ hasFilters ? 'No items match your filters' : 'No items in your library' }}
      </h3>
      <p class="empty-state-description">
        {{ hasFilters ? 'Try adjusting your search criteria or browse different categories' : 'Start building your collection by adding some books or music albums' }}
      </p>
      <div class="flex gap-3 justify-center">
        <button
          v-if="hasFilters"
          @click="clearFilters"
          class="btn-secondary"
        >
          <i class="pi pi-filter-slash mr-2"></i>
          Clear Filters
        </button>
        <button
          v-else
          @click="$router.push('/items/new')"
          class="btn-primary"
        >
          <i class="pi pi-plus mr-2"></i>
          Add First Item
        </button>
      </div>
    </div>

    <!-- Grid View -->
    <div v-else-if="viewMode === 'grid'" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
      <div
        v-for="item in paginatedItems"
        :key="item.id"
        class="item-card-modern"
        @click="viewItem(item)"
      >
        <!-- Badges -->
        <div class="item-card-badges">
          <div v-if="item.condition" class="item-card-badge condition">
            {{ item.condition }}
          </div>
          <div v-if="!item.owned" class="item-card-badge wishlist">
            Wishlist
          </div>
        </div>
        
        <!-- Image Container -->
        <div class="relative overflow-hidden">
          <img
            :src="item.cover_url || getFallbackImage(item.media_type)"
            :alt="item.title"
            class="item-card-image"
            @error="handleImageError"
          />
          
          <!-- Hover Overlay -->
          <div class="item-card-overlay"></div>
          
          <!-- Quick Actions -->
          <div class="item-card-actions">
            <button
              @click.stop="editItem(item)"
              class="item-card-action-btn"
              v-tooltip.top="'Edit'"
            >
              <i class="pi pi-pencil text-sm"></i>
            </button>
            <button
              @click.stop="showCollectionModal(item)"
              class="item-card-action-btn"
              v-tooltip.top="'Add to Collection'"
            >
              <i class="pi pi-folder-plus text-sm"></i>
            </button>
            <button
              @click.stop="deleteItem(item)"
              class="item-card-action-btn"
              v-tooltip.top="'Delete'"
            >
              <i class="pi pi-trash text-sm"></i>
            </button>
          </div>
        </div>
        
        <!-- Content -->
        <div class="item-card-content">
          <h3 class="item-card-title">{{ item.title }}</h3>
          <p v-if="item.contributors?.length" class="item-card-subtitle">
            {{ item.contributors[0].name }}{{ item.contributors.length > 1 ? ` +${item.contributors.length - 1}` : '' }}
          </p>
          <p v-if="item.year" class="item-card-year">{{ item.year }}</p>
          
          <!-- Tags -->
          <div v-if="item.tags?.length" class="mt-3 flex flex-wrap gap-1.5">
            <span
              v-for="tag in item.tags.slice(0, 2)"
              :key="tag.id"
              class="px-2 py-1 text-xs font-medium bg-indigo-50 text-indigo-700 rounded-md"
            >
              {{ tag.name }}
            </span>
            <span
              v-if="item.tags.length > 2"
              class="px-2 py-1 text-xs font-medium bg-slate-100 text-slate-600 rounded-md"
            >
              +{{ item.tags.length - 2 }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- List View -->
    <div v-else-if="viewMode === 'list'" class="space-y-3">
      <div
        v-for="item in paginatedItems"
        :key="item.id"
        class="list-item-modern p-4"
        @click="viewItem(item)"
      >
        <div class="flex gap-4">
          <div class="relative flex-shrink-0">
            <img
              :src="item.cover_url || getFallbackImage(item.media_type)"
              :alt="item.title"
              class="w-16 h-20 object-cover rounded-lg shadow-sm"
              @error="handleImageError"
            />
            <!-- Status indicator -->
            <div v-if="!item.owned" class="absolute -top-1 -right-1 w-3 h-3 bg-amber-400 rounded-full border-2 border-white shadow-sm"></div>
          </div>
          
          <div class="flex-grow min-w-0">
            <div class="flex items-start justify-between">
              <div class="flex-grow min-w-0">
                <h3 class="font-semibold text-slate-800 truncate group-hover:text-indigo-700 transition-colors">{{ item.title }}</h3>
                <p v-if="item.contributors?.length" class="text-sm text-slate-500 mt-0.5">
                  {{ item.contributors.map(c => c.name).join(', ') }}
                </p>
                <div class="flex items-center gap-2 mt-2">
                  <span class="badge-primary">
                    <i :class="`pi ${item.media_type === 'book' ? 'pi-book' : 'pi-volume-up'} mr-1`"></i>
                    {{ item.media_type }}
                  </span>
                  <span v-if="item.year" class="text-sm text-slate-400 font-medium">{{ item.year }}</span>
                  <span v-if="item.condition" class="badge-success">{{ item.condition }}</span>
                  <span v-if="!item.owned" class="badge-warning">
                    <i class="pi pi-heart mr-1"></i>
                    Wishlist
                  </span>
                </div>
              </div>
              
              <div class="list-item-actions flex gap-1 ml-4 opacity-0 transition-opacity">
                <button
                  @click.stop="editItem(item)"
                  class="btn-ghost p-2"
                  v-tooltip.top="'Edit'"
                >
                  <i class="pi pi-pencil text-sm"></i>
                </button>
                <button
                  @click.stop="showCollectionModal(item)"
                  class="btn-ghost p-2"
                  v-tooltip.top="'Add to Collection'"
                >
                  <i class="pi pi-folder-plus text-sm"></i>
                </button>
                <button
                  @click.stop="deleteItem(item)"
                  class="btn-ghost p-2 hover:text-red-500 hover:bg-red-50"
                  v-tooltip.top="'Delete'"
                >
                  <i class="pi pi-trash text-sm"></i>
                </button>
              </div>
            </div>
            
            <!-- Tags -->
            <div v-if="item.tags?.length" class="mt-3 flex flex-wrap gap-1.5">
              <span
                v-for="tag in item.tags.slice(0, 4)"
                :key="tag.id"
                class="px-2 py-1 text-xs font-medium bg-slate-100 text-slate-600 rounded-md hover:bg-indigo-100 hover:text-indigo-700 transition-colors"
              >
                {{ tag.name }}
              </span>
              <span
                v-if="item.tags.length > 4"
                class="px-2 py-1 text-xs font-medium bg-slate-200 text-slate-500 rounded-md"
              >
                +{{ item.tags.length - 4 }} more
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Table View -->
    <div v-else-if="viewMode === 'table'" class="bg-white rounded-lg shadow">
      <DataTable
        :value="paginatedItems"
        :paginator="false"
        :rows="itemsPerPage"
        striped-rows
        responsive-layout="scroll"
        :global-filter-fields="['title', 'contributors.name', 'tags.name']"
        @row-click="onRowClick"
        class="cursor-pointer"
      >
        <Column field="cover_url" header="" style="width: 60px">
          <template #body="slotProps">
            <img
              :src="slotProps.data.cover_url || getFallbackImage(slotProps.data.media_type)"
              :alt="slotProps.data.title"
              class="w-12 h-12 object-cover rounded"
              @error="handleImageError"
            />
          </template>
        </Column>
        
        <Column field="title" header="Title" sortable>
          <template #body="slotProps">
            <div>
              <div class="font-medium">{{ slotProps.data.title }}</div>
              <div v-if="slotProps.data.contributors?.length" class="text-sm text-gray-500">
                {{ slotProps.data.contributors[0].name }}{{ slotProps.data.contributors.length > 1 ? ` +${slotProps.data.contributors.length - 1}` : '' }}
              </div>
            </div>
          </template>
        </Column>
        
        <Column field="media_type" header="Type" sortable style="width: 100px">
          <template #body="slotProps">
            <PrimeTag
              :value="slotProps.data.media_type"
              :severity="slotProps.data.media_type === 'book' ? 'info' : 'success'"
            />
          </template>
        </Column>
        
        <Column field="year" header="Year" sortable style="width: 80px">
          <template #body="slotProps">
            {{ slotProps.data.year || '-' }}
          </template>
        </Column>
        
        <Column field="condition" header="Condition" sortable style="width: 120px">
          <template #body="slotProps">
            <PrimeTag
              v-if="slotProps.data.condition"
              :value="slotProps.data.condition"
              severity="secondary"
            />
            <span v-else class="text-gray-400">-</span>
          </template>
        </Column>
        
        <Column field="owned" header="Status" sortable style="width: 100px">
          <template #body="slotProps">
            <PrimeTag
              :value="slotProps.data.owned ? 'Owned' : 'Wishlist'"
              :severity="slotProps.data.owned ? 'success' : 'warning'"
            />
          </template>
        </Column>
        
        <Column field="tags" header="Tags" style="width: 200px">
          <template #body="slotProps">
            <div class="flex flex-wrap gap-1">
              <Chip
                v-for="tag in slotProps.data.tags?.slice(0, 3)"
                :key="tag.id"
                :label="tag.name"
                size="small"
              />
              <Chip
                v-if="slotProps.data.tags?.length > 3"
                :label="`+${slotProps.data.tags.length - 3}`"
                size="small"
              />
            </div>
          </template>
        </Column>
        
        <Column style="width: 120px">
          <template #body="slotProps">
            <div class="flex gap-1">
              <Button
                @click.stop="editItem(slotProps.data)"
                icon="pi pi-pencil"
                severity="secondary"
                text
                size="small"
              />
              <Button
                @click.stop="showCollectionModal(slotProps.data)"
                icon="pi pi-folder-plus"
                severity="secondary"
                text
                size="small"
              />
              <Button
                @click.stop="deleteItem(slotProps.data)"
                icon="pi pi-trash"
                severity="danger"
                text
                size="small"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Pagination -->
    <div v-if="filteredItems.length > itemsPerPage" class="mt-8 flex justify-center">
      <div class="glass-card px-4 py-3 flex items-center gap-2 shadow-sm">
        <button
          @click="currentPage = Math.max(1, currentPage - 1)"
          :disabled="currentPage <= 1"
          class="btn-ghost p-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <i class="pi pi-chevron-left"></i>
        </button>
        
        <div class="flex gap-1">
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="currentPage = page"
            :class="[
              'w-10 h-10 rounded-lg font-medium text-sm transition-all duration-200',
              currentPage === page 
                ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md' 
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
            ]"
          >
            {{ page }}
          </button>
        </div>
        
        <button
          @click="currentPage = Math.min(totalPages, currentPage + 1)"
          :disabled="currentPage >= totalPages"
          class="btn-ghost p-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <i class="pi pi-chevron-right"></i>
        </button>
        
        <div class="ml-4 text-sm text-slate-500 border-l border-slate-200 pl-4">
          {{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredItems.length) }} of {{ filteredItems.length }}
        </div>
      </div>
    </div>

    <!-- Collection Management Modal -->
    <CollectionManager
      v-if="showCollectionManager"
      :visible="showCollectionManager"
      @close="showCollectionManager = false"
    />
    
    <!-- Add to Collection Modal -->
    <AddToCollectionModal
      v-if="showAddToCollection"
      :visible="showAddToCollection"
      :item="selectedItem"
      @close="showAddToCollection = false"
      @added="onItemAddedToCollection"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useItemsStore } from '@/stores/items'
import { useCollectionsStore } from '@/stores/collections'
import externalApiService from '@/services/externalApi'
import CollectionManager from './CollectionManager.vue'
import AddToCollectionModal from './AddToCollectionModal.vue'

const router = useRouter()
const itemsStore = useItemsStore()
const collectionsStore = useCollectionsStore()

// Reactive state
const loading = ref(false)
const viewMode = ref('grid')
const currentPage = ref(1)
const itemsPerPage = ref(24)
const showCollectionManager = ref(false)
const showAddToCollection = ref(false)
const selectedItem = ref(null)

// Filters
const filters = ref({
  search: '',
  mediaType: null,
  yearFrom: null,
  yearTo: null,
  conditions: [],
  collections: [],
  tags: [],
  owned: null
})

// Sort
const sortField = ref('created_at')
const sortOrder = ref('desc')

// Options
const mediaTypeOptions = [
  { label: 'Books', value: 'book' },
  { label: 'Music', value: 'music' }
]

const conditionOptions = [
  { label: 'Mint', value: 'mint' },
  { label: 'Near Mint', value: 'near_mint' },
  { label: 'Excellent', value: 'excellent' },
  { label: 'Very Good', value: 'very_good' },
  { label: 'Good', value: 'good' },
  { label: 'Fair', value: 'fair' },
  { label: 'Poor', value: 'poor' }
]

const ownershipOptions = [
  { label: 'Owned', value: true },
  { label: 'Wishlist', value: false }
]

const sortOptions = [
  { label: 'Title A-Z', command: () => setSortField('title', 'asc') },
  { label: 'Title Z-A', command: () => setSortField('title', 'desc') },
  { label: 'Year (Newest)', command: () => setSortField('year', 'desc') },
  { label: 'Year (Oldest)', command: () => setSortField('year', 'asc') },
  { label: 'Recently Added', command: () => setSortField('created_at', 'desc') },
  { label: 'Oldest Added', command: () => setSortField('created_at', 'asc') }
]

// Computed properties
const availableTags = computed(() => {
  const allTags = new Set()
  itemsStore.items.forEach(item => {
    item.tags?.forEach(tag => allTags.add(tag.name))
  })
  return Array.from(allTags).map(name => ({ name }))
})

const filteredItems = computed(() => {
  let items = [...itemsStore.items]
  
  // Text search
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    items = items.filter(item => 
      item.title.toLowerCase().includes(search) ||
      item.contributors?.some(c => c.name.toLowerCase().includes(search)) ||
      item.tags?.some(t => t.name.toLowerCase().includes(search))
    )
  }
  
  // Media type filter
  if (filters.value.mediaType) {
    items = items.filter(item => item.media_type === filters.value.mediaType)
  }
  
  // Year range filter
  if (filters.value.yearFrom) {
    items = items.filter(item => item.year && item.year >= filters.value.yearFrom)
  }
  if (filters.value.yearTo) {
    items = items.filter(item => item.year && item.year <= filters.value.yearTo)
  }
  
  // Condition filter
  if (filters.value.conditions.length > 0) {
    items = items.filter(item => 
      item.condition && filters.value.conditions.includes(item.condition)
    )
  }
  
  // Tags filter
  if (filters.value.tags.length > 0) {
    items = items.filter(item =>
      item.tags?.some(tag => filters.value.tags.includes(tag.name))
    )
  }
  
  // Ownership filter
  if (filters.value.owned !== null) {
    items = items.filter(item => item.owned === filters.value.owned)
  }
  
  // Collection filter
  if (filters.value.collections.length > 0) {
    items = items.filter(item =>
      item.collections?.some(collection => 
        filters.value.collections.includes(collection.id)
      )
    )
  }
  
  // Sort
  items.sort((a, b) => {
    let aVal = a[sortField.value]
    let bVal = b[sortField.value]
    
    if (sortField.value === 'title') {
      aVal = aVal?.toLowerCase() || ''
      bVal = bVal?.toLowerCase() || ''
    }
    
    if (aVal === null || aVal === undefined) aVal = sortOrder.value === 'asc' ? '' : 'zzz'
    if (bVal === null || bVal === undefined) bVal = sortOrder.value === 'asc' ? '' : 'zzz'
    
    if (sortOrder.value === 'asc') {
      return aVal > bVal ? 1 : -1
    } else {
      return aVal < bVal ? 1 : -1
    }
  })
  
  return items
})

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredItems.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(filteredItems.value.length / itemsPerPage.value)
})

const visiblePages = computed(() => {
  const pages = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(totalPages.value, currentPage.value + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

const hasFilters = computed(() => {
  return filters.value.search || 
         filters.value.mediaType || 
         filters.value.yearFrom || 
         filters.value.yearTo || 
         filters.value.conditions.length > 0 || 
         filters.value.collections.length > 0 || 
         filters.value.tags.length > 0 || 
         filters.value.owned !== null
})

const currentSortLabel = computed(() => {
  const option = sortOptions.find(opt => {
    if (sortField.value === 'title') return sortOrder.value === 'asc' ? opt.label === 'Title A-Z' : opt.label === 'Title Z-A'
    if (sortField.value === 'year') return sortOrder.value === 'desc' ? opt.label === 'Year (Newest)' : opt.label === 'Year (Oldest)'
    if (sortField.value === 'created_at') return sortOrder.value === 'desc' ? opt.label === 'Recently Added' : opt.label === 'Oldest Added'
    return false
  })
  return option?.label || 'Sort'
})

// Watch filters and reset pagination
watch(filters, () => {
  currentPage.value = 1
}, { deep: true })

// Methods
const clearFilters = () => {
  filters.value = {
    search: '',
    mediaType: null,
    yearFrom: null,
    yearTo: null,
    conditions: [],
    collections: [],
    tags: [],
    owned: null
  }
}

const setSortField = (field, order) => {
  sortField.value = field
  sortOrder.value = order
}

const toggleSort = () => {
  sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
}

const getFallbackImage = (mediaType) => {
  return externalApiService.getFallbackCoverUrl(mediaType)
}

const handleImageError = (event) => {
  const mediaType = event.target.alt?.toLowerCase().includes('music') ? 'music' : 'book'
  event.target.src = getFallbackImage(mediaType)
}

const viewItem = (item) => {
  router.push(`/items/${item.id}`)
}

const editItem = (item) => {
  router.push(`/items/${item.id}/edit`)
}

const deleteItem = async (item) => {
  if (confirm(`Are you sure you want to delete "${item.title}"?`)) {
    try {
      await itemsStore.deleteItem(item.id)
    } catch (error) {
      console.error('Failed to delete item:', error)
    }
  }
}

const showCollectionModal = (item) => {
  selectedItem.value = item
  showAddToCollection.value = true
}

const onRowClick = (event) => {
  viewItem(event.data)
}

const onItemAddedToCollection = () => {
  showAddToCollection.value = false
  selectedItem.value = null
}

// Lifecycle
onMounted(async () => {
  loading.value = true
  try {
    await Promise.all([
      itemsStore.fetchItems(),
      collectionsStore.fetchCollections()
    ])
  } catch (error) {
    console.error('Failed to load data:', error)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.library-interface :deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: #f8fafc;
}
</style>