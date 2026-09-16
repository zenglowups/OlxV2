<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\OAuthAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as ProviderUser;
use Mockery;
use Tests\TestCase;

class OAuthTest extends TestCase
{
    use RefreshDatabase;

    private function identity(string $id = 'provider-123', string $email = 'oauth@example.test', bool $verified = true): ProviderUser
    {
        return (new ProviderUser)->setRaw(['email_verified' => $verified])->map(['id' => $id, 'name' => 'OAuth Test', 'email' => $email]);
    }

    public function test_new_google_identity_creates_individual_and_verified_email_without_storing_tokens(): void
    {
        Notification::fake();
        $user = app(OAuthAccounts::class)->resolve('google', $this->identity(), 'en');
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame('individual', $user->account_type);
        $this->assertSame('en', $user->locale);
        $this->assertDatabaseHas('social_accounts', ['provider' => 'google', 'provider_id' => 'provider-123', 'user_id' => $user->id]);
        $this->assertSame($user->id, app(OAuthAccounts::class)->resolve('google', $this->identity(), 'ro')->id);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_facebook_identity_requires_our_email_verification(): void
    {
        Notification::fake();
        $user = app(OAuthAccounts::class)->resolve('facebook', $this->identity(), 'ro');
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_matching_email_does_not_link_or_take_over_existing_account(): void
    {
        $user = User::factory()->create(['email' => 'oauth@example.test']);
        try {
            app(OAuthAccounts::class)->resolve('google', $this->identity(), 'ro');
            $this->fail('Existing email must not be automatically linked.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->errors());
        }
        $this->assertDatabaseCount('social_accounts', 0);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_callback_rejects_invalid_oauth_state(): void
    {
        config(['services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-only-secret', 'services.google.redirect' => 'http://localhost:8088/oauth/google/callback']);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
        $this->withSession(['oauth_locale' => 'en'])->get('/oauth/google/callback')->assertRedirect('/en/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_valid_provider_callback_starts_session_but_banned_linked_user_is_rejected(): void
    {
        Notification::fake();
        config(['services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-only-secret', 'services.google.redirect' => 'http://localhost:8088/oauth/google/callback']);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->twice()->andReturn($this->identity());
        Socialite::shouldReceive('driver')->with('google')->twice()->andReturn($provider);
        $this->withSession(['oauth_locale' => 'ro'])->get('/oauth/google/callback')->assertRedirect('/ro/account');
        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->post('/ro/logout');
        $user->forceFill(['status' => 'banned'])->save();
        $this->get('/oauth/google/callback')->assertRedirect('/ro/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
