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
    public string $request_type = 'bug'; // 'bug', 'feature', 'other'

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

    public function removeScreenshot()
    {
        $this->screenshot = null;
    }

    public function resolveDefaultCategoryId(): int
    {
        $targetName = match ($this->request_type) {
            'feature' => 'Feature Request',
            'other' => 'General Inquiry / Other',
            default => 'Software / Application Bug',
        };

        $category = TicketCategory::where('name', $targetName)->first();

        if (! $category) {
            $category = TicketCategory::where('is_active', true)->first();
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
            'request_type' => 'required|in:bug,feature,other',
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
                default => '[Bug]',
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

        $this->reset(['request_type', 'custom_title', 'title', 'description', 'category_id', 'priority', 'screenshot']);
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
