<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::middleware(SetLocale::class)->group(function (): void {
    Route::view('/', 'home')->name('home');
    Route::view('/{locale}', 'home')->whereIn('locale', ['ro', 'en'])->name('home.localized');
});
