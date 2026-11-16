<?php

declare(strict_types=1);

namespace App\Domains\Ticketing\Actions;

use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Entities\TicketActivity;
use App\Domains\Ticketing\Enums\ActivityType;
use App\Domains\Ticketing\Events\TicketCommentAdded;

class AddTicketComment
{
    public function execute(Ticket $ticket, int $userId, string $comment, ?string $attachmentPath = null): TicketActivity
    {
        $trimmed = trim($comment);
        if ($trimmed === '' && empty($attachmentPath)) {
            throw new \InvalidArgumentException('Comment or attachment cannot be empty.');
        }

        // Touch the ticket so its updated_at timestamp changes for sorting/polling
        $ticket->touch();

        $activity = TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'type' => $trimmed === '' && $attachmentPath ? ActivityType::ATTACHMENT : ActivityType::COMMENT,
            'old_state' => null,
            'new_state' => $attachmentPath,
            'reason' => $trimmed !== '' ? $trimmed : null,
        ]);

        TicketCommentAdded::dispatch($ticket, $activity, $userId);

        return $activity;
    }
}
