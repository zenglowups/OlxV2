<?php

use App\Http\Controllers\HealthController;
use App\Http\Middleware\EnsureUsableAccount;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // No session middleware: Redis failure must return 503, not a session error.
            Route::get('/health/ready', HealthController::class)->name('health.ready');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, SetLocale::class);
        $middleware->web(append: [EnsureUsableAccount::class]);
        $middleware->redirectGuestsTo(fn (Request $request) => route('login', ['locale' => $request->route('locale') ?? app()->getLocale()]));
        $middleware->redirectUsersTo(fn (Request $request) => route('account', ['locale' => $request->route('locale') ?? app()->getLocale()]));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('health/*', 'api/*') || $request->expectsJson(),
        );
    })->create();
