<?php

namespace App\Services\PriceChecking;

use App\Models\Item;
use Symfony\Component\DomCrawler\Crawler;

class MedimopsService extends AbstractPriceCheckingService
{
    public function getSourceName(): string
    {
        return 'medimops';
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
        return 'https://www.medimops.de/search/?fcIsSearch=1&searchparam=' . urlencode($searchTerm);
    }

    protected function parseResults(string $html, Item $item): array
    {
        $results = [];
        
        try {
            $crawler = new Crawler($html);
            
            // Medimops product listings
            $crawler->filter('.mm-product-item')->each(function (Crawler $node) use (&$results, $item) {
                try {
                    $title = $node->filter('.mm-product-title a')->text('');
                    $priceText = $node->filter('.mm-product-price .price')->text('');
                    $condition = $node->filter('.mm-product-condition')->text('');
                    
                    // Extract URL
                    $linkNode = $node->filter('.mm-product-title a');
                    $url = $linkNode->count() > 0 ? 'https://www.medimops.de' . $linkNode->attr('href') : '';

                    // Parse price
                    $price = $this->parsePrice($priceText);
                    
                    if ($price && $title) {
                        $results[] = [
                            'source' => $this->getSourceName(),
                            'title' => trim($title),
                            'author' => '',
                            'isbn' => $item->isbn,
                            'price' => $price,
                            'shipping_cost' => 0, // Medimops often has free shipping
                            'condition' => $this->normalizeCondition($condition),
                            'seller_name' => 'Medimops',
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