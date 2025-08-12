<?php

namespace App\Services\PriceChecking;

use App\Models\Item;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractPriceCheckingService implements PriceCheckingServiceInterface
{
    protected int $timeout = 30;
    protected int $retries = 3;
    protected array $headers = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language' => 'en-US,en;q=0.5',
        'Accept-Encoding' => 'gzip, deflate',
        'Connection' => 'keep-alive',
    ];

    public function canHandle(Item $item): bool
    {
        return $item->media_type === 'book' && !empty($item->isbn);
    }

    protected function makeRequest(string $url, array $params = []): ?string
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout($this->timeout)
                ->retry($this->retries, 1000)
                ->get($url, $params);

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning("Failed to fetch price data from {$this->getSourceName()}", [
                'url' => $url,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Error fetching price data from {$this->getSourceName()}", [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function parsePrice(string $priceString): ?float
    {
        // Remove currency symbols and normalize
        $price = preg_replace('/[^\d.,]/', '', $priceString);
        $price = str_replace(',', '.', $price);
        
        if (is_numeric($price)) {
            return (float) $price;
        }

        return null;
    }

    protected function normalizeCondition(string $condition): string
    {
        $condition = strtolower(trim($condition));
        
        $conditionMap = [
            'new' => 'mint',
            'like new' => 'near_mint',
            'very fine' => 'excellent',
            'fine' => 'very_good',
            'good' => 'good',
            'fair' => 'fair',
            'poor' => 'poor',
        ];

        return $conditionMap[$condition] ?? 'good';
    }

    abstract protected function buildSearchUrl(Item $item): string;
    abstract protected function parseResults(string $html, Item $item): array;
}