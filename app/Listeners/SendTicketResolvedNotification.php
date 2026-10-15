<?php

namespace App\Listeners;

use App\Domains\Ticketing\Events\TicketResolved;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Mail\TicketStatusUpdatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTicketResolvedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TicketResolved $event): void
    {
        $ticket = $event->ticket;
        $recipientEmail = $ticket->reporter_email;

        // If no explicit reporter_email, check if employee has an associated user account
        if (empty($recipientEmail) && $ticket->reporter) {
            $user = User::where('employee_id', $ticket->reporter->id)->first();
            if ($user && $user->email) {
                $recipientEmail = $user->email;
            }
        }

        if (empty($recipientEmail) || ! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($recipientEmail)->send(new TicketStatusUpdatedMail(
                $ticket,
                $ticket->status->value ?? 'Resolved',
                'Your ticket has been marked as resolved by the IT team.'
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send ticket resolved email: ' . $e->getMessage(), [
                'ticket_id' => $ticket->id,
            ]);
        }
    }
}
