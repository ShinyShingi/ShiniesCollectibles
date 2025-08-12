<?php

namespace App\Console\Commands;

use App\Jobs\CheckItemPricesJob;
use App\Models\Item;
use App\Services\PriceChecking\PriceCheckingManager;
use Illuminate\Console\Command;

class ScheduledPriceCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'price:check-scheduled 
                           {--limit=50 : Maximum number of items to check}
                           {--force : Force check even if recent data exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check prices for books that need updating';

    /**
     * Execute the console command.
     */
    public function handle(PriceCheckingManager $priceManager)
    {
        $limit = $this->option('limit');
        $force = $this->option('force');

        $this->info("Starting scheduled price check (limit: {$limit}, force: " . ($force ? 'yes' : 'no') . ')');

        // Get books that need price checking
        $query = Item::books()
            ->whereHas('identifiers', function ($query) {
                $query->where('type', 'isbn');
            });

        if (!$force) {
            // Only check items that don't have recent price data (older than 6 hours)
            $query->whereDoesntHave('priceChecks', function ($query) {
                $query->where('checked_at', '>=', now()->subHours(6));
            });
        }

        $items = $query->take($limit)->get();

        if ($items->isEmpty()) {
            $this->info('No items need price checking');
            return 0;
        }

        $this->info("Found {$items->count()} items to check");

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        $queued = 0;
        foreach ($items as $item) {
            try {
                CheckItemPricesJob::dispatch($item, $force);
                $queued++;
            } catch (\Exception $e) {
                $this->error("Failed to queue price check for item {$item->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Queued {$queued} price check jobs");

        // Clean up old price checks
        $deleted = $priceManager->cleanupOldPriceChecks(30);
        if ($deleted > 0) {
            $this->info("Cleaned up {$deleted} old price checks");
        }

        return 0;
    }
}
