import api from './api'

class ExternalApiService {
  /**
   * Search for book by ISBN using Open Library API
   */
  async searchBookByIsbn(isbn) {
    try {
      const response = await api.post('/search/book/isbn', { isbn })
      return response.data
    } catch (error) {
      if (error.response?.status === 404) {
        throw new Error(`No book found for ISBN: ${isbn}`)
      }
      if (error.response?.status === 429) {
        throw new Error('Rate limit exceeded. Please wait before searching again.')
      }
      throw new Error('Failed to search for book')
    }
  }

  /**
   * Search for music by barcode using Discogs API
   */
  async searchMusicByBarcode(barcode) {
    try {
      const response = await api.post('/search/music/barcode', { barcode })
      return response.data
    } catch (error) {
      if (error.response?.status === 404) {
        throw new Error(`No music release found for barcode: ${barcode}`)
      }
      if (error.response?.status === 429) {
        throw new Error('Rate limit exceeded. Please wait before searching again.')
      }
      throw new Error('Failed to search for music release')
    }
  }

  /**
   * Search for music by catalog number using Discogs API
   */
  async searchMusicByCatalog(catalogNumber, label = null) {
    try {
      const response = await api.post('/search/music/catalog', {
        catalog_number: catalogNumber,
        label: label
      })
      return response.data
    } catch (error) {
      if (error.response?.status === 404) {
        throw new Error(`No music release found for catalog number: ${catalogNumber}`)
      }
      if (error.response?.status === 429) {
        throw new Error('Rate limit exceeded. Please wait before searching again.')
      }
      throw new Error('Failed to search for music release')
    }
  }

  /**
   * Create item from external API data
   */
  async createFromApiData(apiData, mediaType, additionalData = {}) {
    try {
      const response = await api.post('/items/from-api', {
        source: apiData.source,
        api_data: apiData.data,
        media_type: mediaType,
        ...additionalData
      })
      return response.data
    } catch (error) {
      throw new Error('Failed to create item from API data')
    }
  }

  /**
   * Validate and clean ISBN
   */
  validateIsbn(isbn) {
    const cleaned = isbn.replace(/[^0-9X]/gi, '').toUpperCase()
    if (cleaned.length !== 10 && cleaned.length !== 13) {
      throw new Error('ISBN must be 10 or 13 digits')
    }
    return cleaned
  }

  /**
   * Validate and clean barcode
   */
  validateBarcode(barcode) {
    const cleaned = barcode.replace(/[^0-9]/g, '')
    if (cleaned.length < 8 || cleaned.length > 14) {
      throw new Error('Barcode must be between 8 and 14 digits')
    }
    return cleaned
  }

  /**
   * Format contributor role for display
   */
  formatRole(role) {
    const roleMap = {
      'author': 'Author',
      'artist': 'Artist',
      'label': 'Label',
      'publisher': 'Publisher',
      'producer': 'Producer',
      'composer': 'Composer',
      'performer': 'Performer'
    }
    return roleMap[role] || role.charAt(0).toUpperCase() + role.slice(1)
  }

  /**
   * Get fallback cover image URL
   */
  getFallbackCoverUrl(mediaType) {
    return mediaType === 'book' 
      ? '/images/book-placeholder.svg'
      : '/images/music-placeholder.svg'
  }

  /**
   * Check if image URL is accessible
   */
  async checkImageUrl(url) {
    try {
      const response = await fetch(url, { method: 'HEAD' })
      return response.ok
    } catch {
      return false
    }
  }

  /**
   * Get best available cover image with fallbacks
   */
  async getBestCoverImage(urls, mediaType) {
    const urlsArray = Array.isArray(urls) ? urls : [urls].filter(Boolean)
    
    for (const url of urlsArray) {
      if (await this.checkImageUrl(url)) {
        return url
      }
    }
    
    return this.getFallbackCoverUrl(mediaType)
  }
}

export default new ExternalApiService()