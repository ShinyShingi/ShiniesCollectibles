<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DiscogsService
{
    private const BASE_URL = 'https://api.discogs.com';
    private const CACHE_TTL = 3600; // 1 hour
    private const RATE_LIMIT_DELAY = 1; // 1 second between requests

    private string $userAgent;
    private ?string $token;

    public function __construct()
    {
        $this->userAgent = config('services.discogs.user_agent', 'ShiniesCollectibles/1.0');
        $this->token = config('services.discogs.token');
    }

    public function searchByBarcode(string $barcode): ?array
    {
        $barcode = $this->cleanBarcode($barcode);
        
        if (!$this->isValidBarcode($barcode)) {
            return null;
        }

        $cacheKey = "discogs_barcode_{$barcode}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($barcode) {
            try {
                // Rate limiting
                sleep(self::RATE_LIMIT_DELAY);

                $headers = [
                    'User-Agent' => $this->userAgent,
                ];

                if ($this->token) {
                    $headers['Authorization'] = "Discogs token={$this->token}";
                }

                $response = Http::timeout(15)
                    ->withHeaders($headers)
                    ->retry(3, 2000)
                    ->get(self::BASE_URL . '/database/search', [
                        'barcode' => $barcode,
                        'type' => 'release',
                        'per_page' => 5
                    ]);

                if (!$response->successful()) {
                    Log::warning("Discogs API failed for barcode {$barcode}", [
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    return null;
                }

                $data = $response->json();
                
                if (!isset($data['results']) || empty($data['results'])) {
                    return null;
                }

                // Get detailed info for the first result
                return $this->getDetailedRelease($data['results'][0]['id']);
                
            } catch (\Exception $e) {
                Log::error("Discogs API error for barcode {$barcode}", [
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    public function searchByCatalogNumber(string $catalogNumber, ?string $label = null): ?array
    {
        $catalogNumber = trim($catalogNumber);
        
        if (empty($catalogNumber)) {
            return null;
        }

        $cacheKey = "discogs_catalog_" . md5($catalogNumber . ($label ?? ''));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($catalogNumber, $label) {
            try {
                // Rate limiting
                sleep(self::RATE_LIMIT_DELAY);

                $headers = [
                    'User-Agent' => $this->userAgent,
                ];

                if ($this->token) {
                    $headers['Authorization'] = "Discogs token={$this->token}";
                }

                $searchParams = [
                    'catno' => $catalogNumber,
                    'type' => 'release',
                    'per_page' => 5
                ];

                if ($label) {
                    $searchParams['label'] = $label;
                }

                $response = Http::timeout(15)
                    ->withHeaders($headers)
                    ->retry(3, 2000)
                    ->get(self::BASE_URL . '/database/search', $searchParams);

                if (!$response->successful()) {
                    Log::warning("Discogs API failed for catalog number {$catalogNumber}", [
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    return null;
                }

                $data = $response->json();
                
                if (!isset($data['results']) || empty($data['results'])) {
                    return null;
                }

                // Get detailed info for the first result
                return $this->getDetailedRelease($data['results'][0]['id']);
                
            } catch (\Exception $e) {
                Log::error("Discogs API error for catalog number {$catalogNumber}", [
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    private function getDetailedRelease(int $releaseId): ?array
    {
        $cacheKey = "discogs_release_{$releaseId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($releaseId) {
            try {
                // Rate limiting
                sleep(self::RATE_LIMIT_DELAY);

                $headers = [
                    'User-Agent' => $this->userAgent,
                ];

                if ($this->token) {
                    $headers['Authorization'] = "Discogs token={$this->token}";
                }

                $response = Http::timeout(15)
                    ->withHeaders($headers)
                    ->retry(3, 2000)
                    ->get(self::BASE_URL . "/releases/{$releaseId}");

                if (!$response->successful()) {
                    return null;
                }

                $release = $response->json();
                return $this->formatReleaseData($release);
                
            } catch (\Exception $e) {
                Log::error("Discogs API error for release {$releaseId}", [
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    private function formatReleaseData(array $release): array
    {
        $contributors = [];
        
        // Add artists
        if (isset($release['artists'])) {
            foreach ($release['artists'] as $artist) {
                $contributors[] = [
                    'name' => $artist['name'],
                    'role' => 'artist'
                ];
            }
        }

        // Add extra artists (featured, remixer, etc.)
        if (isset($release['extraartists'])) {
            foreach ($release['extraartists'] as $artist) {
                $contributors[] = [
                    'name' => $artist['name'],
                    'role' => strtolower($artist['role'] ?? 'contributor')
                ];
            }
        }

        // Get identifiers
        $identifiers = [];
        if (isset($release['identifiers'])) {
            foreach ($release['identifiers'] as $identifier) {
                $type = strtolower($identifier['type'] ?? '');
                
                // Map Discogs identifier types to our enum values
                $mappedType = match($type) {
                    'barcode' => 'upc',
                    'matrix / runout' => 'catalog_no',
                    'catalog#' => 'catalog_no',
                    'label code' => 'catalog_no',
                    default => in_array($type, ['isbn13', 'discogs_release_id', 'upc', 'ean', 'catalog_no']) ? $type : 'catalog_no'
                };
                
                $identifiers[] = [
                    'type' => $mappedType,
                    'value' => $identifier['value'] ?? ''
                ];
            }
        }

        // Add catalog number if available
        if (isset($release['labels'][0]['catno'])) {
            $identifiers[] = [
                'type' => 'catalog_no',
                'value' => $release['labels'][0]['catno']
            ];
        }

        return [
            'title' => $release['title'] ?? '',
            'year' => $release['year'] ?? null,
            'cover_url' => $this->getCoverImage($release),
            'contributors' => $contributors,
            'identifiers' => $identifiers,
            'metadata' => [
                'labels' => $this->getLabels($release),
                'formats' => $this->getFormats($release),
                'genres' => $release['genres'] ?? [],
                'styles' => $release['styles'] ?? [],
                'country' => $release['country'] ?? null,
                'notes' => $release['notes'] ?? null,
                'tracklist' => $this->getTracklist($release)
            ]
        ];
    }

    private function getCoverImage(array $release): ?string
    {
        if (isset($release['images']) && is_array($release['images'])) {
            // Prefer primary images first
            foreach ($release['images'] as $image) {
                if (($image['type'] ?? '') === 'primary') {
                    return $image['uri'] ?? null;
                }
            }
            
            // Fall back to first available image
            return $release['images'][0]['uri'] ?? null;
        }
        
        return null;
    }

    private function getLabels(array $release): array
    {
        if (isset($release['labels']) && is_array($release['labels'])) {
            return array_map(function ($label) {
                return [
                    'name' => $label['name'] ?? '',
                    'catno' => $label['catno'] ?? ''
                ];
            }, $release['labels']);
        }
        
        return [];
    }

    private function getFormats(array $release): array
    {
        if (isset($release['formats']) && is_array($release['formats'])) {
            return array_map(function ($format) {
                return [
                    'name' => $format['name'] ?? '',
                    'qty' => $format['qty'] ?? 1,
                    'descriptions' => $format['descriptions'] ?? []
                ];
            }, $release['formats']);
        }
        
        return [];
    }

    private function getTracklist(array $release): array
    {
        if (isset($release['tracklist']) && is_array($release['tracklist'])) {
            return array_slice(
                array_map(function ($track) {
                    return [
                        'position' => $track['position'] ?? '',
                        'title' => $track['title'] ?? '',
                        'duration' => $track['duration'] ?? ''
                    ];
                }, $release['tracklist']),
                0,
                20 // Limit to first 20 tracks
            );
        }
        
        return [];
    }

    private function cleanBarcode(string $barcode): string
    {
        return preg_replace('/[^0-9]/', '', $barcode);
    }

    private function isValidBarcode(string $barcode): bool
    {
        $length = strlen($barcode);
        return $length >= 8 && $length <= 14; // Common barcode lengths
    }
}