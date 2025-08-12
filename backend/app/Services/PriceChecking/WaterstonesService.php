<?php

namespace App\Services\PriceChecking;

use App\Models\Item;
use Symfony\Component\DomCrawler\Crawler;

class WaterstonesService extends AbstractPriceCheckingService
{
    public function getSourceName(): string
    {
        return 'waterstones';
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
        return 'https://www.waterstones.com/books/search/term/' . urlencode($searchTerm);
    }

    protected function parseResults(string $html, Item $item): array
    {
        $results = [];
        
        try {
            $crawler = new Crawler($html);
            
            // Waterstones product listings
            $crawler->filter('.book-item')->each(function (Crawler $node) use (&$results, $item) {
                try {
                    $title = $node->filter('.title a')->text('');
                    $author = $node->filter('.author')->text('');
                    $priceText = $node->filter('.price')->text('');
                    
                    // Extract URL
                    $linkNode = $node->filter('.title a');
                    $url = $linkNode->count() > 0 ? 'https://www.waterstones.com' . $linkNode->attr('href') : '';

                    // Parse price and convert from GBP to EUR (approximate)
                    $priceGBP = $this->parsePrice($priceText);
                    $price = $priceGBP ? $priceGBP * 1.17 : null; // Rough GBP to EUR conversion
                    
                    if ($price && $title) {
                        $results[] = [
                            'source' => $this->getSourceName(),
                            'title' => trim($title),
                            'author' => trim($author),
                            'isbn' => $item->isbn,
                            'price' => $price,
                            'shipping_cost' => 5.99, // International shipping to EU
                            'condition' => 'new',
                            'seller_name' => 'Waterstones',
                            'seller_location' => 'United Kingdom',
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