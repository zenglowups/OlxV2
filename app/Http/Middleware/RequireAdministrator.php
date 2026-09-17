<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireAdministrator
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->is_admin && $request->user()->hasVerifiedEmail() && $request->user()->canAuthenticate(), 403);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
