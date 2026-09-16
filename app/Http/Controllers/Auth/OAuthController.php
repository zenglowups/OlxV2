<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthenticatedSession;
use App\Services\Auth\OAuthAccounts;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class OAuthController extends Controller
{
    public static function enabled(string $provider): bool
    {
        return in_array($provider, ['google', 'facebook'], true)
            && config("services.$provider.client_id")
            && config("services.$provider.client_secret")
            && config("services.$provider.redirect");
    }

    public function redirect(Request $request, string $provider)
    {
        abort_unless(self::enabled($provider), 404);
        $request->session()->put('oauth_locale', app()->getLocale());

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider, OAuthAccounts $accounts, AuthenticatedSession $session)
    {
        abort_unless(self::enabled($provider), 404);
        $locale = $request->session()->pull('oauth_locale', 'ro');
        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);
        try {
            $identity = Socialite::driver($provider)->user(); // Session-backed state validation is mandatory.
            $user = $accounts->resolve($provider, $identity, $locale);
            if (! $user->canAuthenticate()) {
                throw ValidationException::withMessages(['email' => __('auth.failed')]);
            }
            $session->start($request, $user);

            return redirect()->route('account');
        } catch (ValidationException $exception) {
            return redirect()->route('login')->withErrors($exception->errors());
        } catch (InvalidStateException|UniqueConstraintViolationException $exception) {
            return redirect()->route('login')->withErrors(['email' => __('auth.oauth_failed')]);
        } catch (GuzzleException $exception) {
            // Provider responses may contain secrets; do not log the request or exception body.
            return redirect()->route('login')->withErrors(['email' => __('auth.oauth_failed')]);
        }
    }
}
