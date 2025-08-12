import { ref, computed } from 'vue'
import api from '@/services/api'

export function usePriceAlerts() {
  const alerts = ref([])
  const statistics = ref({})
  const loading = ref(false)
  const error = ref(null)

  // Get price alerts
  const fetchAlerts = async (options = {}) => {
    loading.value = true
    error.value = null
    try {
      const params = new URLSearchParams()
      if (options.unreadOnly) params.append('unread_only', 'true')
      if (options.days) params.append('days', options.days)
      
      const response = await api.get(`/price-alerts?${params}`)
      alerts.value = response.data.data
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch price alerts'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Get price alert statistics
  const fetchStatistics = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/price-alerts/statistics')
      statistics.value = response.data
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch statistics'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Mark alert as read
  const markAsRead = async (alertId) => {
    loading.value = true
    error.value = null
    try {
      await api.patch(`/price-alerts/${alertId}/read`)
      const alert = alerts.value.find(a => a.id === alertId)
      if (alert) {
        alert.is_read = true
      }
      // Update statistics
      if (statistics.value.unread_alerts) {
        statistics.value.unread_alerts--
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to mark alert as read'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Mark all alerts as read
  const markAllAsRead = async () => {
    loading.value = true
    error.value = null
    try {
      await api.patch('/price-alerts/mark-all-read')
      alerts.value.forEach(alert => {
        alert.is_read = true
      })
      // Update statistics
      statistics.value.unread_alerts = 0
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to mark all alerts as read'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Delete alert
  const deleteAlert = async (alertId) => {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/price-alerts/${alertId}`)
      alerts.value = alerts.value.filter(a => a.id !== alertId)
      // Update statistics
      if (statistics.value.total_alerts) {
        statistics.value.total_alerts--
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete alert'
      throw err
    } finally {
      loading.value = false
    }
  }

  const unreadAlerts = computed(() => alerts.value.filter(a => !a.is_read))
  const readAlerts = computed(() => alerts.value.filter(a => a.is_read))
  const unreadCount = computed(() => unreadAlerts.value.length)
  const totalSavings = computed(() => 
    alerts.value.reduce((sum, alert) => sum + (alert.savings || 0), 0)
  )

  return {
    // State
    alerts,
    statistics,
    loading,
    error,

    // Actions
    fetchAlerts,
    fetchStatistics,
    markAsRead,
    markAllAsRead,
    deleteAlert,

    // Computed
    unreadAlerts,
    readAlerts,
    unreadCount,
    totalSavings
  }
}