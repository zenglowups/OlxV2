<?php

namespace App\Http\Controllers;

use App\Services\ReadinessChecker;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(ReadinessChecker $checker): JsonResponse
    {
        $ready = $checker->ready();

        return response()->json(['application' => 'olxv2', 'status' => $ready ? 'ok' : 'unavailable'], $ready ? 200 : 503)
            ->header('Cache-Control', 'no-store');
    }
}
