<?php

namespace App\Services\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as ProviderUser;

class OAuthAccounts
{
    public function resolve(string $provider, ProviderUser $identity, string $locale): User
    {
        $providerId = (string) $identity->getId();
        if ($providerId === '' || strlen($providerId) > 255) {
            throw ValidationException::withMessages(['email' => __('auth.oauth_failed')]);
        }
        $account = SocialAccount::with('user')->where('provider', $provider)->where('provider_id', $providerId)->first();
        if ($account) {
            return $account->user;
        }
        $email = mb_strtolower(trim((string) $identity->getEmail()));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            throw ValidationException::withMessages(['email' => __('auth.oauth_email_required')]);
        }
        // Never link a provider to an existing account merely because emails match.
        if (User::whereRaw('lower(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages(['email' => __('auth.oauth_existing')]);
        }

        return DB::transaction(function () use ($provider, $providerId, $email, $identity, $locale) {
            $user = User::create([
                'name' => Str::limit($identity->getName() ?: $identity->getNickname() ?: __('auth.new_member'), 80, ''),
                'email' => $email, 'password' => Str::random(80), 'locale' => $locale,
                'account_type' => 'individual',
            ])->refresh();
            // Facebook email still requires our own email verification.
            $raw = $identity->getRaw();
            if ($provider === 'google' && ($raw['email_verified'] ?? $raw['verified_email'] ?? false) === true) {
                $user->markEmailAsVerified();
            }
            SocialAccount::create(['user_id' => $user->id, 'provider' => $provider, 'provider_id' => $providerId]);
            event(new Registered($user));

            return $user;
        });
    }
}
