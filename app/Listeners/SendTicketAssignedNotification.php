<?php

namespace App\Listeners;

use App\Domains\Ticketing\Events\TicketAssigned;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Mail\TicketAssignedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTicketAssignedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TicketAssigned $event): void
    {
        $assignee = User::find($event->newAssigneeId);

        if (! $assignee || empty($assignee->email) || ! filter_var($assignee->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $assignedBy = $event->assignedByUserId ? User::find($event->assignedByUserId) : null;

        try {
            Mail::to($assignee->email)->send(new TicketAssignedMail(
                $event->ticket,
                $assignee,
                $assignedBy
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send ticket assigned notification email: ' . $e->getMessage(), [
                'ticket_id' => $event->ticket->id,
                'assignee_id' => $assignee->id,
            ]);
        }
    }
}
