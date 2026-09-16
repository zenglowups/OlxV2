<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $dev = app()->environment('local');
        $script = $dev ? " 'unsafe-inline' http://localhost:5173" : '';
        $style = $dev ? " 'unsafe-inline' http://localhost:5173" : '';
        $connect = $dev ? ' http://localhost:5173 ws://localhost:5173' : '';
        $response->headers->set('Content-Security-Policy', "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; img-src 'self' data:; font-src 'self'; script-src 'self'{$script}; style-src 'self'{$style}; connect-src 'self'{$connect}");

        return $response;
    }
}
