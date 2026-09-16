<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale') ?? config('app.locale');
        abort_unless(in_array($locale, ['ro', 'en'], true), 404);
        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);
        // Locale is routing context, not a controller or model argument.
        $request->route()->forgetParameter('locale');

        return $next($request);
    }
}
