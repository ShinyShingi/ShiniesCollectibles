<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\CheckItemPricesJob;
use App\Models\Item;
use App\Services\PriceChecking\PriceCheckingManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceCheckController extends Controller
{
    public function __construct(
        private PriceCheckingManager $priceManager
    ) {}

    /**
     * Get latest price checks for an item
     */
    public function index(Item $item): JsonResponse
    {
        $this->authorize('view', $item);

        $priceChecks = $this->priceManager->getLatestPriceChecks($item, 24);
        
        return response()->json([
            'data' => $priceChecks->groupBy('source')->map(function ($checks) {
                return $checks->sortBy('total_cost')->values();
            }),
            'last_updated' => $priceChecks->max('checked_at'),
            'total_offers' => $priceChecks->count(),
        ]);
    }

    /**
     * Trigger a manual price check for an item
     */
    public function refresh(Item $item): JsonResponse
    {
        $this->authorize('view', $item);

        // Only allow books for now
        if ($item->media_type !== 'book') {
            return response()->json([
                'message' => 'Price checking is only available for books',
            ], 422);
        }

        // Check if item has required data
        if (empty($item->isbn) && empty($item->title)) {
            return response()->json([
                'message' => 'Item must have either an ISBN or title for price checking',
            ], 422);
        }

        // Dispatch the job with force refresh
        CheckItemPricesJob::dispatch($item, true);

        return response()->json([
            'message' => 'Price check started. Results will be available shortly.',
            'status' => 'queued',
        ]);
    }

    /**
     * Get price check history for an item
     */
    public function history(Item $item, Request $request): JsonResponse
    {
        $this->authorize('view', $item);

        $days = $request->integer('days', 30);
        
        $history = $item->priceChecks()
            ->where('checked_at', '>=', now()->subDays($days))
            ->available()
            ->with([])
            ->orderBy('checked_at', 'desc')
            ->paginate(50);

        return response()->json([
            'data' => $history->items(),
            'pagination' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    /**
     * Get price statistics for an item
     */
    public function statistics(Item $item): JsonResponse
    {
        $this->authorize('view', $item);

        $recentChecks = $item->priceChecks()
            ->where('checked_at', '>=', now()->subDays(30))
            ->available()
            ->get();

        if ($recentChecks->isEmpty()) {
            return response()->json([
                'message' => 'No price data available',
            ], 404);
        }

        $statistics = [
            'lowest_price' => $recentChecks->min('total_cost'),
            'highest_price' => $recentChecks->max('total_cost'),
            'average_price' => $recentChecks->avg('total_cost'),
            'median_price' => $recentChecks->sortBy('total_cost')->skip(intval($recentChecks->count() / 2))->first()?->total_cost,
            'offers_count' => $recentChecks->count(),
            'sources_count' => $recentChecks->pluck('source')->unique()->count(),
            'last_updated' => $recentChecks->max('checked_at'),
        ];

        return response()->json([
            'data' => $statistics,
        ]);
    }
}
