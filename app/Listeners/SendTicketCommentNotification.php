<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domains\Ticketing\Events\TicketCommentAdded;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Mail\TicketCommentMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTicketCommentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TicketCommentAdded $event): void
    {
        $ticket = $event->ticket;
        $activity = $event->activity;
        $commenter = ($activity->relationLoaded('user') && $activity->user)
            ? $activity->user
            : User::find($event->commenterUserId);
        $authorName = $commenter?->name ?? 'User';

        // Role-Asymmetric Notification Strategy:
        // 1. Requesters (office & plant staff) do not monitor DISS constantly, so they receive immediate
        //    email notifications whenever an IT technician replies or asks questions.
        // 2. IT Technicians actively work inside DISS and receive the scheduled 07:30 AM Morning Digest,
        //    so routine back-and-forth chat replies are suppressed to prevent inbox flooding.

        $reporterUser = null;
        if ($ticket->relationLoaded('reporter') && $ticket->reporter) {
            $reporterUser = ($ticket->reporter->relationLoaded('user') && $ticket->reporter->user)
                ? $ticket->reporter->user
                : User::where('employee_id', $ticket->reporter->id)->first();
        }

        $isCommenterRequester = ($reporterUser && $reporterUser->id === (int) $event->commenterUserId)
            || ($ticket->reporter_id && $commenter?->employee?->nik === $ticket->reporter_id)
            || ($ticket->reporter_email && $commenter?->email === $ticket->reporter_email);

        // Only send email if the reply was posted by IT / non-requester
        if (! $isCommenterRequester) {
            $requesterEmail = $ticket->reporter_email;
            if (empty($requesterEmail) && $reporterUser) {
                $requesterEmail = $reporterUser->email;
            }

            if (! empty($requesterEmail) && filter_var($requesterEmail, FILTER_VALIDATE_EMAIL)) {
                $requesterName = optional($ticket->reporter)->name
                    ?? optional($ticket->reporter)->Nama
                    ?? $reporterUser?->name
                    ?? 'Colleague';

                try {
                    Mail::to($requesterEmail)->send(new TicketCommentMail(
                        $ticket,
                        $activity,
                        $requesterName,
                        $authorName
                    ));
                } catch (\Throwable $e) {
                    Log::error('Failed to send comment notification to requester: ' . $e->getMessage(), [
                        'ticket_id' => $ticket->id,
                        'email' => $requesterEmail,
                    ]);
                }
            }
        }
    }
}
