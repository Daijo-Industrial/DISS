<?php

namespace App\Domains\Ticketing\Events;

use App\Domains\Ticketing\Entities\Ticket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Ticket $ticket;

    public ?int $oldAssigneeId;

    public int $newAssigneeId;

    public ?int $assignedByUserId;

    public function __construct(Ticket $ticket, ?int $oldAssigneeId, int $newAssigneeId, ?int $assignedByUserId = null)
    {
        $this->ticket = $ticket;
        $this->oldAssigneeId = $oldAssigneeId;
        $this->newAssigneeId = $newAssigneeId;
        $this->assignedByUserId = $assignedByUserId;
    }
}
