<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Contributor;
use App\Models\Tag;
use App\Services\OpenLibraryService;
use App\Services\DiscogsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::with(['identifiers', 'contributors', 'tags'])
            ->where('user_id', Auth::id());

        if ($request->has('media_type')) {
            $query->where('media_type', $request->media_type);
        }

        if ($request->has('owned')) {
            $query->where('owned', $request->boolean('owned'));
        }

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $items = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'media_type' => 'required|in:book,music',
            'title' => 'required|string|max:255',
            'year' => 'nullable|integer|min:1000|max:' . (date('Y') + 10),
            'cover_url' => 'nullable|url',
            'owned' => 'boolean',
            'condition' => 'nullable|in:mint,near_mint,excellent,very_good,good,fair,poor',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'identifiers' => 'nullable|array',
            'identifiers.*.type' => 'required_with:identifiers|in:isbn13,discogs_release_id,upc,ean,catalog_no',
            'identifiers.*.value' => 'required_with:identifiers|string',
            'contributors' => 'nullable|array',
            'contributors.*.name' => 'required_with:contributors|string',
            'contributors.*.role' => 'required_with:contributors|in:author,artist,label,publisher,producer,composer,performer',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        DB::beginTransaction();
        try {
            $item = Item::create([
                'user_id' => Auth::id(),
                'media_type' => $validated['media_type'],
                'title' => $validated['title'],
                'year' => $validated['year'] ?? null,
                'cover_url' => $validated['cover_url'] ?? null,
                'owned' => $validated['owned'] ?? true,
                'condition' => $validated['condition'] ?? null,
                'purchase_price' => $validated['purchase_price'] ?? null,
                'purchase_date' => $validated['purchase_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Add identifiers
            if (isset($validated['identifiers'])) {
                foreach ($validated['identifiers'] as $identifier) {
                    $item->identifiers()->create($identifier);
                }
            }

            // Add contributors
            if (isset($validated['contributors'])) {
                foreach ($validated['contributors'] as $contributorData) {
                    $contributor = Contributor::firstOrCreate(
                        ['name' => $contributorData['name']],
                        ['name' => $contributorData['name']]
                    );
                    
                    $item->contributors()->attach($contributor->id, [
                        'role' => $contributorData['role']
                    ]);
                }
            }

            // Add tags
            if (isset($validated['tags'])) {
                foreach ($validated['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['name' => $tagName],
                        ['name' => $tagName]
                    );
                    $item->tags()->attach($tag->id);
                }
            }

            DB::commit();

            $item->load(['identifiers', 'contributors', 'tags']);
            return response()->json($item, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create item'], 500);
        }
    }

    public function show($id)
    {
        $item = Item::with(['identifiers', 'contributors', 'tags', 'collections'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = Item::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'media_type' => 'required|in:book,music',
            'title' => 'required|string|max:255',
            'year' => 'nullable|integer|min:1000|max:' . (date('Y') + 10),
            'cover_url' => 'nullable|url',
            'owned' => 'boolean',
            'condition' => 'nullable|in:mint,near_mint,excellent,very_good,good,fair,poor',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'identifiers' => 'nullable|array',
            'identifiers.*.type' => 'required_with:identifiers|in:isbn13,discogs_release_id,upc,ean,catalog_no',
            'identifiers.*.value' => 'required_with:identifiers|string',
            'contributors' => 'nullable|array',
            'contributors.*.name' => 'required_with:contributors|string',
            'contributors.*.role' => 'required_with:contributors|in:author,artist,label,publisher,producer,composer,performer',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        DB::beginTransaction();
        try {
            $item->update([
                'media_type' => $validated['media_type'],
                'title' => $validated['title'],
                'year' => $validated['year'] ?? null,
                'cover_url' => $validated['cover_url'] ?? null,
                'owned' => $validated['owned'] ?? true,
                'condition' => $validated['condition'] ?? null,
                'purchase_price' => $validated['purchase_price'] ?? null,
                'purchase_date' => $validated['purchase_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update identifiers
            $item->identifiers()->delete();
            if (isset($validated['identifiers'])) {
                foreach ($validated['identifiers'] as $identifier) {
                    $item->identifiers()->create($identifier);
                }
            }

            // Update contributors
            $item->contributors()->detach();
            if (isset($validated['contributors'])) {
                foreach ($validated['contributors'] as $contributorData) {
                    $contributor = Contributor::firstOrCreate(
                        ['name' => $contributorData['name']],
                        ['name' => $contributorData['name']]
                    );
                    
                    $item->contributors()->attach($contributor->id, [
                        'role' => $contributorData['role']
                    ]);
                }
            }

            // Update tags
            $item->tags()->detach();
            if (isset($validated['tags'])) {
                foreach ($validated['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['name' => $tagName],
                        ['name' => $tagName]
                    );
                    $item->tags()->attach($tag->id);
                }
            }

            DB::commit();

            $item->load(['identifiers', 'contributors', 'tags']);
            return response()->json($item);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update item'], 500);
        }
    }

    public function destroy($id)
    {
        $item = Item::where('user_id', Auth::id())->findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Item deleted successfully']);
    }

    /**
     * Search for book by ISBN using Open Library API
     */
    public function searchBookByIsbn(Request $request, OpenLibraryService $openLibraryService)
    {
        $request->validate([
            'isbn' => 'required|string|min:10|max:17'
        ]);

        $bookData = $openLibraryService->searchByIsbn($request->isbn);

        if (!$bookData) {
            return response()->json([
                'message' => 'No book found for the provided ISBN',
                'isbn' => $request->isbn
            ], 404);
        }

        return response()->json([
            'data' => $bookData,
            'source' => 'openlibrary'
        ]);
    }

    /**
     * Search for music by barcode using Discogs API
     */
    public function searchMusicByBarcode(Request $request, DiscogsService $discogsService)
    {
        $request->validate([
            'barcode' => 'required|string|min:8|max:14'
        ]);

        $musicData = $discogsService->searchByBarcode($request->barcode);

        if (!$musicData) {
            return response()->json([
                'message' => 'No music release found for the provided barcode',
                'barcode' => $request->barcode
            ], 404);
        }

        return response()->json([
            'data' => $musicData,
            'source' => 'discogs'
        ]);
    }

    /**
     * Search for music by catalog number using Discogs API
     */
    public function searchMusicByCatalog(Request $request, DiscogsService $discogsService)
    {
        $request->validate([
            'catalog_number' => 'required|string|min:1|max:50',
            'label' => 'nullable|string|max:100'
        ]);

        $musicData = $discogsService->searchByCatalogNumber(
            $request->catalog_number,
            $request->label
        );

        if (!$musicData) {
            return response()->json([
                'message' => 'No music release found for the provided catalog number',
                'catalog_number' => $request->catalog_number,
                'label' => $request->label
            ], 404);
        }

        return response()->json([
            'data' => $musicData,
            'source' => 'discogs'
        ]);
    }

    /**
     * Create item from external API data
     */
    public function createFromApiData(Request $request)
    {
        $request->validate([
            'source' => 'required|in:openlibrary,discogs',
            'api_data' => 'required|array',
            'media_type' => 'required|in:book,music',
            'owned' => 'boolean',
            'condition' => 'nullable|in:mint,near_mint,excellent,very_good,good,fair,poor',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'notes' => 'nullable|string'
        ]);

        $apiData = $request->api_data;

        DB::beginTransaction();
        try {
            // Create the item with API data
            $item = Item::create([
                'user_id' => Auth::id(),
                'media_type' => $request->media_type,
                'title' => $apiData['title'] ?? 'Unknown Title',
                'year' => $apiData['year'] ?? null,
                'cover_url' => $apiData['cover_url'] ?? null,
                'owned' => $request->owned ?? true,
                'condition' => $request->condition,
                'purchase_price' => $request->purchase_price,
                'purchase_date' => $request->purchase_date,
                'notes' => $request->notes,
            ]);

            // Add identifiers from API data
            if (isset($apiData['identifiers']) && is_array($apiData['identifiers'])) {
                foreach ($apiData['identifiers'] as $identifier) {
                    $item->identifiers()->create([
                        'type' => $identifier['type'],
                        'value' => $identifier['value']
                    ]);
                }
            }

            // Add contributors from API data
            if (isset($apiData['contributors']) && is_array($apiData['contributors'])) {
                foreach ($apiData['contributors'] as $contributorData) {
                    $contributor = Contributor::firstOrCreate(
                        ['name' => $contributorData['name']],
                        ['name' => $contributorData['name']]
                    );
                    
                    $item->contributors()->attach($contributor->id, [
                        'role' => $contributorData['role']
                    ]);
                }
            }

            // Add genres/subjects as tags
            $tags = [];
            if (isset($apiData['metadata']['genres'])) {
                $tags = array_merge($tags, $apiData['metadata']['genres']);
            }
            if (isset($apiData['metadata']['styles'])) {
                $tags = array_merge($tags, $apiData['metadata']['styles']);
            }
            if (isset($apiData['metadata']['subjects'])) {
                $tags = array_merge($tags, array_slice($apiData['metadata']['subjects'], 0, 5));
            }

            foreach (array_unique($tags) as $tagName) {
                if (!empty(trim($tagName))) {
                    $tag = Tag::firstOrCreate(
                        ['name' => trim($tagName)],
                        ['name' => trim($tagName)]
                    );
                    $item->tags()->attach($tag->id);
                }
            }

            DB::commit();

            $item->load(['identifiers', 'contributors', 'tags']);
            return response()->json($item, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create item from API data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}