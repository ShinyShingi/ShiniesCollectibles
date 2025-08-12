<?php

namespace App\Services\PriceChecking;

use App\Models\Item;
use App\Models\PriceCheck;
use Illuminate\Support\Facades\Log;

class PriceCheckingManager
{
    protected array $services = [];

    public function __construct()
    {
        $this->services = [
            new AbeBooksService(),
            new ZvabService(),
            new RebuyService(),
            new MedimopsService(),
            new EbayService(),
            new AmazonService(),
            new ThaliaService(),
            new HugendubelService(),
            new WaterstonesService(),
        ];
    }

    /**
     * Check prices for an item across all services
     *
     * @param Item $item
     * @return array
     */
    public function checkAllPrices(Item $item): array
    {
        $allResults = [];

        foreach ($this->services as $service) {
            if (!$service->canHandle($item)) {
                continue;
            }

            try {
                $results = $service->checkPrices($item);
                $allResults = array_merge($allResults, $results);
                
                Log::info("Price check completed for {$service->getSourceName()}", [
                    'item_id' => $item->id,
                    'results_count' => count($results),
                ]);
            } catch (\Exception $e) {
                Log::error("Price check failed for {$service->getSourceName()}", [
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $allResults;
    }

    /**
     * Check prices and save to database
     *
     * @param Item $item
     * @return int Number of price checks saved
     */
    public function checkAndSavePrices(Item $item): int
    {
        $results = $this->checkAllPrices($item);
        $saved = 0;

        foreach ($results as $result) {
            try {
                $result['item_id'] = $item->id;
                PriceCheck::create($result);
                $saved++;
            } catch (\Exception $e) {
                Log::error("Failed to save price check", [
                    'item_id' => $item->id,
                    'source' => $result['source'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $saved;
    }

    /**
     * Get latest price checks for an item
     *
     * @param Item $item
     * @param int $hours How many hours back to look
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLatestPriceChecks(Item $item, int $hours = 24)
    {
        return $item->priceChecks()
            ->where('checked_at', '>=', now()->subHours($hours))
            ->available()
            ->byTotalCost()
            ->get();
    }

    /**
     * Clean up old price checks
     *
     * @param int $days
     * @return int Number of records deleted
     */
    public function cleanupOldPriceChecks(int $days = 30): int
    {
        return PriceCheck::where('checked_at', '<', now()->subDays($days))->delete();
    }
}