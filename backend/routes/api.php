<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\CollectionController;
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
});
