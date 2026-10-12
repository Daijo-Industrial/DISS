<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Project\Enums\ProjectPriority;
use App\Domain\Project\Enums\ProjectStage;
use App\Domain\Project\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'project_code',
        'name',
        'description',
        'requester_name',
        'requester_department_id',
        'primary_pic_id',
        'priority',
        'stage',
        'status',
        'start_date',
        'original_due_date',
        'due_date',
        'finished_date',
        'progress_percent',
        'deadline_extension_count',
        'paused_at',
        'resumed_at',
        'hold_reason',
        'cancelled_at',
        'cancellation_reason_category',
        'cancellation_notes',
        'created_by',
        'updated_by',
        'closed_at',
    ];

    protected $casts = [
        'stage' => ProjectStage::class,
        'status' => ProjectStatus::class,
        'priority' => ProjectPriority::class,
        'start_date' => 'date',
        'original_due_date' => 'date',
        'due_date' => 'date',
        'finished_date' => 'date',
        'progress_percent' => 'float',
        'deadline_extension_count' => 'integer',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'requester_department_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_pic_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(ProjectCheckpoint::class, 'project_id')->orderBy('sequence_order');
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(ProjectDeadlineExtension::class, 'project_id')->latest('created_at');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ProjectApproval::class, 'project_id')->latest('created_at');
    }

    public function stageHistories(): HasMany
    {
        return $this->hasMany(ProjectStageHistory::class, 'project_id')->latest('created_at');
    }
}
