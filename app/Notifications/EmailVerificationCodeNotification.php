<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly string $newEmail,
        public readonly string $userName
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("[DISS] Email Verification Code: {$this->code}")
            ->greeting("Hello, {$this->userName}")
            ->line("You requested to verify your email address: {$this->newEmail}.")
            ->line('Your 6-digit email verification code is:')
            ->line("# **{$this->code}**")
            ->line('This verification code is valid for 15 minutes. Please enter it in the prompt to complete your email verification.')
            ->line('If you did not request this verification, please ignore this message or inform your system administrator immediately.')
            ->salutation("Regards,\nDaijo Industrial Support System");

        return $message;
    }
}
