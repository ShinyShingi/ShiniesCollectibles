<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlists = Auth::user()->wishlists()
            ->withCount('items')
            ->latest()
            ->get();

        return response()->json($wishlists);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();

        $wishlist = Wishlist::create($validated);

        return response()->json($wishlist->load('user'), 201);
    }

    public function show(Wishlist $wishlist)
    {
        $this->authorize('view', $wishlist);

        $wishlist->load([
            'items.identifiers',
            'items.contributors',
            'items.latestPriceChecks' => function ($query) {
                $query->available()->latest('checked_at');
            }
        ]);

        // Add current lowest prices to items
        $wishlist->items->each(function ($item) {
            $lowestPrice = $item->latestPriceChecks->min('total_cost');
            $item->current_lowest_price = $lowestPrice;
        });

        return response()->json($wishlist);
    }

    public function update(Request $request, Wishlist $wishlist)
    {
        $this->authorize('update', $wishlist);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        $wishlist->update($validated);

        return response()->json($wishlist);
    }

    public function destroy(Wishlist $wishlist)
    {
        $this->authorize('delete', $wishlist);

        $wishlist->delete();

        return response()->json(['message' => 'Wishlist deleted successfully']);
    }

    public function addItem(Request $request, Wishlist $wishlist)
    {
        $this->authorize('update', $wishlist);

        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'target_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'priority' => 'integer|between:1,5',
        ]);

        $validated['wishlist_id'] = $wishlist->id;

        // Check if item is already in wishlist
        $existingItem = WishlistItem::where('wishlist_id', $wishlist->id)
            ->where('item_id', $validated['item_id'])
            ->first();

        if ($existingItem) {
            return response()->json(['error' => 'Item already in wishlist'], 409);
        }

        $wishlistItem = WishlistItem::create($validated);

        return response()->json($wishlistItem->load('item'), 201);
    }

    public function removeItem(Request $request, Wishlist $wishlist)
    {
        $this->authorize('update', $wishlist);

        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
        ]);

        $deleted = WishlistItem::where('wishlist_id', $wishlist->id)
            ->where('item_id', $validated['item_id'])
            ->delete();

        if (!$deleted) {
            return response()->json(['error' => 'Item not found in wishlist'], 404);
        }

        return response()->json(['message' => 'Item removed from wishlist']);
    }

    public function updateItem(Request $request, Wishlist $wishlist)
    {
        $this->authorize('update', $wishlist);

        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'target_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'priority' => 'integer|between:1,5',
        ]);

        $wishlistItem = WishlistItem::where('wishlist_id', $wishlist->id)
            ->where('item_id', $validated['item_id'])
            ->first();

        if (!$wishlistItem) {
            return response()->json(['error' => 'Item not found in wishlist'], 404);
        }

        unset($validated['item_id']);
        $wishlistItem->update($validated);

        return response()->json($wishlistItem->load('item'));
    }

    public function togglePublic(Wishlist $wishlist)
    {
        $this->authorize('update', $wishlist);

        $wishlist->togglePublic();

        return response()->json([
            'is_public' => $wishlist->is_public,
            'share_token' => $wishlist->share_token,
            'public_url' => $wishlist->public_url,
        ]);
    }

    public function public($shareToken)
    {
        $wishlist = Wishlist::where('share_token', $shareToken)
            ->where('is_public', true)
            ->firstOrFail();

        $wishlist->load([
            'user:id,name',
            'items.identifiers',
            'items.contributors',
            'items.latestPriceChecks' => function ($query) {
                $query->available()->latest('checked_at');
            }
        ]);

        // Add current lowest prices to items
        $wishlist->items->each(function ($item) {
            $lowestPrice = $item->latestPriceChecks->min('total_cost');
            $item->current_lowest_price = $lowestPrice;
            
            // Add best price source
            $bestPriceCheck = $item->latestPriceChecks->firstWhere('total_cost', $lowestPrice);
            $item->best_price_source = $bestPriceCheck ? $bestPriceCheck->source : null;
        });

        return response()->json($wishlist);
    }
}