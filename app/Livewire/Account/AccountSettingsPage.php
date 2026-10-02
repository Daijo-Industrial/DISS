<?php

namespace App\Livewire\Account;

use App\Application\User\Queries\GetUserAbilitiesQuery;
use App\Application\User\UseCases\ChangeUserPassword;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

class AccountSettingsPage extends Component
{
    #[Url]
    public string $tab = 'profile';

    // Profile fields
    public string $name = '';

    public string $email = '';

    // Email OTP Verification
    public bool $showEmailOtpModal = false;

    public string $pendingEmail = '';

    public string $emailOtp = '';

    public int $resendCooldown = 0;

    // Security fields
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    // Notification fields
    public string $global_mode = 'both';

    // Abilities search (Super Admin only)
    public string $abilitiesSearch = '';

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->global_mode = $user->email_notification_mode ?? 'both';

        // Check for pending email verification session/cache
        $cachedOtp = Cache::get('email_change_otp:' . $user->id);
        if ($cachedOtp && ! empty($cachedOtp['email'])) {
            $this->pendingEmail = $cachedOtp['email'];
            $this->showEmailOtpModal = true;
            $resendAllowedAt = $cachedOtp['resend_allowed_at'] ?? 0;
            $this->resendCooldown = max(0, $resendAllowedAt - now()->timestamp);
        }

