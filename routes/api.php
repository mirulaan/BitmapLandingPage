<?php

use App\Http\Controllers\AdminController;
use App\Models\Bookmark;
use App\Models\Notice;
use App\Models\PortalSetting;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public JSON Endpoints for live updates & search
Route::get('/settings', fn () => response()->json(PortalSetting::current()));
Route::get('/services', fn () => response()->json(Service::ordered()->get()));
Route::get('/notices', fn () => response()->json(Notice::sorted()->get()));
Route::get('/bookmarks', fn () => response()->json(Bookmark::ordered()->get()));

// Public notice submission from Staff Portal
Route::post('/notices', [AdminController::class, 'storeNotice']);

// Authenticated API operations
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/settings', [AdminController::class, 'updateSettings']);
    Route::post('/services', [AdminController::class, 'storeService']);
    Route::put('/services/{service}', [AdminController::class, 'updateService']);
    Route::delete('/services/{service}', [AdminController::class, 'destroyService']);
    Route::post('/services/reorder', [AdminController::class, 'reorderServices']);

    Route::put('/notices/{notice}', [AdminController::class, 'updateNotice']);
    Route::delete('/notices/{notice}', [AdminController::class, 'destroyNotice']);

    Route::post('/bookmarks', [AdminController::class, 'storeBookmark']);
    Route::put('/bookmarks/{bookmark}', [AdminController::class, 'updateBookmark']);
    Route::delete('/bookmarks/{bookmark}', [AdminController::class, 'destroyBookmark']);
});
