<?php

namespace App\Services\PriceChecking;

use App\Models\Item;
use Symfony\Component\DomCrawler\Crawler;

class HugendubelService extends AbstractPriceCheckingService
{
    public function getSourceName(): string
    {
        return 'hugendubel';
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
        return 'https://www.hugendubel.de/de/shopsearch?searchKeyword=' . urlencode($searchTerm);
    }

    protected function parseResults(string $html, Item $item): array
    {
        $results = [];
        
        try {
            $crawler = new Crawler($html);
            
            // Hugendubel product listings
            $crawler->filter('.product-tile')->each(function (Crawler $node) use (&$results, $item) {
                try {
                    $title = $node->filter('.product-tile__title a')->text('');
                    $author = $node->filter('.product-tile__author')->text('');
                    $priceText = $node->filter('.product-tile__price .price')->text('');
                    
                    // Extract URL
                    $linkNode = $node->filter('.product-tile__title a');
                    $url = $linkNode->count() > 0 ? 'https://www.hugendubel.de' . $linkNode->attr('href') : '';

                    // Parse price
                    $price = $this->parsePrice($priceText);
                    
                    if ($price && $title) {
                        $results[] = [
                            'source' => $this->getSourceName(),
                            'title' => trim($title),
                            'author' => trim($author),
                            'isbn' => $item->isbn,
                            'price' => $price,
                            'shipping_cost' => 0, // Hugendubel often has free shipping
                            'condition' => 'new',
                            'seller_name' => 'Hugendubel',
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