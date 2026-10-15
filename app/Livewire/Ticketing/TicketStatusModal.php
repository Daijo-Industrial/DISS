<?php

namespace App\Livewire\Ticketing;

use App\Domains\Ticketing\Actions\TransitionTicketStatus;
use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Enums\TicketStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class TicketStatusModal extends Component
{
    public ?Ticket $ticket = null;

    public string $newStatus = '';

    public string $reason = '';

    public bool $isOpen = false;

    private function canManageTicket(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('super-admin')
            || $user->hasRole('admin')
            || $user->hasAnyPermission(['ticketing.manage', 'ticketing.admin', 'system.admin']);
    }

    #[On('openStatusModal')]
    public function open($ticket)
    {
        if (! $this->canManageTicket()) {
            return;
        }

        $this->ticket = Ticket::find($ticket);
        if (! $this->ticket) {
            return;
        }

        $this->newStatus = $this->ticket->status->value;
        $this->reason = '';
        $this->isOpen = true;
    }

    public function save(TransitionTicketStatus $action)
    {
        if (! $this->canManageTicket()) {
            abort(403);
        }

        $this->validate([
            'newStatus' => 'required',
            'reason' => 'required_if:newStatus,On Hold',
        ]);

        $statusEnum = TicketStatus::from($this->newStatus);

        $action->execute($this->ticket, $statusEnum, Auth::id(), $this->reason ?: null);

        $this->isOpen = false;
        $this->dispatch('ticketUpdated');
    }

    public function render()
    {
        return view('livewire.ticketing.ticket-status-modal', [
            'statuses' => TicketStatus::cases(),
        ]);
    }
}
