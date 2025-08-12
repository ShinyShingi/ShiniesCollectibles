<?php

namespace App\Services\PriceChecking;

use App\Models\Item;
use Symfony\Component\DomCrawler\Crawler;

class ThaliaService extends AbstractPriceCheckingService
{
    public function getSourceName(): string
    {
        return 'thalia';
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
            'sq' => $searchTerm,
            'sswg' => 'ANY',
            'timestamp' => time(),
        ];

        return 'https://www.thalia.de/shop/home/suchartikel/?' . http_build_query($params);
    }

    protected function parseResults(string $html, Item $item): array
    {
        $results = [];
        
        try {
            $crawler = new Crawler($html);
            
            // Thalia product listings
            $crawler->filter('.element-list-item')->each(function (Crawler $node) use (&$results, $item) {
                try {
                    $title = $node->filter('.element-link-text')->text('');
                    $author = $node->filter('.element-author')->text('');
                    $priceText = $node->filter('.current-price')->text('');
                    
                    // Extract URL
                    $linkNode = $node->filter('.element-link');
                    $url = $linkNode->count() > 0 ? 'https://www.thalia.de' . $linkNode->attr('href') : '';

                    // Parse price
                    $price = $this->parsePrice($priceText);
                    
                    if ($price && $title) {
                        $results[] = [
                            'source' => $this->getSourceName(),
                            'title' => trim($title),
                            'author' => trim($author),
                            'isbn' => $item->isbn,
                            'price' => $price,
                            'shipping_cost' => 0, // Thalia often has free shipping
                            'condition' => 'new',
                            'seller_name' => 'Thalia',
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