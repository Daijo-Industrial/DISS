<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => null,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'unverified@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $response->assertSessionHas('unverified_email', 'unverified@example.com');
    }

    public function test_verified_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'verified@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_can_view_resend_verification_form()
    {
        $response = $this->get('/email/resend-verification');

        $response->assertStatus(200);
        $response->assertViewIs('auth.verify-resend');
    }

    public function test_guest_can_request_resend_verification_link()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
            'is_active' => true,
        ]);

        $response = $this->post('/email/resend-verification', [
            'email' => 'unverified@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'If an unverified account exists for that email address, a new verification link has been sent.');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_verification_returns_generic_message_for_non_existent_email()
    {
        Notification::fake();

        $response = $this->post('/email/resend-verification', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'If an unverified account exists for that email address, a new verification link has been sent.');

        Notification::assertNothingSent();
    }

    public function test_resend_verification_does_not_send_to_already_verified_user()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'alreadyverified@example.com',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->post('/email/resend-verification', [
            'email' => 'alreadyverified@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'If an unverified account exists for that email address, a new verification link has been sent.');

        Notification::assertNothingSent();
    }

    public function test_guest_can_verify_email_via_signed_link()
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
            'is_active' => true,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status', 'Your email has been verified! You may now sign in.');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verification_link_fails_with_invalid_signature()
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
            'is_active' => true,
        ]);

        $invalidUrl = "/email/verify/{$user->getKey()}/wrong-hash?expires=9999999999&signature=invalid";

        $response = $this->get($invalidUrl);

        $response->assertStatus(403);
        $this->assertNull($user->fresh()->email_verified_at);
    }
}
