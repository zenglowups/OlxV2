<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSession
{
    public function start(Request $request, User $user, bool $remember = false): void
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put('auth_version', $user->auth_version);
    }

    public function end(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
