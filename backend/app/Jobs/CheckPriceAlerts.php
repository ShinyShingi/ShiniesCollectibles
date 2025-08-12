<?php

namespace App\Jobs;

use App\Jobs\SendPriceAlertEmail;
use App\Models\PriceAlert;
use App\Models\PriceCheck;
use App\Models\WishlistItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckPriceAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('Checking price alerts...');

        // Get all wishlist items with target prices
        $wishlistItems = WishlistItem::withTargetPrice()
            ->with(['item.latestPriceChecks', 'wishlist.user'])
            ->get();

        $alertsTriggered = 0;

        foreach ($wishlistItems as $wishlistItem) {
            $item = $wishlistItem->item;
            $user = $wishlistItem->wishlist->user;
            $targetPrice = $wishlistItem->target_price;

            // Get the latest available price checks for this item
            $latestPriceChecks = $item->latestPriceChecks()
                ->available()
                ->where('total_cost', '<=', $targetPrice)
                ->get();

            foreach ($latestPriceChecks as $priceCheck) {
                // Check if we already have an alert for this price check
                $existingAlert = PriceAlert::where('user_id', $user->id)
                    ->where('item_id', $item->id)
                    ->where('price_check_id', $priceCheck->id)
                    ->first();

                if (!$existingAlert) {
                    // Create new price alert
                    $alert = PriceAlert::create([
                        'user_id' => $user->id,
                        'item_id' => $item->id,
                        'price_check_id' => $priceCheck->id,
                        'target_price' => $targetPrice,
                        'triggered_price' => $priceCheck->total_cost,
                        'source' => $priceCheck->source,
                        'triggered_at' => now(),
                    ]);

                    $alertsTriggered++;

                    Log::info("Price alert triggered for user {$user->id}, item {$item->id}, price {$priceCheck->total_cost}");

                    // Dispatch email notification job
                    SendPriceAlertEmail::dispatch($alert);
                }
            }
        }

        Log::info("Price alert check complete. {$alertsTriggered} alerts triggered.");
    }
}