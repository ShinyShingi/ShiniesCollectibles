<?php

namespace App\Services\PriceChecking;

use App\Models\Item;
use Symfony\Component\DomCrawler\Crawler;

class RebuyService extends AbstractPriceCheckingService
{
    public function getSourceName(): string
    {
        return 'rebuy';
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
        return 'https://www.rebuy.de/kaufen/search?q=' . urlencode($searchTerm);
    }

    protected function parseResults(string $html, Item $item): array
    {
        $results = [];
        
        try {
            $crawler = new Crawler($html);
            
            // Rebuy result selectors (these are hypothetical and need to be updated based on actual HTML)
            $crawler->filter('.product-item')->each(function (Crawler $node) use (&$results, $item) {
                try {
                    $title = $node->filter('.product-title')->text('');
                    $priceText = $node->filter('.product-price')->text('');
                    $condition = $node->filter('.product-condition')->text('');
                    
                    // Extract URL
                    $linkNode = $node->filter('.product-title a');
                    $url = $linkNode->count() > 0 ? 'https://www.rebuy.de' . $linkNode->attr('href') : '';

                    // Parse price
                    $price = $this->parsePrice($priceText);
                    
                    if ($price && $title) {
                        $results[] = [
                            'source' => $this->getSourceName(),
                            'title' => trim($title),
                            'author' => '',
                            'isbn' => $item->isbn,
                            'price' => $price,
                            'shipping_cost' => 1.99, // Rebuy typically has fixed shipping
                            'condition' => $this->normalizeCondition($condition),
                            'seller_name' => 'Rebuy',
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

        return array_slice($results, 0, 10);
    }
}