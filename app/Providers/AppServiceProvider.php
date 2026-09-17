<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $identity = fn (Request $request) => hash('sha256', (is_string($request->input('email')) ? mb_strtolower(trim($request->input('email'))) : 'invalid').'|'.$request->ip());
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(6)->by('login:'.$identity($request)),
            Limit::perMinute(30)->by('login-ip:'.$request->ip()),
        ]);
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('recovery', fn (Request $request) => [
            Limit::perMinute(3)->by('recovery:'.$identity($request)),
            Limit::perMinute(10)->by('recovery-ip:'.$request->ip()),
        ]);
        RateLimiter::for('verification', fn (Request $request) => Limit::perMinute(6)->by($request->user()?->id ?? $request->ip()));
        RateLimiter::for('oauth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('profile', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?? $request->ip()));
        RateLimiter::for('security', fn (Request $request) => Limit::perMinute(5)->by($request->user()?->id ?? $request->ip()));
    }
}
