<?php

use App\Http\Controllers\DraftController;
use App\Http\Controllers\ListingPhotoController;
use App\Services\Listings\DraftValidator;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::post('/account/drafts/{listing}/photos', [ListingPhotoController::class, 'store'])->whereUuid('listing')->middleware('throttle:photo-upload')->name('photos.store');
    Route::get('/account/drafts/{listing}/photos/status', [ListingPhotoController::class, 'status'])->whereUuid('listing')->middleware('throttle:photo-status')->name('photos.status');
    Route::put('/account/drafts/{listing}/photos/{photo}', [ListingPhotoController::class, 'update'])->whereUuid(['listing', 'photo'])->middleware('throttle:draft-save')->name('photos.update');
    Route::delete('/account/drafts/{listing}/photos/{photo}', [ListingPhotoController::class, 'destroy'])->whereUuid(['listing', 'photo'])->middleware('throttle:draft-save')->name('photos.destroy');
    Route::get('/sell', [DraftController::class, 'create'])->name('drafts.create');
    Route::get('/account/drafts', [DraftController::class, 'index'])->name('drafts.index');
    Route::post('/account/drafts', [DraftController::class, 'store'])->middleware('throttle:draft-create')->name('drafts.store');
    Route::get('/account/drafts/{listing}/preview', [DraftController::class, 'preview'])->whereUuid('listing')->name('drafts.preview');
    Route::get('/account/drafts/{listing}/edit/{step}', [DraftController::class, 'edit'])->whereUuid('listing')->whereIn('step', DraftValidator::STEPS)->name('drafts.edit');
    Route::match(['POST', 'PATCH'], '/account/drafts/{listing}/steps/{step}', [DraftController::class, 'update'])
        ->whereUuid('listing')->whereIn('step', array_diff(DraftValidator::STEPS, ['preview']))->middleware('throttle:draft-save')->name('drafts.update');
});
