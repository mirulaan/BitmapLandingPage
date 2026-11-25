<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

// Public Staff Portal
Route::get('/', [PortalController::class, 'index'])->name('portal');
Route::get('/wifi', [PortalController::class, 'showWifi'])->name('wifi');
Route::get('/services/ping', [PortalController::class, 'pingServices'])->name('services.ping');
Route::get('/services/{service}/ping', [PortalController::class, 'pingService'])->name('services.ping_one');

// Auth routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public Notice creation (Staff Portal "＋ Post" modal)
Route::post('/notices', [AdminController::class, 'storeNotice'])->name('notices.public_store');

// Admin panel (auth-protected)
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::put('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');

    // Services CRUD
    Route::post('/services', [AdminController::class, 'storeService'])->name('services.store');
    Route::put('/services/{service}', [AdminController::class, 'updateService'])->name('services.update');
    Route::delete('/services/{service}', [AdminController::class, 'destroyService'])->name('services.destroy');
    Route::post('/services/reorder', [AdminController::class, 'reorderServices'])->name('services.reorder');

    // Notices CRUD
    Route::post('/notices', [AdminController::class, 'storeNotice'])->name('notices.store');
    Route::put('/notices/{notice}', [AdminController::class, 'updateNotice'])->name('notices.update');
    Route::delete('/notices/{notice}', [AdminController::class, 'destroyNotice'])->name('notices.destroy');

    // Bookmarks CRUD
    Route::post('/bookmarks', [AdminController::class, 'storeBookmark'])->name('bookmarks.store');
    Route::put('/bookmarks/{bookmark}', [AdminController::class, 'updateBookmark'])->name('bookmarks.update');
    Route::delete('/bookmarks/{bookmark}', [AdminController::class, 'destroyBookmark'])->name('bookmarks.destroy');

    // Portal Users (admin only)
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');

    // Backup & Restore
    Route::get('/export', [AdminController::class, 'exportConfig'])->name('export');
    Route::post('/import', [AdminController::class, 'importConfig'])->name('import');
    Route::post('/reset', [AdminController::class, 'resetDefaults'])->name('reset');
});

Route::fallback(fn () => redirect()->route('portal'));
