<?php

namespace App\Services\PriceChecking;

use App\Models\Item;
use Symfony\Component\DomCrawler\Crawler;

class EbayService extends AbstractPriceCheckingService
{
    public function getSourceName(): string
    {
        return 'ebay';
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
            '_nkw' => $searchTerm,
            '_sacat' => '267', // Books category
            'LH_BIN' => '1', // Buy It Now only
            '_sop' => '15', // Sort by price + shipping
        ];

        return 'https://www.ebay.de/sch/i.html?' . http_build_query($params);
    }

    protected function parseResults(string $html, Item $item): array
    {
        $results = [];
        
        try {
            $crawler = new Crawler($html);
            
            // eBay search results
            $crawler->filter('.s-item')->each(function (Crawler $node) use (&$results, $item) {
                try {
                    $title = $node->filter('.s-item__title')->text('');
                    $priceText = $node->filter('.s-item__price')->text('');
                    $shippingText = $node->filter('.s-item__shipping')->text('');
                    $condition = $node->filter('.s-item__subtitle')->text('');
                    
                    // Extract URL
                    $linkNode = $node->filter('.s-item__link');
                    $url = $linkNode->count() > 0 ? $linkNode->attr('href') : '';

                    // Parse price and shipping
                    $price = $this->parsePrice($priceText);
                    $shipping = $this->parseShipping($shippingText);
                    
                    if ($price && $title && !str_contains(strtolower($title), 'sponsored')) {
                        $results[] = [
                            'source' => $this->getSourceName(),
                            'title' => trim($title),
                            'author' => '',
                            'isbn' => $item->isbn,
                            'price' => $price,
                            'shipping_cost' => $shipping,
                            'condition' => $this->normalizeCondition($condition),
                            'seller_name' => '',
                            'seller_location' => '',
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

    private function parseShipping(string $shippingText): float
    {
        if (str_contains(strtolower($shippingText), 'kostenloser versand') || 
            str_contains(strtolower($shippingText), 'free shipping')) {
            return 0;
        }

        // Extract shipping cost
        $shipping = $this->parsePrice($shippingText);
        return $shipping ?? 3.99; // Default shipping if not found
    }
}