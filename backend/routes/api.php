<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\PriceCheckController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\PriceAlertController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Item management
    Route::apiResource('items', ItemController::class);
    
    // Collection management
    Route::apiResource('collections', CollectionController::class);
    Route::post('/collections/{collection}/items', [CollectionController::class, 'addItems']);
    Route::delete('/collections/{collection}/items', [CollectionController::class, 'removeItems']);
    
    // External API search endpoints (with rate limiting)
    Route::middleware('api.rate.limit:5,1')->group(function () {
        Route::post('/search/book/isbn', [ItemController::class, 'searchBookByIsbn']);
        Route::post('/search/music/barcode', [ItemController::class, 'searchMusicByBarcode']);
        Route::post('/search/music/catalog', [ItemController::class, 'searchMusicByCatalog']);
    });
    Route::post('/items/from-api', [ItemController::class, 'createFromApiData']);
    
    // Price checking routes
    Route::prefix('items/{item}/price-checks')->group(function () {
        Route::get('/', [PriceCheckController::class, 'index']);
        Route::post('/refresh', [PriceCheckController::class, 'refresh']);
        Route::get('/history', [PriceCheckController::class, 'history']);
        Route::get('/statistics', [PriceCheckController::class, 'statistics']);
    });
    
    // Wishlist routes
    Route::apiResource('wishlists', WishlistController::class);
    Route::post('/wishlists/{wishlist}/items', [WishlistController::class, 'addItem']);
    Route::delete('/wishlists/{wishlist}/items', [WishlistController::class, 'removeItem']);
    Route::put('/wishlists/{wishlist}/items', [WishlistController::class, 'updateItem']);
    Route::post('/wishlists/{wishlist}/toggle-public', [WishlistController::class, 'togglePublic']);
    
    // Price alert routes
    Route::get('/price-alerts', [PriceAlertController::class, 'index']);
    Route::get('/price-alerts/statistics', [PriceAlertController::class, 'statistics']);
    Route::patch('/price-alerts/{priceAlert}/read', [PriceAlertController::class, 'markAsRead']);
    Route::patch('/price-alerts/mark-all-read', [PriceAlertController::class, 'markAllAsRead']);
    Route::delete('/price-alerts/{priceAlert}', [PriceAlertController::class, 'destroy']);
});

// Public wishlist routes (no authentication required)
Route::get('/wishlists/public/{shareToken}', [WishlistController::class, 'public']);
