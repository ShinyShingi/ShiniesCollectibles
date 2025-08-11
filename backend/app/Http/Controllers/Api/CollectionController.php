<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CollectionController extends Controller
{
    /**
     * Display a listing of the user's collections.
     */
    public function index()
    {
        $collections = Collection::with('items')
            ->where('user_id', Auth::id())
            ->orderBy('name')
            ->get()
            ->map(function ($collection) {
                return [
                    'id' => $collection->id,
                    'name' => $collection->name,
                    'description' => $collection->description,
                    'color' => $collection->color,
                    'is_public' => $collection->is_public,
                    'items_count' => $collection->items->count(),
                    'created_at' => $collection->created_at,
                    'updated_at' => $collection->updated_at,
                ];
            });

        return response()->json($collections);
    }

    /**
     * Store a newly created collection.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('collections', 'name')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                })
            ],
            'description' => 'nullable|string',
            'color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'is_public' => 'boolean'
        ]);

        $collection = Collection::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? '#3B82F6',
            'is_public' => $validated['is_public'] ?? false,
        ]);

        return response()->json([
            'id' => $collection->id,
            'name' => $collection->name,
            'description' => $collection->description,
            'color' => $collection->color,
            'is_public' => $collection->is_public,
            'items_count' => 0,
            'created_at' => $collection->created_at,
            'updated_at' => $collection->updated_at,
        ], 201);
    }

    /**
     * Display the specified collection.
     */
    public function show($id)
    {
        $collection = Collection::with(['items.identifiers', 'items.contributors', 'items.tags'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'id' => $collection->id,
            'name' => $collection->name,
            'description' => $collection->description,
            'color' => $collection->color,
            'is_public' => $collection->is_public,
            'items' => $collection->items,
            'items_count' => $collection->items->count(),
            'created_at' => $collection->created_at,
            'updated_at' => $collection->updated_at,
        ]);
    }

    /**
     * Update the specified collection.
     */
    public function update(Request $request, $id)
    {
        $collection = Collection::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('collections', 'name')
                    ->where(function ($query) {
                        return $query->where('user_id', Auth::id());
                    })
                    ->ignore($collection->id)
            ],
            'description' => 'nullable|string',
            'color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'is_public' => 'boolean'
        ]);

        $collection->update($validated);

        return response()->json([
            'id' => $collection->id,
            'name' => $collection->name,
            'description' => $collection->description,
            'color' => $collection->color,
            'is_public' => $collection->is_public,
            'items_count' => $collection->items()->count(),
            'created_at' => $collection->created_at,
            'updated_at' => $collection->updated_at,
        ]);
    }

    /**
     * Remove the specified collection.
     */
    public function destroy($id)
    {
        $collection = Collection::where('user_id', Auth::id())->findOrFail($id);
        $collection->delete();

        return response()->json(['message' => 'Collection deleted successfully']);
    }

    /**
     * Add items to a collection
     */
    public function addItems(Request $request, $id)
    {
        $collection = Collection::where('user_id', Auth::id())->findOrFail($id);
        
        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:items,id'
        ]);

        // Only add items that belong to the user and aren't already in the collection
        $userItemIds = auth()->user()->items()->pluck('id');
        $itemsToAdd = collect($validated['item_ids'])
            ->intersect($userItemIds)
            ->diff($collection->items()->pluck('items.id'));

        $collection->items()->attach($itemsToAdd);

        return response()->json([
            'message' => 'Items added to collection successfully',
            'added_count' => $itemsToAdd->count()
        ]);
    }

    /**
     * Remove items from a collection
     */
    public function removeItems(Request $request, $id)
    {
        $collection = Collection::where('user_id', Auth::id())->findOrFail($id);
        
        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer'
        ]);

        $collection->items()->detach($validated['item_ids']);

        return response()->json([
            'message' => 'Items removed from collection successfully'
        ]);
    }
}
