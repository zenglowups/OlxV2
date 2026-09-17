<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function registration(array $extra = []): array
    {
        return array_replace([
            'name' => 'Test Seller', 'email' => 'seller@example.test',
            'password' => 'a sufficiently long password',
            'password_confirmation' => 'a sufficiently long password',
            'account_type' => 'individual',
        ], $extra);
    }

    public function test_registration_persists_normalized_account_and_queues_verification_without_privilege_escalation(): void
    {
        Notification::fake();
        $this->post('/en/register', $this->registration(['email' => 'SELLER@example.test', 'account_type' => 'business', 'status' => 'banned', 'is_admin' => true, 'auth_version' => 999, 'email_verified_at' => now()]))
            ->assertRedirect('/en/email/verify');
        $user = User::sole();
        $this->assertSame('seller@example.test', $user->email);
        $this->assertSame('en', $user->locale);
        $this->assertSame('business', $user->account_type);
        $this->assertSame('active', $user->status);
        $this->assertFalse($user->is_admin);
        $this->assertSame(1, $user->auth_version);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('a sufficiently long password', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->get('/en/email/verify')->assertOk();
        Notification::assertSentTo($user, VerifyEmailNotification::class);
        $this->get('/en/account')->assertRedirect('/en/email/verify');
    }

    public function test_registration_validates_duplicates_password_confirmation_and_account_type(): void
    {
        User::factory()->create(['email' => 'seller@example.test']);
        $this->post('/ro/register', $this->registration(['email' => 'SELLER@example.test', 'password' => 'short', 'account_type' => 'admin']))
            ->assertSessionHasErrors(['email', 'password', 'account_type']);
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_login_rotates_session_and_logout_invalidates_it(): void
    {
        $user = User::factory()->create(['password' => 'a sufficiently long password']);
        $this->get('/ro/login');
        $oldId = session()->getId();
        $this->post('/ro/login', ['email' => strtoupper($user->email), 'password' => 'a sufficiently long password'])
            ->assertRedirect('/ro/account');
        $this->assertNotSame($oldId, session()->getId());
        $this->assertAuthenticatedAs($user);
        $this->get('/ro/account')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Referrer-Policy', 'no-referrer')->assertSee($user->name);
        $this->post('/ro/logout')->assertRedirect('/ro');
        $this->assertGuest();
        $this->get('/ro/account')->assertRedirect('/ro/login');
    }

    public function test_login_rejects_wrong_password_and_limits_attempts(): void
    {
        User::factory()->create(['email' => 'seller@example.test']);
        for ($i = 0; $i < 6; $i++) {
            $this->post('/ro/login', ['email' => 'seller@example.test', 'password' => 'incorrect'])
                ->assertSessionHasErrors('email');
        }
        $this->post('/ro/login', ['email' => 'seller@example.test', 'password' => 'incorrect'])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_banned_and_suspended_accounts_cannot_login(): void
    {
        foreach (['banned', 'suspended'] as $status) {
            $user = User::factory()->create(['status' => $status, 'password' => 'a sufficiently long password']);
            $this->post('/ro/login', ['email' => $user->email, 'password' => 'a sufficiently long password'])->assertSessionHasErrors('email');
            $this->assertGuest();
        }
    }

    public function test_expired_suspension_can_authenticate(): void
    {
        $user = User::factory()->create(['status' => 'suspended', 'suspended_until' => now()->subMinute(), 'password' => 'a sufficiently long password']);
        $this->post('/ro/login', ['email' => $user->email, 'password' => 'a sufficiently long password'])->assertRedirect('/ro/account');
        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_session_loses_access_after_ban_or_revocation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['auth_version' => 1]);
        $user->forceFill(['status' => 'banned'])->save();
        $this->get('/ro/account')->assertRedirect('/ro/login');
        $this->assertGuest();
        $user->forceFill(['status' => 'active', 'auth_version' => 2])->save();
        $this->actingAs($user)->withSession(['auth_version' => 1])->get('/ro/account')->assertRedirect('/ro/login');
        $this->assertGuest();
    }

    public function test_revoke_requires_password_and_closes_all_sessions(): void
    {
        $user = User::factory()->create(['password' => 'a sufficiently long password']);
        $this->actingAs($user)->withSession(['auth_version' => 1]);
        $this->post('/ro/account/revoke-sessions', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->post('/ro/account/revoke-sessions', ['password' => 'a sufficiently long password'])->assertRedirect('/ro/login');
        $this->assertGuest();
        $this->assertSame(2, $user->fresh()->auth_version);
    }

    public function test_email_verification_requires_matching_authenticated_user_and_valid_unexpired_signature(): void
    {
        $user = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['locale' => 'ro', 'id' => $user->id, 'hash' => sha1($user->email)], absolute: false);
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->actingAs($user)->get($url.'&tampered=1')->assertForbidden();
        $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['locale' => 'ro', 'id' => $user->id, 'hash' => sha1($user->email)], absolute: false);
        $this->get($expired)->assertForbidden();
        $this->get($url)->assertRedirect('/ro/account');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_resend_uses_queued_localized_notification(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create(['locale' => 'ro']);
        $this->actingAs($user)->post('/ro/email/verification-notification')->assertRedirect();
        Notification::assertSentTo($user, VerifyEmailNotification::class, fn ($notification) => $notification instanceof ShouldQueue);
        $message = (new VerifyEmailNotification)->toMail($user);
        $this->assertSame('Confirmă adresa de email', $message->subject);
        $this->assertStringContainsString('/ro/email/verify/', $message->actionUrl);
    }

    public function test_password_recovery_does_not_disclose_account_existence_or_send_for_banned_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $known = $this->post('/ro/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        $message = session('status');
        $this->post('/ro/forgot-password', ['email' => 'absent@example.test'])->assertSessionHas('status', $message);
        $banned = User::factory()->create(['status' => 'banned']);
        $this->post('/ro/forgot-password', ['email' => $banned->email])->assertSessionHas('status', $message);
        Notification::assertSentTo($user, ResetPasswordNotification::class);
        Notification::assertNotSentTo($banned, ResetPasswordNotification::class);
    }

    public function test_password_reset_is_single_use_and_revokes_existing_sessions(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $data = ['email' => $user->email, 'token' => $token, 'password' => 'new secure memorable phrase', 'password_confirmation' => 'new secure memorable phrase'];
        $this->post('/ro/reset-password', $data)->assertRedirect('/ro/login');
        $this->assertTrue(Hash::check($data['password'], $user->fresh()->password));
        $this->assertSame(2, $user->fresh()->auth_version);
        $this->post('/ro/reset-password', $data)->assertSessionHasErrors('email');
    }

    public function test_expired_reset_token_cannot_change_password(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $this->travel(61)->minutes();
        $this->post('/ro/reset-password', ['email' => $user->email, 'token' => $token, 'password' => 'new secure memorable phrase', 'password_confirmation' => 'new secure memorable phrase'])->assertSessionHasErrors('email');
        $this->assertSame(1, $user->fresh()->auth_version);
    }

    public function test_unconfigured_oauth_is_unavailable_and_not_advertised(): void
    {
        $this->get('/ro/login')->assertOk()->assertDontSee('Continuă cu Google')->assertDontSee('Continuă cu Facebook');
        $this->get('/ro/oauth/google')->assertNotFound();
        $this->get('/oauth/facebook/callback')->assertNotFound();
        $this->get('/ro/oauth/unknown')->assertNotFound();
    }

    public function test_auth_pages_are_localized_and_private(): void
    {
        $this->get('/en/register')->assertOk()->assertSee('Create your account')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/ro/register')->assertOk()->assertSee('Creează-ți contul');
        $this->get('/en/forgot-password')->assertOk()->assertSee('Recover access');
        $this->get('/ro/reset-password')->assertStatus(400)->assertSee('Alege o parolă nouă');
    }

    public function test_malformed_input_is_validated_without_server_errors(): void
    {
        $this->from('/ro/register')->post('/ro/register', $this->registration(['email' => ['bad'], 'name' => ['bad']]))->assertSessionHasErrors(['email', 'name']);
        $this->get('/ro/register')->assertOk();
        $this->post('/ro/login', ['email' => ['bad'], 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/ro/forgot-password', ['email' => ['bad']])->assertSessionHasErrors('email');
        $this->get('/ro/reset-password?email[]=bad&token[]=bad')->assertStatus(400);
    }
}
