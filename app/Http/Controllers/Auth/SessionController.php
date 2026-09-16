<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthenticatedSession;
use App\Support\Email;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request, AuthenticatedSession $session)
    {
        $request->merge(['email' => Email::normalize($request->input('email'))]);
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:128'],
            'remember' => ['sometimes', 'boolean'],
        ]);
        if (! Auth::attemptWhen(
            ['email' => $credentials['email'], 'password' => $credentials['password']],
            fn ($user) => $user->canAuthenticate(),
            $request->boolean('remember'),
        )) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }
        $request->session()->regenerate();
        $request->session()->put('auth_version', $request->user()->auth_version);

        return redirect()->intended(route('account'));
    }

    public function destroy(Request $request, AuthenticatedSession $session)
    {
        $session->end($request);

        return redirect()->route('home.localized');
    }

    public function revoke(Request $request, AuthenticatedSession $session)
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $user = $request->user();
        $user->increment('auth_version');
        $user->forceFill(['remember_token' => Str::random(60)])->save();
        $session->end($request);

        return redirect()->route('login')->with('status', __('auth.sessions_revoked'));
    }
}
