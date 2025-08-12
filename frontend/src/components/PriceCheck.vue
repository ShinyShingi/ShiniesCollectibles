<template>
  <div class="price-check-container space-y-6">
    <!-- Header -->
    <div class="glass-card p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-xl font-semibold text-slate-800">Price Comparison</h3>
          <p class="text-slate-500">Compare prices across multiple book marketplaces</p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="refreshPrices"
            :disabled="loading"
            class="btn-primary"
          >
            <i :class="['pi', loading ? 'pi-spin pi-spinner' : 'pi-refresh', 'mr-2']"></i>
            {{ loading ? 'Checking...' : 'Check Prices' }}
          </button>
        </div>
      </div>

      <!-- Statistics -->
      <div v-if="statistics" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stat-card">
          <i class="pi pi-money-bill text-emerald-500"></i>
          <div class="stat-content">
            <span class="stat-number">€{{ statistics.lowest_price?.toFixed(2) || '—' }}</span>
            <span class="stat-label">Lowest Price</span>
          </div>
        </div>
        <div class="stat-card">
          <i class="pi pi-chart-line text-blue-500"></i>
          <div class="stat-content">
            <span class="stat-number">€{{ statistics.average_price?.toFixed(2) || '—' }}</span>
            <span class="stat-label">Average Price</span>
          </div>
        </div>
        <div class="stat-card">
          <i class="pi pi-shopping-cart text-purple-500"></i>
          <div class="stat-content">
            <span class="stat-number">{{ statistics.offers_count || 0 }}</span>
            <span class="stat-label">Total Offers</span>
          </div>
        </div>
        <div class="stat-card">
          <i class="pi pi-globe text-indigo-500"></i>
          <div class="stat-content">
            <span class="stat-number">{{ statistics.sources_count || 0 }}</span>
            <span class="stat-label">Sources</span>
          </div>
        </div>
      </div>

      <!-- Last Updated -->
      <div v-if="lastUpdated" class="mt-4 text-sm text-slate-500">
        Last updated: {{ formatDate(lastUpdated) }}
      </div>
    </div>

    <!-- No Data State -->
    <div v-if="!loading && !hasPriceData" class="empty-state">
      <div class="empty-state-icon">
        <i class="pi pi-search-dollar"></i>
      </div>
      <h3 class="empty-state-title">No Price Data Available</h3>
      <p class="empty-state-description">
        Click "Check Prices" to search for this book across multiple marketplaces and get the best deals.
      </p>
      <button @click="refreshPrices" class="btn-primary">
        <i class="pi pi-search mr-2"></i>
        Check Prices Now
      </button>
    </div>

    <!-- Price Results -->
    <div v-else-if="!loading && hasPriceData" class="space-y-4">
      <!-- Source Filter -->
      <div class="glass-card p-4">
        <div class="flex items-center justify-between mb-3">
          <h4 class="font-medium text-slate-800">Filter by Source</h4>
          <span class="text-sm text-slate-500">{{ filteredOffers.length }} offers</span>
        </div>
        
        <!-- Source Tabs -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
          <button
            v-for="source in availableSources"
            :key="source"
            @click="selectedSource = source"
            :class="[
              'flex items-center justify-between px-3 py-2 rounded-lg font-medium text-sm transition-all duration-200',
              selectedSource === source
                ? 'bg-indigo-600 text-white shadow-md'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
            ]"
          >
            <span>{{ getSourceDisplayName(source) }}</span>
            <span :class="[
              'px-2 py-0.5 rounded-full text-xs font-bold',
              selectedSource === source 
                ? 'bg-white/20 text-white' 
                : 'bg-slate-200 text-slate-600'
            ]">
              {{ getSourceOfferCount(source) }}
            </span>
          </button>
        </div>
      </div>

      <!-- Price Offers -->
      <div class="grid gap-4">
        <div
          v-for="offer in filteredOffers"
          :key="`${offer.source}-${offer.id}`"
          class="offer-card"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <span class="source-badge" :class="`source-${offer.source}`">
                  {{ getSourceDisplayName(offer.source) }}
                </span>
                <span class="badge-success">{{ offer.condition }}</span>
                <span v-if="!offer.is_available" class="badge-error">Unavailable</span>
              </div>
              
              <h4 class="font-medium text-slate-800 mb-1">{{ offer.title }}</h4>
              <p v-if="offer.author" class="text-sm text-slate-500 mb-2">{{ offer.author }}</p>
              
              <div class="flex items-center gap-4 text-sm text-slate-600">
                <span v-if="offer.seller_name">
                  <i class="pi pi-user mr-1"></i>
                  {{ offer.seller_name }}
                </span>
                <span v-if="offer.seller_location">
                  <i class="pi pi-map-marker mr-1"></i>
                  {{ offer.seller_location }}
                </span>
              </div>
              
              <p v-if="offer.description" class="text-sm text-slate-500 mt-2 line-clamp-2">
                {{ offer.description }}
              </p>
            </div>
            
            <div class="text-right ml-4">
              <div class="text-2xl font-bold text-slate-800">
                €{{ offer.total_cost.toFixed(2) }}
              </div>
              <div v-if="offer.shipping_cost > 0" class="text-sm text-slate-500">
                + €{{ offer.shipping_cost.toFixed(2) }} shipping
              </div>
              <div class="text-xs text-slate-400 mt-1">
                {{ offer.currency }}
              </div>
              
              <a
                :href="offer.url"
                target="_blank"
                rel="noopener noreferrer"
                class="btn-secondary mt-3 text-sm"
                @click="trackOfferClick(offer)"
              >
                <i class="pi pi-external-link mr-1"></i>
                View Offer
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Load More -->
      <div v-if="hasMoreOffers" class="text-center">
        <button @click="loadMoreOffers" class="btn-ghost">
          Load More Offers
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <div v-for="n in 5" :key="n" class="loading-card">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="loading-card-title mb-2"></div>
            <div class="loading-card-subtitle mb-3"></div>
            <div class="loading-card-subtitle w-1/2"></div>
          </div>
          <div class="w-24 h-16 bg-slate-200 rounded loading-pulse"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '@/services/api'

