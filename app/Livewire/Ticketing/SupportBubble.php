<?php

namespace App\Livewire\Ticketing;

use App\Domains\Ticketing\Actions\CreateTicket;
use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Entities\TicketCategory;
use App\Domains\Ticketing\Enums\TicketPriority;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class SupportBubble extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;

    public string $activeTab = 'new'; // 'new' or 'my_tickets'

    // New Ticket Form
    public string $issue_type = 'Software / Application Issue';

    public string $request_type = 'bug'; // 'bug', 'feature', 'other' (backwards-compatible)

    public string $custom_title = '';

    public string $title = '';

    public string $description = '';

    public ?int $category_id = null;

    public string $priority = 'Medium';

    public string $current_url = '';

    public $screenshot = null;

    // Notification Polling State
    public bool $hasUnreadUpdates = false;

    public function mount()
    {
        $this->current_url = url()->current();
    }

    #[On('open-support-bubble')]
    public function openBubble($data = [])
    {
        $this->isOpen = true;
        $this->hasUnreadUpdates = false;
        if (isset($data['tab'])) {
            $this->activeTab = $data['tab'];
        }
        if (isset($data['url']) && ! empty($data['url'])) {
            $this->current_url = $data['url'];
        } elseif (empty($this->current_url)) {
            $this->current_url = url()->previous() ?: url()->current();
        }
    }

    public function updatedIssueType($val)
    {
        if (stripos($val, 'Software') !== false) {
            $this->request_type = 'bug';
        } elseif (stripos($val, 'Inquiry') !== false || stripos($val, 'Other') !== false) {
            $this->request_type = 'other';
        } else {
            $this->request_type = 'bug';
        }
    }

    public function updatedRequestType($val)
    {
        $this->issue_type = match ($val) {
            'feature' => 'Software / Application Issue',
            'other' => 'General Inquiry / Other IT Issue',
            default => 'Software / Application Issue',
        };
    }

    public function removeScreenshot()
    {
        $this->screenshot = null;
    }

    public function resolveDefaultCategoryId(): int
    {
        $activeCategories = TicketCategory::where('is_active', true);
        $type = $this->issue_type ?: $this->request_type;

        $category = null;

        if (stripos($type, 'Hardware') !== false || stripos($type, 'Printer') !== false) {
            $category = (clone $activeCategories)->where('name', 'like', '%Hardware%')->first();
        } elseif (stripos($type, 'Network') !== false || stripos($type, 'Wi-Fi') !== false || stripos($type, 'Connectivity') !== false) {
            $category = (clone $activeCategories)->where('name', 'like', '%Network%')->first();
        } elseif ($type === 'feature' || stripos($type, 'Feature') !== false) {
            $category = (clone $activeCategories)->where('name', 'like', '%Feature%')->first();
        } elseif (stripos($type, 'Software') !== false || stripos($type, 'Application') !== false || $type === 'bug') {
            $category = (clone $activeCategories)->where('name', 'like', '%Software%')->first();
        } elseif (stripos($type, 'Inquiry') !== false || stripos($type, 'Other') !== false || $type === 'other') {
            $category = (clone $activeCategories)->where(function ($q) {
                $q->where('name', 'like', '%General%')->orWhere('name', 'like', '%Other%');
            })->first();
        }

        if (! $category) {
            $category = (clone $activeCategories)->where(function ($q) {
                $q->where('name', 'like', '%Access%')->orWhere('name', 'like', '%Permission%');
            })->first() ?? (clone $activeCategories)->first() ?? TicketCategory::first();
        }

        return $category ? $category->id : 1;
    }

    // We pull categories dynamically
    public function getCategoriesProperty()
    {
        return TicketCategory::where('is_active', true)->get();
    }

    public function getMyTicketsProperty()
    {
        $user = Auth::user();
        if (! $user || ! $user->employee) {
            return collect();
        }

        return Ticket::with('category')->where('reporter_id', $user->employee->nik)
            ->latest('updated_at')
            ->take(15)
            ->get();
    }

    public function submitTicket(CreateTicket $action)
    {
        $this->validate([
            'issue_type' => 'nullable|string|max:100',
            'request_type' => 'nullable|string|max:100',
            'custom_title' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'required|string',
            'category_id' => 'nullable|exists:ticket_categories,id',
            'priority' => 'nullable|in:Low,Medium,High,Critical',
            'current_url' => ['nullable', 'string', 'max:1000', 'regex:/^(https?:\/\/|\/)[^\s]+$/i'],
            'screenshot' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $user = Auth::user();
        if (! $user || ! $user->employee) {
            session()->flash('error', __('ticketing.concierge.messages.employee_link_required'));

            return;
        }

        $screenshotPath = null;
        if ($this->screenshot) {
            $screenshotPath = $this->screenshot->store('tickets/screenshots', 'public');
        }

        $deviceInfo = request()->header('User-Agent');
        $priorityEnum = TicketPriority::tryFrom($this->priority) ?? TicketPriority::MEDIUM;
        $resolvedCategoryId = $this->category_id ?: $this->resolveDefaultCategoryId();

        $finalTitle = trim($this->custom_title ?: $this->title);
        if (empty($finalTitle)) {
            $prefix = match ($this->request_type) {
                'feature' => '[Feature]',
                'other' => '[Support]',
                default => ! empty($this->issue_type) ? "[{$this->issue_type}]" : '[Bug]',
            };
            $snippet = Str::limit(trim($this->description), 50, '...');
            $finalTitle = "{$prefix} {$snippet}";
        }

        $action->execute(
            $user->employee->nik,
            $resolvedCategoryId,
            $finalTitle,
            $this->description,
            $priorityEnum,
            $user->email,
            $this->current_url ?: null,
            $screenshotPath,
            $deviceInfo
        );

        $this->reset(['custom_title', 'title', 'description', 'category_id', 'priority', 'screenshot']);
        $this->issue_type = 'Software / Application Issue';
        $this->request_type = 'bug';
        $this->activeTab = 'my_tickets';

        session()->flash('success', __('ticketing.concierge.messages.ticket_submitted'));
    }

    // Polled every 30s to check for new PIC activities on the user's active tickets
    public function checkUpdates()
    {
        $user = Auth::user();
        if (! $user || ! $user->employee) {
            return;
        }

        // Has any of my tickets been updated by someone else recently (e.g. last 1 min)?
        // In a true notification system, we'd have a 'read_at' pivot. For MVP KISS, we check latest activity not by the user.
        $recentUpdate = Ticket::where('reporter_id', $user->employee->nik)
            ->whereHas('activities', function ($q) use ($user) {
                $q->where('user_id', '!=', $user->id)
                    ->where('created_at', '>=', now()->subMinutes(1));
            })->exists();

        if ($recentUpdate && ! $this->isOpen) {
            $this->hasUnreadUpdates = true;
        }
    }

    public function updatedIsOpen($value)
    {
        if ($value) {
            $this->hasUnreadUpdates = false;
        }
    }

    public function toggle()
    {
        $this->isOpen = ! $this->isOpen;
        if ($this->isOpen) {
            $this->hasUnreadUpdates = false; // Mark as read when opened
        }
    }

    public function render()
    {
        return view('livewire.ticketing.support-bubble');
    }
}
