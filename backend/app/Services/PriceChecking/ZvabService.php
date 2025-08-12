<?php

namespace App\Services\PriceChecking;

use App\Models\Item;
use Symfony\Component\DomCrawler\Crawler;

class ZvabService extends AbstractPriceCheckingService
{
    public function getSourceName(): string
    {
        return 'zvab';
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
        $params = [
            'kn' => $item->isbn ?: $item->title,
            'sortby' => '1', // Sort by price
        ];

        if ($item->isbn) {
            $params['isbn'] = $item->isbn;
        }

        return 'https://www.zvab.com/servlet/SearchResults?' . http_build_query($params);
    }

    protected function parseResults(string $html, Item $item): array
    {
        $results = [];
        
        try {
            $crawler = new Crawler($html);
            
            // ZVAB result selectors (similar to AbeBooks as they're related)
            $crawler->filter('.cf.result-item')->each(function (Crawler $node) use (&$results, $item) {
                try {
                    $title = $node->filter('.title-author .title')->text('');
                    $author = $node->filter('.title-author .author')->text('');
                    $priceText = $node->filter('.item-price')->text('');
                    $condition = $node->filter('.condition')->text('');
                    $seller = $node->filter('.seller-info .seller-name')->text('');
                    $location = $node->filter('.seller-info .seller-location')->text('');
                    $description = $node->filter('.item-note')->text('');
                    
                    // Extract URL
                    $linkNode = $node->filter('.title-author .title a');
                    $url = $linkNode->count() > 0 ? 'https://www.zvab.com' . $linkNode->attr('href') : '';

                    // Parse price
                    $price = $this->parsePrice($priceText);
                    
                    if ($price && $title) {
                        $results[] = [
                            'source' => $this->getSourceName(),
                            'title' => trim($title),
                            'author' => trim($author),
                            'isbn' => $item->isbn,
                            'price' => $price,
                            'shipping_cost' => 0,
                            'condition' => $this->normalizeCondition($condition),
                            'seller_name' => trim($seller),
                            'seller_location' => trim($location),
                            'description' => trim($description),
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