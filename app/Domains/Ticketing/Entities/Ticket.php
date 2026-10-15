<?php

namespace App\Domains\Ticketing\Entities;

use App\Domains\Ticketing\Enums\TicketPriority;
use App\Domains\Ticketing\Enums\TicketStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Employee;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Carbon\Carbon;
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
        return $this->hasMany(TicketActivity::class, 'ticket_id')->orderBy('created_at', 'desc');
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
}
