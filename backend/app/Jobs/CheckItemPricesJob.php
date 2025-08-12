<?php

namespace App\Jobs;

use App\Models\Item;
use App\Services\PriceChecking\PriceCheckingManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckItemPricesJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Item $item,
        public bool $forceRefresh = false
    ) {
        $this->onQueue('price-checking');
    }

    /**
     * Execute the job.
     */
    public function handle(PriceCheckingManager $priceManager): void
    {
        Log::info('Starting price check job', [
            'item_id' => $this->item->id,
            'title' => $this->item->title,
            'force_refresh' => $this->forceRefresh,
        ]);

        // Skip if we already have recent data and it's not a forced refresh
        if (!$this->forceRefresh) {
            $recentChecks = $priceManager->getLatestPriceChecks($this->item, 6); // 6 hours
            if ($recentChecks->isNotEmpty()) {
                Log::info('Skipping price check - recent data available', [
                    'item_id' => $this->item->id,
                    'recent_checks' => $recentChecks->count(),
                ]);
                return;
            }
        }

        try {
            $savedCount = $priceManager->checkAndSavePrices($this->item);
            
            Log::info('Price check job completed', [
                'item_id' => $this->item->id,
                'saved_prices' => $savedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Price check job failed', [
                'item_id' => $this->item->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Price check job failed permanently', [
            'item_id' => $this->item->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