        // Enforce access control on tabs
        $this->sanitizeTab();
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->sanitizeTab();
    }

    private function sanitizeTab(): void
    {
        $allowedTabs = ['profile', 'security', 'notifications', 'signatures'];

        /** @var User|null $user */
        $user = Auth::user();
        if ($user && $user->hasRole('super-admin')) {
            $allowedTabs[] = 'abilities';
        }

        if (! in_array($this->tab, $allowedTabs, true)) {
            $this->tab = 'profile';
        }
    }

    // ==========================================
    // PROFILE & EMAIL VERIFICATION
    // ==========================================

    public function updateProfile(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->validate([
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $nameChanged = $user->name !== $this->name;
        $emailChanged = strtolower(trim($user->email)) !== strtolower(trim($this->email));

        // Update name if changed
        if ($nameChanged) {
            $user->update(['name' => $this->name]);
        }

        // If email changed, trigger 6-digit OTP verification flow
        if ($emailChanged) {
            $newEmail = strtolower(trim($this->email));
            $this->initiateEmailChange($user, $newEmail);

            return;
        }

        if ($nameChanged) {
            $this->dispatch('toast', [
                'type' => 'success',
                'message' => 'Profile name updated successfully.',
            ]);
            session()->flash('profile_success', 'Profile name updated successfully.');
        }
    }

    public function verifyCurrentEmail(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (! empty($user->email_verified_at)) {
            $this->dispatch('toast', [
                'type' => 'info',
                'message' => 'Your email address is already verified.',
            ]);

            return;
        }

        $this->initiateEmailChange($user, $user->email);
    }

    private function initiateEmailChange(User $user, string $newEmail): void
    {
        // Rate limit verification email dispatches (max 3 per 5 minutes)
        $rateLimitKey = 'send-email-otp:' . $user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $this->addError('email', "Too many verification requests. Please wait {$seconds} seconds.");

            return;
        }
        RateLimiter::hit($rateLimitKey, 300);

        $code = (string) random_int(100000, 999999);

        Cache::put('email_change_otp:' . $user->id, [
            'email' => $newEmail,
            'code' => $code,
            'attempts' => 0,
            'resend_allowed_at' => now()->addSeconds(60)->timestamp,
        ], now()->addMinutes(15));

        try {
            Notification::route('mail', $newEmail)
                ->notify(new EmailVerificationCodeNotification($code, $newEmail, $user->name));
        } catch (\Throwable $e) {
            $this->addError('email', 'Could not send verification email: ' . $e->getMessage());

            return;
        }

        $this->pendingEmail = $newEmail;
        $this->showEmailOtpModal = true;
        $this->emailOtp = '';
        $this->resendCooldown = 60;

        $this->dispatch('toast', [
            'type' => 'info',
            'message' => "Verification code sent to {$newEmail}. Please enter the 6-digit code.",
        ]);
    }

    public function confirmEmailChange(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->validate([
            'emailOtp' => ['required', 'string', 'size:6'],
        ], [
            'emailOtp.required' => 'Please enter the 6-digit verification code.',
            'emailOtp.size' => 'The verification code must be exactly 6 digits.',
        ]);

        $cached = Cache::get('email_change_otp:' . $user->id);

        if (! $cached || empty($cached['code'])) {
            $this->addError('emailOtp', 'Verification code has expired or is invalid. Please request a new code.');

            return;
        }

        // Anti-brute-force: check attempts
        if (($cached['attempts'] ?? 0) >= 5) {
            Cache::forget('email_change_otp:' . $user->id);
            $this->showEmailOtpModal = false;
            $this->email = $user->email;
            $this->addError('email', 'Too many failed verification attempts. Please try again.');

            return;
        }

        if (trim($this->emailOtp) !== (string) $cached['code']) {
            $cached['attempts'] = ($cached['attempts'] ?? 0) + 1;
            Cache::put('email_change_otp:' . $user->id, $cached, now()->addMinutes(15));
            $remaining = 5 - $cached['attempts'];
            $this->addError('emailOtp', "Incorrect verification code. {$remaining} attempts remaining.");

            return;
        }

        // Verification successful! Update email & mark verified
        $targetEmail = $cached['email'];
        $user->update([
            'email' => $targetEmail,
            'email_verified_at' => now(),
        ]);

        Cache::forget('email_change_otp:' . $user->id);

        $this->showEmailOtpModal = false;
        $this->email = $targetEmail;
        $this->pendingEmail = '';
        $this->emailOtp = '';

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Email address verified and updated successfully.',
        ]);
        session()->flash('profile_success', 'Email address verified and updated successfully.');
    }

    public function resendEmailOtp(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $cached = Cache::get('email_change_otp:' . $user->id);
        if (! $cached || empty($cached['email'])) {
            $this->addError('emailOtp', 'No pending email change found. Please update your email again.');

            return;
        }

        $resendAllowedAt = $cached['resend_allowed_at'] ?? 0;
        if (now()->timestamp < $resendAllowedAt) {
            $wait = $resendAllowedAt - now()->timestamp;
            $this->addError('emailOtp', "Please wait {$wait} seconds before requesting a new code.");

            return;
        }

        $this->initiateEmailChange($user, $cached['email']);
    }

    public function cancelEmailChange(): void
    {
        /** @var User $user */
        $user = Auth::user();

        Cache::forget('email_change_otp:' . $user->id);
        $this->showEmailOtpModal = false;
        $this->email = $user->email;
        $this->pendingEmail = '';
        $this->emailOtp = '';
        $this->resetErrorBag();
    }

    // ==========================================
    // SECURITY & PASSWORD
    // ==========================================

    public function changePassword(ChangeUserPassword $changeUserPassword): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (RateLimiter::tooManyAttempts('change-password:' . $user->id, 5)) {
            $this->addError('current_password', 'Too many password attempts. Please try again later.');

            return;
        }

        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        if (! Hash::check($this->current_password, $user->password)) {
            RateLimiter::hit('change-password:' . $user->id, 60);
            $this->addError('current_password', 'The current password is incorrect.');

            return;
        }

        $changeUserPassword->execute($user->id, $this->password);

        RateLimiter::clear('change-password:' . $user->id);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Your password has been changed successfully.',
        ]);
        session()->flash('password_success', 'Your password has been updated.');
    }

    // ==========================================
    // NOTIFICATIONS
    // ==========================================

    public function saveNotifications(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->validate([
            'global_mode' => ['required', 'in:both,immediate,daily_summary'],
        ]);

        $user->update([
            'email_notification_mode' => $this->global_mode,
        ]);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Notification preferences updated successfully.',
        ]);
        session()->flash('notification_success', 'Notification preferences saved.');
    }

    // ==========================================
    // RENDER
    // ==========================================

    public function render(GetUserAbilitiesQuery $abilitiesQuery)
    {
        /** @var User $user */
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('super-admin');

        $abilitiesData = null;
        if ($isSuperAdmin) {
            $abilitiesData = $abilitiesQuery->execute($user);

            // Filter abilities if search term present
            if (! empty(trim($this->abilitiesSearch))) {
                $term = strtolower(trim($this->abilitiesSearch));
                $filteredModules = [];

                foreach ($abilitiesData['modules'] as $module) {
                    $matchedPerms = array_filter($module['permissions'], function ($perm) use ($term) {
                        return str_contains(strtolower($perm['name']), $term) ||
                               str_contains(strtolower($perm['label']), $term);
                    });

                    if (! empty($matchedPerms) || str_contains(strtolower($module['name']), $term)) {
                        $module['permissions'] = array_values($matchedPerms);
                        $filteredModules[] = $module;
                    }
                }

                $abilitiesData['modules'] = $filteredModules;
            }
        }

        return view('livewire.account.account-settings-page', [
            'user' => $user,
            'employee' => $user->employee,
            'isSuperAdmin' => $isSuperAdmin,
            'abilitiesData' => $abilitiesData,
            'hasDefaultSignature' => $user->hasDefaultSignature(),
        ])->layout('new.layouts.app', [
            'title' => 'Account Settings',
        ]);
    }
}
