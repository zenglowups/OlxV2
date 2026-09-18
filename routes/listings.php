<?php

use App\Http\Controllers\DraftController;
use App\Services\Listings\DraftValidator;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/sell', [DraftController::class, 'create'])->name('drafts.create');
    Route::get('/account/drafts', [DraftController::class, 'index'])->name('drafts.index');
    Route::post('/account/drafts', [DraftController::class, 'store'])->middleware('throttle:draft-create')->name('drafts.store');
    Route::get('/account/drafts/{listing}/preview', [DraftController::class, 'preview'])->whereUuid('listing')->name('drafts.preview');
    Route::get('/account/drafts/{listing}/edit/{step}', [DraftController::class, 'edit'])->whereUuid('listing')->whereIn('step', DraftValidator::STEPS)->name('drafts.edit');
    Route::match(['POST', 'PATCH'], '/account/drafts/{listing}/steps/{step}', [DraftController::class, 'update'])
        ->whereUuid('listing')->whereIn('step', array_diff(DraftValidator::STEPS, ['preview']))->middleware('throttle:draft-save')->name('drafts.update');
});
