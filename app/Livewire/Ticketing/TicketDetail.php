<?php

namespace App\Livewire\Ticketing;

use App\Domains\Ticketing\Actions\AddTicketComment;
use App\Domains\Ticketing\Actions\AssignTicketToPIC;
use App\Domains\Ticketing\Entities\Ticket;
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

    public function mount(Ticket $ticket)
    {
        $this->ticket = $ticket;
        $this->githubIssueUrlInput = $ticket->github_issue_url ?? '';
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

        session()->flash('success', 'GitHub issue link updated successfully.');
    }

    public function getGitHubIssueUrl(): string
    {
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

        return 'https://github.com/Daijo-Industrial/DISS/issues/new?' . http_build_query($params);
    }

    #[Layout('new.layouts.app')]
    public function render()
    {
        return view('livewire.ticketing.ticket-detail', [
            'ticketData' => Ticket::with(['reporter', 'assignee', 'category', 'activities.user'])
                ->findOrFail($this->ticket->id),
        ]);
    }
}
