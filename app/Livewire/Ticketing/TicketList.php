<?php

declare(strict_types=1);

namespace App\Livewire\Ticketing;

use App\Domains\Ticketing\Actions\AssignTicketToPIC;
use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Entities\TicketCategory;
use App\Domains\Ticketing\Enums\TicketPriority;
use App\Domains\Ticketing\Enums\TicketStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class TicketList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $priorityFilter = '';

    public string $categoryFilter = '';

    public string $scopeFilter = 'all'; // 'all', 'assigned_to_me', 'unassigned'

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingScopeFilter(): void
    {
        $this->resetPage();
    }

    public function setScope(string $scope): void
    {
        $this->scopeFilter = $scope;
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = ($this->statusFilter === $status) ? '' : $status;
        $this->resetPage();
    }

    public function setPriorityFilter(string $priority): void
    {
        $this->priorityFilter = ($this->priorityFilter === $priority) ? '' : $priority;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'priorityFilter', 'categoryFilter', 'scopeFilter']);
        $this->resetPage();
    }

    public function claimTicket(int $ticketId, AssignTicketToPIC $action): void
    {
        $user = Auth::user();
        $canManage = $user && (
            $user->hasRole('super-admin')
            || $user->hasRole('admin')
            || $user->hasAnyPermission(['ticketing.manage', 'ticketing.admin', 'system.admin'])
        );

        if (! $canManage) {
            abort(403);
        }

        $ticket = Ticket::findOrFail($ticketId);
        $action->execute($ticket, $user, $user->id);
        session()->flash('success', __('ticketing.list.claimed_success', ['ticket' => $ticket->ticket_number]));
    }

    public function gotoDetail(int|string $id): void
    {
        $this->redirectRoute('ticketing.show', ['ticket' => $id]);
    }

    #[Layout('new.layouts.app')]
    public function render(): View
    {
        $user = Auth::user();
        $canManage = $user && (
            $user->hasRole('super-admin')
            || $user->hasRole('admin')
            || $user->hasAnyPermission(['ticketing.manage', 'ticketing.admin', 'system.admin'])
        );

        $baseQuery = Ticket::query()
            ->when(! $canManage, function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $nik = $user?->employee?->nik;
                    $email = $user?->email;

                    if (! $nik && ! $email) {
                        $sub->whereRaw('0 = 1');

                        return;
                    }

                    if ($nik) {
                        $sub->where('reporter_id', $nik);
                    }
                    if ($email) {
                        $sub->orWhere('reporter_email', $email);
                    }
                });
            });

        // Single consolidated aggregate query for scope and status pill counts
        $openStatus = TicketStatus::OPEN->value;
        $inProgressStatus = TicketStatus::IN_PROGRESS->value;
        $onHoldStatus = TicketStatus::ON_HOLD->value;
        $resolvedStatus = TicketStatus::RESOLVED->value;
        $closedStatus = TicketStatus::CLOSED->value;
        $criticalPriority = TicketPriority::CRITICAL->value;

        $aggregates = (clone $baseQuery)
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN assigned_to = ? THEN 1 END) as assigned_to_me,
                COUNT(CASE WHEN assigned_to IS NULL THEN 1 END) as unassigned,
                COUNT(CASE WHEN status = '{$openStatus}' THEN 1 END) as open_count,
                COUNT(CASE WHEN status = '{$inProgressStatus}' THEN 1 END) as in_progress_count,
                COUNT(CASE WHEN status = '{$onHoldStatus}' THEN 1 END) as on_hold_count,
                COUNT(CASE WHEN status = '{$resolvedStatus}' THEN 1 END) as resolved_count,
                COUNT(CASE WHEN status = '{$closedStatus}' THEN 1 END) as closed_count,
                COUNT(CASE WHEN priority = '{$criticalPriority}' THEN 1 END) as critical_count
            ", [$user?->id ?? 0])
            ->first();

        $counts = [
            'all' => (int) ($aggregates->total ?? 0),
            'assigned_to_me' => $canManage ? (int) ($aggregates->assigned_to_me ?? 0) : 0,
            'unassigned' => $canManage ? (int) ($aggregates->unassigned ?? 0) : 0,
            'open' => (int) ($aggregates->open_count ?? 0),
            'in_progress' => (int) ($aggregates->in_progress_count ?? 0),
            'on_hold' => (int) ($aggregates->on_hold_count ?? 0),
            'resolved' => (int) ($aggregates->resolved_count ?? 0),
            'closed' => (int) ($aggregates->closed_count ?? 0),
            'critical' => (int) ($aggregates->critical_count ?? 0),
        ];

        $query = (clone $baseQuery)->with(['reporter.department', 'assignee', 'category'])
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                $q->where(function ($sub) use ($term) {
                    $sub->where('title', 'like', "%{$term}%")
                        ->orWhere('ticket_number', 'like', "%{$term}%")
                        ->orWhere('reporter_id', 'like', "%{$term}%")
                        ->orWhere('reporter_email', 'like', "%{$term}%")
                        ->orWhereHas('reporter', function ($repQ) use ($term) {
                            $repQ->where('name', 'like', "%{$term}%");
                        });
                });
            })
            ->when($this->scopeFilter === 'assigned_to_me' && $user !== null, fn ($q) => $q->where('assigned_to', $user?->id))
            ->when($this->scopeFilter === 'unassigned', fn ($q) => $q->whereNull('assigned_to'))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter, fn ($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->latest('created_at');

        return view('livewire.ticketing.ticket-list', [
            'tickets' => $query->paginate(15),
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
            'categories' => TicketCategory::where('is_active', true)->orderBy('name')->get(),
            'canManage' => $canManage,
            'counts' => $counts,
        ]);
    }
}
