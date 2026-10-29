<?php

declare(strict_types=1);

namespace App\Domains\Ticketing\Entities;

use App\Domains\Ticketing\Enums\TicketPriority;
use App\Domains\Ticketing\Enums\TicketStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Employee;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tickets';

    protected $fillable = [
        'ticket_number',
        'reporter_id',
        'reporter_email',
        'assigned_to',
        'category_id',
        'title',
        'description',
        'current_url',
        'screenshot_path',
        'device_info',
        'status',
        'priority',
        'first_response_at',
        'resolved_at',
        'on_hold_since',
        'total_hold_time_minutes',
        'reopen_count',
        'github_issue_url',
        'sla_minutes',
    ];

    protected $casts = [
        'status' => TicketStatus::class,
        'priority' => TicketPriority::class,
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'on_hold_since' => 'datetime',
        'total_hold_time_minutes' => 'integer',
        'reopen_count' => 'integer',
        'sla_minutes' => 'integer',
    ];

    // Relationships
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reporter_id', 'nik');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class, 'ticket_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get a human-readable duration of total time on hold.
     */
    public function getFormattedHoldTime(): string
    {
        $minutes = $this->total_hold_time_minutes;

        // If currently on hold, add the elapsed time from on_hold_since
        if ($this->status === TicketStatus::ON_HOLD && $this->on_hold_since) {
            $minutes += now()->diffInMinutes($this->on_hold_since);
        }

        if ($minutes <= 0) {
            return '0m';
        }

        return CarbonInterval::minutes($minutes)->cascade()->forHumans(['short' => true]);
    }

    /**
     * Get effective SLA in minutes (custom SLA if triaged by IT, fallback to category default).
     */
    public function getEffectiveSlaMinutes(): ?int
    {
        if ($this->sla_minutes !== null) {
            return $this->sla_minutes;
        }

        if ($this->category?->sla_hours) {
            return $this->category->sla_hours * 60;
        }

        return null;
    }

    /**
     * Get effective SLA hours.
     */
    public function getEffectiveSlaHours(): ?float
    {
        $minutes = $this->getEffectiveSlaMinutes();

        return $minutes ? round($minutes / 60, 2) : null;
    }

    /**
     * Get human-formatted SLA target string (e.g. "30 Minutes", "2 Hours", "1h 30m").
     */
    public function getFormattedSlaTarget(): string
    {
        $minutes = $this->getEffectiveSlaMinutes();
        if (! $minutes) {
            return __('ticketing.detail.sla_pending');
        }

        if ($minutes < 60) {
            return "{$minutes} " . __('ticketing.detail.minutes');
        }

        $hours = floor($minutes / 60);
        $rem = $minutes % 60;
        if ($rem === 0) {
            return "{$hours} " . __('ticketing.detail.hours');
        }

        return "{$hours}h {$rem}m";
    }

    /**
     * Get target due timestamp based on effective SLA minutes.
     */
    public function getSlaDueAt(): ?Carbon
    {
        $minutes = $this->getEffectiveSlaMinutes();
        if (! $minutes || ! $this->created_at) {
            return null;
        }

        return $this->created_at->copy()->addMinutes($minutes);
    }

    /**
     * Get real-time SLA urgency status: 'completed', 'paused', 'breached', 'due_soon', 'on_track', 'pending'.
     */
    public function getSlaStatus(): array
    {
        if (in_array($this->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED], true)) {
            $diff = $this->resolved_at ? $this->resolved_at->diffForHumans() : null;

            return [
                'state' => 'completed',
                'label' => __('ticketing.sla.completed'),
                'color' => 'bg-slate-100 text-slate-600 border-slate-200',
                'badge' => 'bg-slate-100 text-slate-600 border-slate-200',
                'time_diff' => $diff,
                'remaining' => $diff,
            ];
        }

        if ($this->status === TicketStatus::ON_HOLD) {
            $diff = $this->getFormattedHoldTime();

            return [
                'state' => 'paused',
                'label' => __('ticketing.sla.paused'),
                'color' => 'bg-amber-50 text-amber-700 border-amber-200 font-bold',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200 font-bold',
                'time_diff' => $diff,
                'remaining' => $diff,
            ];
        }

        $dueAt = $this->getSlaDueAt();
        if (! $dueAt) {
            return [
                'state' => 'pending',
                'label' => __('ticketing.sla.pending'),
                'color' => 'bg-slate-50 text-slate-500 border-slate-200',
                'badge' => 'bg-slate-50 text-slate-500 border-slate-200',
                'time_diff' => null,
                'remaining' => null,
            ];
        }

        if (now()->greaterThan($dueAt)) {
            $diff = __('ticketing.sla.overdue_suffix', [
                'time' => now()->diffForHumans($dueAt, CarbonInterface::DIFF_ABSOLUTE),
            ]);

            return [
                'state' => 'breached',
                'label' => __('ticketing.sla.overdue'),
                'color' => 'bg-rose-50 text-rose-700 border-rose-200 font-bold',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200 font-bold',
                'time_diff' => $diff,
                'remaining' => $diff,
            ];
        }

        $minutesRemaining = (int) now()->diffInMinutes($dueAt, false);
        if ($minutesRemaining <= 120) {
            $diff = __('ticketing.sla.left_suffix', [
                'time' => now()->diffForHumans($dueAt, CarbonInterface::DIFF_ABSOLUTE),
            ]);

            return [
                'state' => 'due_soon',
                'label' => __('ticketing.sla.due_soon'),
                'color' => 'bg-amber-50 text-amber-700 border-amber-200 font-bold',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200 font-bold',
                'time_diff' => $diff,
                'remaining' => $diff,
            ];
        }

        $diff = __('ticketing.sla.left_suffix', [
            'time' => now()->diffForHumans($dueAt, CarbonInterface::DIFF_ABSOLUTE),
        ]);

        return [
            'state' => 'on_track',
            'label' => __('ticketing.sla.on_track'),
            'color' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'time_diff' => $diff,
            'remaining' => $diff,
        ];
    }

    /**
     * Extract contact number from description (if submitted via public assistance).
     */
    public function getContactPhoneAttribute(): ?string
    {
        if (preg_match('/Contact Number:\s*([0-9\+\-\s]+)/i', (string) $this->description, $matches)) {
            $clean = trim($matches[1]);
            $digits = preg_replace('/[^0-9]/', '', $clean);
            if ($digits && strlen($digits) >= 8) {
                return $clean;
            }
        }

        return null;
    }

    /**
     * Generate direct WhatsApp click-to-chat URL.
     */
    public function getWhatsAppUrlAttribute(): ?string
    {
        $phone = $this->contact_phone;
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        $greeting = "Halo, kami dari tim IT Support Daijo mengenai Tiket #{$this->ticket_number} ({$this->title}).";

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($greeting);
    }

    /**
     * Get clean, readable description without legacy raw form headers.
     */
    public function getCleanDescription(): string
    {
        $desc = (string) $this->description;

        // Clean legacy login help format: "Issue Type: ...\nContact Number: ...\n\nProblem Description:\n..."
        if (str_contains($desc, "Problem Description:\n")) {
            $parts = explode("Problem Description:\n", $desc, 2);
            $cleanBody = trim($parts[1] ?? '');

            return $cleanBody !== '' ? $cleanBody : $desc;
        }

        return $desc;
    }
}