const props = defineProps({
  item: {
    type: Object,
    required: true
  }
})

const toast = useToast()

// Reactive state
const loading = ref(false)
const priceData = ref({})
const statistics = ref(null)
const lastUpdated = ref(null)
const selectedSource = ref('all')
const displayCount = ref(10)

// Computed properties
const availableSources = computed(() => {
  const sources = Object.keys(priceData.value)
  return ['all', ...sources]
})

const hasPriceData = computed(() => {
  return Object.keys(priceData.value).length > 0
})

const filteredOffers = computed(() => {
  let offers = []
  
  if (selectedSource.value === 'all') {
    // Combine all offers from all sources
    Object.values(priceData.value).forEach(sourceOffers => {
      offers = [...offers, ...sourceOffers]
    })
  } else {
    offers = priceData.value[selectedSource.value] || []
  }
  
  // Sort by total cost and limit display
  return offers
    .filter(offer => offer.is_available)
    .sort((a, b) => a.total_cost - b.total_cost)
    .slice(0, displayCount.value)
})

const hasMoreOffers = computed(() => {
  let totalOffers = 0
  if (selectedSource.value === 'all') {
    Object.values(priceData.value).forEach(sourceOffers => {
      totalOffers += sourceOffers.filter(offer => offer.is_available).length
    })
  } else {
    totalOffers = (priceData.value[selectedSource.value] || [])
      .filter(offer => offer.is_available).length
  }
  return totalOffers > displayCount.value
})

// Methods
const loadPriceData = async () => {
  try {
    loading.value = true
    const response = await api.get(`/items/${props.item.id}/price-checks`)
    
    priceData.value = response.data.data
    lastUpdated.value = response.data.last_updated
    
    // Load statistics
    await loadStatistics()
  } catch (error) {
    console.error('Failed to load price data:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load price data',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadStatistics = async () => {
  try {
    const response = await api.get(`/items/${props.item.id}/price-checks/statistics`)
    statistics.value = response.data.data
  } catch (error) {
    // Statistics are optional, don't show error
    statistics.value = null
  }
}

const refreshPrices = async () => {
  try {
    loading.value = true
    
    // Trigger price check
    await api.post(`/items/${props.item.id}/price-checks/refresh`)
    
    toast.add({
      severity: 'info',
      summary: 'Price Check Started',
      detail: 'Checking prices across multiple sources...',
      life: 3000
    })
    
    // Poll for results
    setTimeout(async () => {
      await loadPriceData()
      toast.add({
        severity: 'success',
        summary: 'Prices Updated',
        detail: 'Latest prices have been loaded',
        life: 3000
      })
    }, 5000)
    
  } catch (error) {
    console.error('Failed to refresh prices:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to refresh prices',
      life: 3000
    })
    loading.value = false
  }
}

const getSourceDisplayName = (source) => {
  const sourceNames = {
    'abebooks': 'AbeBooks',
    'zvab': 'ZVAB',
    'rebuy': 'Rebuy',
    'medimops': 'Medimops',
    'ebay': 'eBay',
    'amazon': 'Amazon',
    'thalia': 'Thalia',
    'hugendubel': 'Hugendubel',
    'waterstones': 'Waterstones',
    'all': 'All Sources'
  }
  return sourceNames[source] || source
}

const getSourceOfferCount = (source) => {
  if (source === 'all') {
    let total = 0
    Object.values(priceData.value).forEach(offers => {
      total += offers.filter(offer => offer.is_available).length
    })
    return total
  }
  return (priceData.value[source] || []).filter(offer => offer.is_available).length
}

const loadMoreOffers = () => {
  displayCount.value += 10
}

const trackOfferClick = (offer) => {
  // Track analytics if needed
  console.log('Offer clicked:', offer.source, offer.total_cost)
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString()
}

// Lifecycle
onMounted(() => {
  if (props.item.media_type === 'book') {
    loadPriceData()
  }
})

// Watch for item changes
watch(() => props.item, () => {
  if (props.item.media_type === 'book') {
    loadPriceData()
  }
}, { deep: true })
</script>

<style scoped>
.offer-card {
  @apply glass-card p-4 hover:shadow-lg transition-all duration-200;
}

.source-badge {
  @apply px-2 py-1 text-xs font-medium rounded-md;
}

.source-abebooks {
  @apply bg-blue-100 text-blue-800;
}

.source-zvab {
  @apply bg-green-100 text-green-800;
}

.source-rebuy {
  @apply bg-purple-100 text-purple-800;
}

.source-medimops {
  @apply bg-orange-100 text-orange-800;
}

.source-ebay {
  @apply bg-yellow-100 text-yellow-800;
}

.source-amazon {
  @apply bg-slate-100 text-slate-800;
}

.source-thalia {
  @apply bg-red-100 text-red-800;
}

.source-hugendubel {
  @apply bg-pink-100 text-pink-800;
}

.source-waterstones {
  @apply bg-teal-100 text-teal-800;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>