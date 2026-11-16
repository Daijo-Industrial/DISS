<?php

declare(strict_types=1);

namespace App\Livewire\Ticketing;

use App\Domains\Ticketing\Actions\AssignTicketToPIC;
use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Entities\TicketCategory;
use App\Domains\Ticketing\Enums\TicketStatus;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TicketList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'category')]
    public string $categoryFilter = '';

    #[Url(as: 'scope')]
    public string $scopeFilter = 'unassigned'; // 'unassigned', 'assigned_to_me', 'all'

    #[Url(as: 'mode')]
    public string $viewMode = ''; // 'self_service' | 'agent_queue'

    #[Url(as: 'tab')]
    public string $selfServiceTab = 'active'; // 'active' | 'past'

    public bool $showAdvancedFilters = false;

    public function mount(): void
    {
        $user = Auth::user();
        $canManage = $this->canUserManage($user);

        if (! $canManage) {
            $this->viewMode = 'self_service';
        } elseif (empty($this->viewMode)) {
            $this->viewMode = 'agent_queue';
        }
    }

    protected function canUserManage(?User $user): bool
    {
        return (bool) ($user && (
            $user->hasRole('super-admin')
            || $user->hasRole('admin')
            || $user->hasAnyPermission(['ticketing.manage', 'ticketing.admin', 'system.admin'])
        ));
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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

    public function updatingSelfServiceTab(): void
    {
        $this->resetPage();
    }

    public function toggleAdvancedFilters(): void
    {
        $this->showAdvancedFilters = ! $this->showAdvancedFilters;
    }

    public function setViewMode(string $mode): void
    {
        $user = Auth::user();
        if ($mode === 'agent_queue' && ! $this->canUserManage($user)) {
            $this->viewMode = 'self_service';

            return;
        }

        $this->viewMode = $mode;
        $this->resetPage();
    }

    public function setSelfServiceTab(string $tab): void
    {
        $this->selfServiceTab = in_array($tab, ['active', 'past'], true) ? $tab : 'active';
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

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'categoryFilter']);
        $this->scopeFilter = 'unassigned';
        $this->resetPage();
    }

    public function claimTicket(int $ticketId, AssignTicketToPIC $action): void
    {
        $user = Auth::user();
        $canManage = $this->canUserManage($user);

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
        $canManage = $this->canUserManage($user);

        // Enforce self_service for non-management
        $effectiveMode = ($canManage && $this->viewMode === 'agent_queue') ? 'agent_queue' : 'self_service';

        $openStatus = TicketStatus::OPEN->value;
        $inProgressStatus = TicketStatus::IN_PROGRESS->value;
        $onHoldStatus = TicketStatus::ON_HOLD->value;
        $resolvedStatus = TicketStatus::RESOLVED->value;
        $closedStatus = TicketStatus::CLOSED->value;

        // Base query closure for current user's personal tickets
        $applyUserScope = function ($q) use ($user): void {
            $q->where(function ($sub) use ($user): void {
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
        };

        // Self-Service counts (Active vs Past)
        $userTicketsBase = Ticket::query();
        $applyUserScope($userTicketsBase);

        $selfServiceAggregates = (clone $userTicketsBase)
            ->selectRaw("
                COUNT(CASE WHEN status IN ('{$openStatus}', '{$inProgressStatus}', '{$onHoldStatus}') THEN 1 END) as active_count,
                COUNT(CASE WHEN status IN ('{$resolvedStatus}', '{$closedStatus}') THEN 1 END) as past_count
            ")->first();

        $selfServiceCounts = [
            'active' => (int) ($selfServiceAggregates->active_count ?? 0),
            'past' => (int) ($selfServiceAggregates->past_count ?? 0),
        ];

        // IT Agent Queue counts
        $counts = [
            'all' => 0,
            'assigned_to_me' => 0,
            'unassigned' => 0,
            'open' => 0,
            'in_progress' => 0,
            'on_hold' => 0,
            'resolved' => 0,
            'closed' => 0,
            'critical' => 0,
        ];

        if ($canManage) {
            $queueAggregates = Ticket::query()
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(CASE WHEN assigned_to = ? AND status IN ('{$openStatus}', '{$inProgressStatus}', '{$onHoldStatus}') THEN 1 END) as assigned_to_me,
                    COUNT(CASE WHEN assigned_to IS NULL AND status IN ('{$openStatus}', '{$inProgressStatus}', '{$onHoldStatus}') THEN 1 END) as unassigned,
                    COUNT(CASE WHEN status IN ('{$openStatus}', '{$inProgressStatus}', '{$onHoldStatus}') THEN 1 END) as all_active,
                    COUNT(CASE WHEN status = '{$openStatus}' THEN 1 END) as open_count,
                    COUNT(CASE WHEN status = '{$inProgressStatus}' THEN 1 END) as in_progress_count,
                    COUNT(CASE WHEN status = '{$onHoldStatus}' THEN 1 END) as on_hold_count,
                    COUNT(CASE WHEN status = '{$resolvedStatus}' THEN 1 END) as resolved_count,
                    COUNT(CASE WHEN status = '{$closedStatus}' THEN 1 END) as closed_count
                ", [$user?->id ?? 0])
                ->first();

            $counts = [
                'total' => (int) ($queueAggregates->total ?? 0),
                'assigned_to_me' => (int) ($queueAggregates->assigned_to_me ?? 0),
                'unassigned' => (int) ($queueAggregates->unassigned ?? 0),
                'all_active' => (int) ($queueAggregates->all_active ?? 0),
                'open' => (int) ($queueAggregates->open_count ?? 0),
                'in_progress' => (int) ($queueAggregates->in_progress_count ?? 0),
                'on_hold' => (int) ($queueAggregates->on_hold_count ?? 0),
                'resolved' => (int) ($queueAggregates->resolved_count ?? 0),
                'closed' => (int) ($queueAggregates->closed_count ?? 0),
            ];
        }

        // Build main ticket query
        $query = Ticket::query()->with(['reporter.department', 'assignee', 'category', 'latestActivity.user'])->withCount('activities');

        if ($effectiveMode === 'self_service') {
            $applyUserScope($query);

            if ($this->selfServiceTab === 'past') {
                $query->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED]);
            } else {
                $query->whereIn('status', [TicketStatus::OPEN, TicketStatus::IN_PROGRESS, TicketStatus::ON_HOLD]);
            }

            if ($this->search) {
                $term = trim($this->search);
                $query->where(function ($sub) use ($term): void {
                    $sub->where('title', 'like', "%{$term}%")
                        ->orWhere('ticket_number', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            }
        } else {
            // Agent queue mode (Action-focused: defaults to unassigned active tickets)
            $query->when($this->search, function ($q): void {
                $term = trim($this->search);
                $q->where(function ($sub) use ($term): void {
                    $sub->where('title', 'like', "%{$term}%")
                        ->orWhere('ticket_number', 'like', "%{$term}%")
                        ->orWhere('reporter_id', 'like', "%{$term}%")
                        ->orWhere('reporter_email', 'like', "%{$term}%")
                        ->orWhereHas('reporter', function ($repQ) use ($term): void {
                            $repQ->where('name', 'like', "%{$term}%");
                        });
                });
            })
                ->when($this->scopeFilter === 'assigned_to_me' && $user !== null, fn ($q) => $q->where('assigned_to', $user?->id))
                ->when($this->scopeFilter === 'unassigned', fn ($q) => $q->whereNull('assigned_to'))
                ->when($this->statusFilter && $this->statusFilter !== 'all_history', fn ($q) => $q->where('status', $this->statusFilter))
                ->when(! $this->statusFilter, fn ($q) => $q->whereIn('status', [TicketStatus::OPEN, TicketStatus::IN_PROGRESS, TicketStatus::ON_HOLD]))
                ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter));
        }

        $tickets = $query->latest('created_at')->paginate(12);

        return view('livewire.ticketing.ticket-list', [
            'tickets' => $tickets,
            'statuses' => TicketStatus::cases(),
            'categories' => TicketCategory::where('is_active', true)->orderBy('name')->get(),
            'canManage' => $canManage,
            'effectiveMode' => $effectiveMode,
            'counts' => $counts,
            'selfServiceCounts' => $selfServiceCounts,
            'showAdvancedFilters' => $this->showAdvancedFilters,
        ]);
    }
}
