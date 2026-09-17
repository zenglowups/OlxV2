<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::middleware(SetLocale::class)->group(function (): void {
    Route::get('/', [CatalogController::class, 'home'])->name('home');
    Route::get('/{locale}', [CatalogController::class, 'home'])->whereIn('locale', ['ro', 'en'])->name('home.localized');
});

Route::prefix('{locale}')->whereIn('locale', ['ro', 'en'])->middleware(SetLocale::class)->group(function (): void {
    require __DIR__.'/catalog.php';
    Route::get('/sellers/{profile:username}', [ProfileController::class, 'show'])->name('profile.show');
    Route::middleware(['auth', 'verified'])->group(function (): void {
        Route::get('/account/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/account/profile', [ProfileController::class, 'update'])->middleware('throttle:profile')->name('profile.update');
        Route::get('/account/profile/preview', [ProfileController::class, 'preview'])->name('profile.preview');
    });
    Route::middleware('guest')->group(function (): void {
        Route::get('/register', [RegistrationController::class, 'create'])->name('register');
        Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:register');
        Route::get('/login', [SessionController::class, 'create'])->name('login');
        Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:login');
        Route::get('/forgot-password', [PasswordController::class, 'request'])->name('password.request');
        Route::post('/forgot-password', [PasswordController::class, 'email'])->middleware('throttle:recovery')->name('password.email');
        Route::get('/reset-password', [PasswordController::class, 'reset'])->name('password.reset');
        Route::post('/reset-password', [PasswordController::class, 'update'])->middleware('throttle:recovery')->name('password.update');
        Route::get('/oauth/{provider}', [OAuthController::class, 'redirect'])->whereIn('provider', ['google', 'facebook'])->middleware('throttle:oauth')->name('oauth.redirect');
    });
    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
        Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
        Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed:relative', 'throttle:verification'])->name('verification.verify');
        Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:verification')->name('verification.send');
        Route::view('/account', 'auth.account')->middleware('verified')->name('account');
        Route::post('/account/revoke-sessions', [SessionController::class, 'revoke'])->middleware('throttle:security')->name('sessions.revoke');
    });
});
// Fixed callback URLs can be registered with the providers; original locale stays in the session.
Route::get('/oauth/{provider}/callback', [OAuthController::class, 'callback'])
    ->whereIn('provider', ['google', 'facebook'])->middleware(['guest', 'throttle:oauth'])->name('oauth.callback');

Route::get('/media/avatars/{avatar}', AvatarController::class)->whereUuid('avatar')->name('avatar');
