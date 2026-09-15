<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale') ?? config('app.locale');
        abort_unless(in_array($locale, ['ro', 'en'], true), 404);
        app()->setLocale($locale);

        return $next($request);
    }
}
