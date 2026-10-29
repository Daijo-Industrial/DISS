<?php

declare(strict_types=1);

namespace App\Livewire\Ticketing;

use App\Domains\Ticketing\Actions\TransitionTicketStatus;
use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Enums\TicketStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
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
    public function open(int|string|Ticket $ticket, ?string $targetStatus = null): void
    {
        if (! $this->canManageTicket()) {
            return;
        }

        $this->ticket = $ticket instanceof Ticket ? $ticket : Ticket::find($ticket);
        if (! $this->ticket) {
            return;
        }

        $this->newStatus = $targetStatus ?: $this->ticket->status->value;
        $this->reason = '';
        $this->isOpen = true;
    }

    public function save(TransitionTicketStatus $action): void
    {
        if (! $this->canManageTicket()) {
            abort(403);
        }

        if (! $this->ticket) {
            abort(404);
        }

        $this->validate([
            'newStatus' => ['required', new Enum(TicketStatus::class)],
            'reason' => 'required_if:newStatus,On Hold',
        ]);

        $statusEnum = TicketStatus::from($this->newStatus);

        $action->execute($this->ticket, $statusEnum, (int) Auth::id(), $this->reason ?: null);

        $this->isOpen = false;
        $this->dispatch('ticketUpdated');
    }

    public function render(): View
    {
        return view('livewire.ticketing.ticket-status-modal', [
            'statuses' => TicketStatus::cases(),
        ]);
    }
}
