<?php

namespace App\Livewire\Ticketing;

use App\Domains\Ticketing\Actions\AddTicketComment;
use App\Domains\Ticketing\Actions\AssignTicketToPIC;
use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Entities\TicketActivity;
use App\Domains\Ticketing\Entities\TicketCategory;
use App\Domains\Ticketing\Enums\ActivityType;
use App\Domains\Ticketing\Enums\TicketPriority;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class TicketDetail extends Component
{
    public Ticket $ticket;

    public string $githubIssueUrlInput = '';

    // Refresh when modal updates status
    protected $listeners = ['ticketUpdated' => '$refresh'];

    public string $newComment = '';

    public bool $canManage = false;

    public function mount(Ticket $ticket)
    {
        $this->ticket = $ticket;
        $this->githubIssueUrlInput = $ticket->github_issue_url ?? '';
        $this->canManage = $this->canManageTicket();
    }

    public function assignToMe(AssignTicketToPIC $action)
    {
        $action->execute($this->ticket, Auth::user(), Auth::id());
        $this->ticket->refresh();
    }

    public function postComment(AddTicketComment $action)
    {
        $this->validate(['newComment' => 'required|string']);

        $action->execute($this->ticket, Auth::id(), $this->newComment);

        $this->newComment = '';
        $this->ticket->refresh();
    }

    public function saveGithubIssueUrl()
    {
        $this->validate([
            'githubIssueUrlInput' => 'nullable|url|max:255',
        ]);

        $this->ticket->github_issue_url = $this->githubIssueUrlInput ?: null;
        $this->ticket->save();

        session()->flash('success', __('ticketing.detail.github_url_updated'));
    }

    public function getGitHubIssueUrl(): string
    {
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

    public function updateCategory(int $newCategoryId)
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
        session()->flash('success', __('ticketing.detail.category_updated', ['category' => $category->name]));
    }

    public function updatePriority(string $newPriority)
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
        session()->flash('success', __('ticketing.detail.priority_updated', ['priority' => $priorityEnum->value]));
    }

    #[Layout('new.layouts.app')]
    public function render()
    {
        return view('livewire.ticketing.ticket-detail', [
            'ticketData' => Ticket::with(['reporter', 'assignee', 'category', 'activities.user'])
                ->findOrFail($this->ticket->id),
            'allCategories' => TicketCategory::where('is_active', true)->get(),
            'canManage' => $this->canManageTicket(),
        ]);
    }
}
