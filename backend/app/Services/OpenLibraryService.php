<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OpenLibraryService
{
    private const BASE_URL = 'https://openlibrary.org';
    private const COVERS_URL = 'https://covers.openlibrary.org/b';
    private const CACHE_TTL = 3600; // 1 hour

    public function searchByIsbn(string $isbn): ?array
    {
        $isbn = $this->cleanIsbn($isbn);
        
        if (!$this->isValidIsbn($isbn)) {
            return null;
        }

        $cacheKey = "openlibrary_isbn_{$isbn}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($isbn) {
            try {
                $response = Http::timeout(10)
                    ->retry(3, 1000)
                    ->get(self::BASE_URL . "/api/books", [
                        'bibkeys' => "ISBN:{$isbn}",
                        'format' => 'json',
                        'jscmd' => 'data'
                    ]);

                if (!$response->successful()) {
                    Log::warning("OpenLibrary API failed for ISBN {$isbn}", [
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    return null;
                }

                $data = $response->json();
                $key = "ISBN:{$isbn}";
                
                if (!isset($data[$key])) {
                    return null;
                }

                return $this->formatBookData($data[$key], $isbn);
            } catch (\Exception $e) {
                Log::error("OpenLibrary API error for ISBN {$isbn}", [
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    private function formatBookData(array $bookData, string $isbn): array
    {
        $authors = [];
        if (isset($bookData['authors'])) {
            foreach ($bookData['authors'] as $author) {
                $authors[] = [
                    'name' => $author['name'] ?? '',
                    'role' => 'author'
                ];
            }
        }

        // Get cover image URLs
        $coverUrl = $this->getCoverUrl($isbn, $bookData);

        return [
            'title' => $bookData['title'] ?? '',
            'subtitle' => $bookData['subtitle'] ?? null,
            'year' => $this->extractYear($bookData),
            'cover_url' => $coverUrl,
            'contributors' => $authors,
            'identifiers' => [
                [
                    'type' => 'isbn13',
                    'value' => $isbn
                ]
            ],
            'metadata' => [
                'publisher' => $this->getPublishers($bookData),
                'page_count' => $bookData['number_of_pages'] ?? null,
                'subjects' => $this->getSubjects($bookData),
                'description' => $this->getDescription($bookData),
                'language' => $this->getLanguages($bookData)
            ]
        ];
    }

    private function getCoverUrl(string $isbn, array $bookData): ?string
    {
        // Try different cover sources
        $coverSources = [
            self::COVERS_URL . "/isbn/{$isbn}-L.jpg",
            self::COVERS_URL . "/isbn/{$isbn}-M.jpg"
        ];

        // Check if cover exists in book data
        if (isset($bookData['cover']['large'])) {
            array_unshift($coverSources, $bookData['cover']['large']);
        }
        if (isset($bookData['cover']['medium'])) {
            array_unshift($coverSources, $bookData['cover']['medium']);
        }

        foreach ($coverSources as $url) {
            if ($this->urlExists($url)) {
                return $url;
            }
        }

        return null;
    }

    private function urlExists(string $url): bool
    {
        try {
            $response = Http::timeout(5)->head($url);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function extractYear(array $bookData): ?int
    {
        if (isset($bookData['publish_date'])) {
            preg_match('/(\d{4})/', $bookData['publish_date'], $matches);
            return isset($matches[1]) ? (int) $matches[1] : null;
        }
        return null;
    }

    private function getPublishers(array $bookData): ?string
    {
        if (isset($bookData['publishers']) && is_array($bookData['publishers'])) {
            return implode(', ', array_column($bookData['publishers'], 'name'));
        }
        return null;
    }

    private function getSubjects(array $bookData): array
    {
        if (isset($bookData['subjects']) && is_array($bookData['subjects'])) {
            return array_slice(array_column($bookData['subjects'], 'name'), 0, 10);
        }
        return [];
    }

    private function getDescription(array $bookData): ?string
    {
        if (isset($bookData['excerpts']) && is_array($bookData['excerpts'])) {
            return $bookData['excerpts'][0]['text'] ?? null;
        }
        return null;
    }

    private function getLanguages(array $bookData): ?string
    {
        if (isset($bookData['languages']) && is_array($bookData['languages'])) {
            return implode(', ', array_column($bookData['languages'], 'name'));
        }
        return null;
    }

    private function cleanIsbn(string $isbn): string
    {
        return preg_replace('/[^0-9X]/', '', strtoupper($isbn));
    }

    private function isValidIsbn(string $isbn): bool
    {
        $length = strlen($isbn);
        return $length === 10 || $length === 13;
    }
}