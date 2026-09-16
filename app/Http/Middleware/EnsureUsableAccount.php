<?php

namespace App\Http\Middleware;

use App\Services\Auth\AuthenticatedSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUsableAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            if (! $user->canAuthenticate()
                || $request->session()->get('auth_version', $user->auth_version) !== $user->auth_version) {
                app(AuthenticatedSession::class)->end($request);

                return redirect()->route('login', ['locale' => $user->preferredLocale()])
                    ->withErrors(['email' => __('auth.session_expired', [], $user->preferredLocale())]);
            }
            $request->session()->put('auth_version', $user->auth_version);
        }
        $response = $next($request);
        if ($request->user() || $request->is('*/login', '*/register', '*/forgot-password', '*/reset-password', 'oauth/*') || $request->routeIs('login', 'register', 'password.*', 'verification.*', 'oauth.*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Referrer-Policy', 'no-referrer');
        }

        return $response;
    }
}
