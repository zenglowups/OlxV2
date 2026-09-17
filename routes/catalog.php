<?php

use App\Http\Controllers\Admin\CatalogController as AdminCatalog;
use App\Http\Controllers\CatalogController;
use App\Http\Middleware\RequireAdministrator;
use Illuminate\Support\Facades\Route;

Route::get('/categories', [CatalogController::class, 'index'])->name('categories.index');
Route::get('/categories/{category:slug}', [CatalogController::class, 'show'])->name('categories.show');
Route::middleware(['auth', 'verified', RequireAdministrator::class])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/categories', [AdminCatalog::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [AdminCatalog::class, 'create'])->name('categories.create');
    Route::post('/categories', [AdminCatalog::class, 'store'])->middleware('throttle:admin-write')->name('categories.store');
    Route::get('/categories/{category}/edit', [AdminCatalog::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [AdminCatalog::class, 'update'])->middleware('throttle:admin-write')->name('categories.update');
    Route::get('/categories/{category}/attributes/create', [AdminCatalog::class, 'createAttribute'])->name('attributes.create');
    Route::post('/categories/{category}/attributes', [AdminCatalog::class, 'storeAttribute'])->middleware('throttle:admin-write')->name('attributes.store');
    Route::get('/categories/{category}/attributes/{attribute}/edit', [AdminCatalog::class, 'editAttribute'])->name('attributes.edit');
    Route::put('/categories/{category}/attributes/{attribute}', [AdminCatalog::class, 'updateAttribute'])->middleware('throttle:admin-write')->name('attributes.update');
});
