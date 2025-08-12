<?php

namespace App\Services\PriceChecking;

use App\Models\Item;
use Symfony\Component\DomCrawler\Crawler;

class AmazonService extends AbstractPriceCheckingService
{
    protected array $headers = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Connection' => 'keep-alive',
        'Upgrade-Insecure-Requests' => '1',
    ];

    public function getSourceName(): string
    {
        return 'amazon';
    }

    public function checkPrices(Item $item): array
    {
        if (!$this->canHandle($item)) {
            return [];
        }

        $url = $this->buildSearchUrl($item);
        $html = $this->makeRequest($url);

        if (!$html) {
            return [];
        }

        return $this->parseResults($html, $item);
    }

    protected function buildSearchUrl(Item $item): string
    {
        $searchTerm = $item->isbn ?: $item->title;
        $params = [
            'k' => $searchTerm,
            'i' => 'stripbooks',
            's' => 'price-asc-rank',
        ];

        return 'https://www.amazon.de/s?' . http_build_query($params);
    }

    protected function parseResults(string $html, Item $item): array
    {
        $results = [];
        
        try {
            $crawler = new Crawler($html);
            
            // Amazon search results
            $crawler->filter('[data-component-type="s-search-result"]')->each(function (Crawler $node) use (&$results, $item) {
                try {
                    $title = $node->filter('h2 a span')->text('');
                    $priceText = $node->filter('.a-price .a-offscreen')->text('');
                    $author = $node->filter('.a-color-secondary .a-link-normal')->text('');
                    
                    // Extract URL
                    $linkNode = $node->filter('h2 a');
                    $url = $linkNode->count() > 0 ? 'https://www.amazon.de' . $linkNode->attr('href') : '';

                    // Parse price
                    $price = $this->parsePrice($priceText);
                    
                    if ($price && $title) {
                        $results[] = [
                            'source' => $this->getSourceName(),
                            'title' => trim($title),
                            'author' => trim($author),
                            'isbn' => $item->isbn,
                            'price' => $price,
                            'shipping_cost' => 0, // Amazon Prime/free shipping
                            'condition' => 'new',
                            'seller_name' => 'Amazon',
                            'seller_location' => 'Germany',
                            'description' => '',
                            'url' => $url,
                            'currency' => 'EUR',
                            'is_available' => true,
                            'checked_at' => now(),
                        ];
                    }
                } catch (\Exception $e) {
                    // Skip this result if parsing fails
                }
            });
        } catch (\Exception $e) {
            // Log but don't throw - return empty results
        }

        return array_slice($results, 0, 5); // Limit Amazon results to avoid overwhelming
    }
}