<?php

declare(strict_types=1);

namespace App\Livewire\Ticketing;

use App\Domains\Ticketing\Actions\AddTicketComment;
use App\Domains\Ticketing\Actions\AssignTicketToPIC;
use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Entities\TicketActivity;
use App\Domains\Ticketing\Entities\TicketCategory;
use App\Domains\Ticketing\Enums\ActivityType;
use App\Domains\Ticketing\Enums\TicketPriority;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class TicketDetail extends Component
{
    public Ticket $ticket;

    public string $githubIssueUrlInput = '';

    public string $newComment = '';

    #[Locked]
    public bool $canManage = false;

    public ?int $customSlaMinutes = null;

    #[On('ticketUpdated')]
    public function onTicketUpdated(): void
    {
        $this->ticket->refresh();
        $this->dispatch('chat-scrolled');
    }

    public function mount(Ticket $ticket): void
    {
        $this->ticket = $ticket;
        $this->canManage = $this->canManageTicket();

        $user = Auth::user();
        $isReporter = $user && (
            ($user->employee && $user->employee->nik === $ticket->reporter_id)
            || ($ticket->reporter_email && $user->email === $ticket->reporter_email)
            || ($user->id === $ticket->assigned_to)
        );

        if (! $isReporter && ! $this->canManage) {
            abort(403);
        }

        $this->githubIssueUrlInput = $ticket->github_issue_url ?? '';
        $this->customSlaMinutes = $this->ticket->getEffectiveSlaMinutes();
    }

    public function assignToMe(AssignTicketToPIC $action): void
    {
        if (! $this->canManageTicket()) {
            abort(403);
        }

        $action->execute($this->ticket, Auth::user(), (int) Auth::id());
        $this->ticket->refresh();
        $this->dispatch('chat-scrolled');
    }

    public function postComment(AddTicketComment $action): void
    {
        $user = Auth::user();
        $isReporter = $user && (
            ($user->employee && $user->employee->nik === $this->ticket->reporter_id)
            || ($this->ticket->reporter_email && $user->email === $this->ticket->reporter_email)
            || ($user->id === $this->ticket->assigned_to)
        );

        if (! $isReporter && ! $this->canManageTicket()) {
            abort(403);
        }

        $this->validate(['newComment' => 'required|string']);

        $action->execute($this->ticket, (int) Auth::id(), $this->newComment);

        $this->newComment = '';
        $this->ticket->refresh();
        $this->dispatch('chat-scrolled');
    }

    public function saveGithubIssueUrl(): void
    {
        if (! $this->canManageTicket()) {
            abort(403);
        }

        $this->validate([
            'githubIssueUrlInput' => [
                'nullable',
                'url',
                'max:255',
                'regex:/^https:\/\/github\.com\/[a-zA-Z0-9_\-\.\/]+$/',
            ],
        ]);

        $this->ticket->github_issue_url = $this->githubIssueUrlInput ?: null;
        $this->ticket->save();

        session()->flash('success', __('ticketing.detail.github_url_updated'));
    }

    public function getGitHubIssueUrl(): string
    {
        if (! $this->canManageTicket()) {
            return '#';
        }

        $repoUrl = config('services.github.repo_url');
        if (! $repoUrl) {
            return '#';
        }

        $categoryName = $this->ticket->category->name ?? '';
        $isFeature = str_contains(strtolower($categoryName), 'feature');

        if ($isFeature) {
            $params = [
                'template' => 'feature_request.yml',
                'title' => '[Feature]: ' . $this->ticket->title,
                'problem_statement' => "From DISS Ticket #{$this->ticket->ticket_number}\n\n" . $this->ticket->description,
                'proposed_solution' => "Proposed enhancement based on Ticket #{$this->ticket->ticket_number}.",
            ];
        } else {
            $contextLines = [];
            if ($this->ticket->reporter_email) {
                $contextLines[] = "Reporter Email: {$this->ticket->reporter_email}";
            }
            if ($this->ticket->device_info) {
                $contextLines[] = "Device Info: {$this->ticket->device_info}";
            }
            if ($this->ticket->screenshot_path) {
                $contextLines[] = 'Screenshot attached in DISS: ' . asset('storage/' . $this->ticket->screenshot_path);
            }

            $params = [
                'template' => 'bug_report.yml',
                'title' => '[Bug]: ' . $this->ticket->title,
                'url_or_route' => $this->ticket->current_url ?? '',
                'user_role' => optional($this->ticket->reporter)->Nama ? (optional($this->ticket->reporter)->Nama . ' (NIK: ' . $this->ticket->reporter_id . ')') : ($this->ticket->reporter_id ?? ''),
                'description' => "From DISS Ticket #{$this->ticket->ticket_number}\n\n" . $this->ticket->description,
                'reproduction_steps' => "1. Refer to DISS Ticket #{$this->ticket->ticket_number}\n2. See details in description.",
                'expected_behavior' => 'Normal error-free behavior.',
                'additional_context' => implode("\n", $contextLines),
            ];
        }

        return rtrim($repoUrl, '/') . '/issues/new?' . http_build_query($params);
    }

    public function canManageTicket(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('super-admin')
            || $user->hasRole('admin')
            || $user->hasAnyPermission(['ticketing.manage', 'ticketing.admin', 'system.admin']);
    }

    public function updateCategory(int $newCategoryId): void
    {
        if (! $this->canManageTicket()) {
            abort(403);
        }

        $category = TicketCategory::findOrFail($newCategoryId);
        $oldCategory = $this->ticket->category->name ?? 'None';
        $this->ticket->category_id = $category->id;
        $this->ticket->save();

        TicketActivity::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => Auth::id(),
            'type' => ActivityType::STATUS_CHANGE,
            'old_state' => $oldCategory,
            'new_state' => $category->name,
            'reason' => 'Category triaged by IT Support.',
        ]);

        $this->ticket->refresh();
        $this->dispatch('chat-scrolled');
        session()->flash('success', __('ticketing.detail.category_updated', ['category' => $category->name]));
    }

    public function updatePriority(string $newPriority): void
    {
        if (! $this->canManageTicket()) {
            abort(403);
        }

        $priorityEnum = TicketPriority::from($newPriority);
        $oldPriority = $this->ticket->priority->value;
        $this->ticket->priority = $priorityEnum;
        $this->ticket->save();

        TicketActivity::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => Auth::id(),
            'type' => ActivityType::STATUS_CHANGE,
            'old_state' => $oldPriority,
            'new_state' => $priorityEnum->value,
            'reason' => 'Priority triaged by IT Support.',
        ]);

        $this->ticket->refresh();
        $this->dispatch('chat-scrolled');
        session()->flash('success', __('ticketing.detail.priority_updated', ['priority' => $priorityEnum->value]));
    }

    public function updateSlaMinutes(int $slaMinutes): void
    {
        if (! $this->canManageTicket()) {
            abort(403);
        }

        if ($slaMinutes < 1 || $slaMinutes > 43200) {
            $this->addError('customSlaMinutes', __('ticketing.detail.invalid_sla_minutes'));

            return;
        }

        $oldSla = $this->ticket->sla_minutes;
        $this->ticket->sla_minutes = $slaMinutes;
        $this->ticket->save();
        $this->customSlaMinutes = $slaMinutes;

        TicketActivity::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => Auth::id(),
            'type' => ActivityType::STATUS_CHANGE,
            'old_state' => $oldSla ? "{$oldSla} " . __('ticketing.detail.minutes') : __('ticketing.detail.not_set'),
            'new_state' => "{$slaMinutes} " . __('ticketing.detail.minutes'),
            'reason' => 'SLA target adjusted by IT Support.',
        ]);

        $this->ticket->refresh();
        $this->dispatch('chat-scrolled');
        session()->flash('success', __('ticketing.detail.sla_minutes_updated', ['minutes' => $slaMinutes]));
    }

    public function saveSlaMinutes(): void
    {
        if (! $this->canManageTicket()) {
            abort(403);
        }

        $this->validate([
            'customSlaMinutes' => 'required|integer|min:1|max:43200',
        ]);

        $this->updateSlaMinutes((int) $this->customSlaMinutes);
    }

    public function setSlaPreset(int $minutes): void
    {
        $this->updateSlaMinutes($minutes);
    }

    public function reassignToUser(int $userId, AssignTicketToPIC $action): void
    {
        if (! $this->canManageTicket()) {
            abort(403);
        }

        $newAssignee = User::findOrFail($userId);
        $action->execute($this->ticket, $newAssignee, (int) Auth::id());
        $this->ticket->refresh();
        $this->dispatch('chat-scrolled');
        session()->flash('success', __('ticketing.detail.reassigned_success', ['name' => $newAssignee->name]));
    }

    #[Layout('new.layouts.app')]
    public function render(): View
    {
        $itStaff = collect();
        if ($this->canManageTicket()) {
            $itStaff = User::query()
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->role(['super-admin', 'admin'])
                        ->orWhere(fn ($sq) => $sq->permission(['ticketing.manage', 'ticketing.admin', 'system.admin']));
                })
                ->orderBy('name')
                ->get();
        }

        return view('livewire.ticketing.ticket-detail', [
            'ticketData' => Ticket::with(['reporter.department', 'assignee', 'category', 'activities.user'])
                ->findOrFail($this->ticket->id),
            'allCategories' => TicketCategory::where('is_active', true)->get(),
            'itStaff' => $itStaff,
            'canManage' => $this->canManageTicket(),
        ]);
    }
}
