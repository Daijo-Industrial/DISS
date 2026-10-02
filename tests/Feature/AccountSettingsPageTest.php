<?php

namespace Tests\Feature;

use App\Livewire\Account\AccountSettingsPage;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login()
    {
        $response = $this->get(route('account.settings'));
        $response->assertRedirect(route('login'));
    }

    public function test_account_security_route_redirects_to_settings_security_tab()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('account.security'));
        $response->assertRedirect(route('account.settings', ['tab' => 'security']));
    }

    public function test_user_can_view_account_settings_page()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        Livewire::actingAs($user)
            ->test(AccountSettingsPage::class)
            ->assertSet('tab', 'profile')
            ->assertSet('name', 'John Doe')
            ->assertSet('email', 'john@example.com')
            ->assertDontSeeHtml('super-admin')
            ->assertDontSeeHtml('Staff');
    }

    public function test_user_can_update_their_name()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'user@example.com',
        ]);

        Livewire::actingAs($user)
            ->test(AccountSettingsPage::class)
            ->set('name', 'Updated Name')
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
    }

    public function test_email_change_triggers_otp_notification()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        Livewire::actingAs($user)
            ->test(AccountSettingsPage::class)
            ->set('email', 'new@example.com')
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertSet('showEmailOtpModal', true)
            ->assertSet('pendingEmail', 'new@example.com');

        // Verify notification sent
        Notification::assertSentOnDemand(EmailVerificationCodeNotification::class, function ($notification, $channels, $notifiable) {
            return $notifiable->routes['mail'] === 'new@example.com';
        });

        // Email in database should NOT change yet
        $user->refresh();
        $this->assertEquals('old@example.com', $user->email);
    }

    public function test_submitting_correct_otp_updates_email()
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        // Mock cached OTP
        Cache::put('email_change_otp:' . $user->id, [
            'email' => 'verified@example.com',
            'code' => '654321',
            'attempts' => 0,
            'resend_allowed_at' => now()->addSeconds(60)->timestamp,
        ], now()->addMinutes(15));

        Livewire::actingAs($user)
            ->test(AccountSettingsPage::class)
            ->set('emailOtp', '654321')
            ->call('confirmEmailChange')
            ->assertHasNoErrors()
            ->assertSet('showEmailOtpModal', false)
            ->assertSet('email', 'verified@example.com');

        $user->refresh();
        $this->assertEquals('verified@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull(Cache::get('email_change_otp:' . $user->id));
    }

    public function test_submitting_incorrect_otp_fails()
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        Cache::put('email_change_otp:' . $user->id, [
            'email' => 'new@example.com',
            'code' => '123456',
            'attempts' => 0,
            'resend_allowed_at' => now()->addSeconds(60)->timestamp,
        ], now()->addMinutes(15));

        Livewire::actingAs($user)
            ->test(AccountSettingsPage::class)
            ->set('emailOtp', '999999')
            ->call('confirmEmailChange')
            ->assertHasErrors(['emailOtp']);

        $user->refresh();
        $this->assertEquals('old@example.com', $user->email);
    }

    public function test_regular_user_cannot_access_abilities_tab()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->withQueryParams(['tab' => 'abilities'])
            ->test(AccountSettingsPage::class)
            ->assertSet('tab', 'profile') // fallback to profile
            ->assertDontSee('System Abilities (Admin)');
    }

    public function test_super_admin_can_access_abilities_tab()
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        Livewire::actingAs($user)
            ->withQueryParams(['tab' => 'abilities'])
            ->test(AccountSettingsPage::class)
            ->assertSet('tab', 'abilities')
            ->assertSee('Super Administrator Global Access Granted')
            ->assertSee('System Abilities (Admin)');
    }

    public function test_user_can_change_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        Livewire::actingAs($user)
            ->test(AccountSettingsPage::class)
            ->set('current_password', 'oldpassword')
            ->set('password', 'newsecretpass123')
            ->set('password_confirmation', 'newsecretpass123')
            ->call('changePassword')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $user->refresh();
        $this->assertTrue(Hash::check('newsecretpass123', $user->password));
    }

    public function test_change_password_validates_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        Livewire::actingAs($user)
            ->test(AccountSettingsPage::class)
            ->set('current_password', 'incorrectpass')
            ->set('password', 'newsecretpass123')
            ->set('password_confirmation', 'newsecretpass123')
            ->call('changePassword')
            ->assertHasErrors(['current_password' => 'The current password is incorrect.']);
    }

    public function test_unverified_user_sees_warning_banner_and_can_initiate_verification()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
        ]);

        Livewire::actingAs($user)
            ->test(AccountSettingsPage::class)
            ->assertSee('Unverified')
            ->assertSee('Verify now')
            ->call('verifyCurrentEmail')
            ->assertHasNoErrors()
            ->assertSet('showEmailOtpModal', true)
            ->assertSet('pendingEmail', 'unverified@example.com');

        Notification::assertSentOnDemand(EmailVerificationCodeNotification::class, function ($notification, $channels, $notifiable) {
            return $notifiable->routes['mail'] === 'unverified@example.com';
        });
    }
}
