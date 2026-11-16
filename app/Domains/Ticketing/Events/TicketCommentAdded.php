<?php

declare(strict_types=1);

namespace App\Domains\Ticketing\Events;

use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Entities\TicketActivity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCommentAdded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public TicketActivity $activity,
        public int $commenterUserId
    ) {}
}
