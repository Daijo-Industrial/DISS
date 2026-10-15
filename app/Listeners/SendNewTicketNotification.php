<?php

namespace App\Listeners;

use App\Domains\Ticketing\Events\TicketCreated;
use App\Mail\NewTicketSubmittedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewTicketNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TicketCreated $event): void
    {
        $supportEmail = config('mail.support_address');

        if (empty($supportEmail) || ! filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($supportEmail)->send(new NewTicketSubmittedMail($event->ticket));
        } catch (\Throwable $e) {
            Log::error('Failed to send new ticket notification email: ' . $e->getMessage(), [
                'ticket_id' => $event->ticket->id,
            ]);
        }
    }
}
