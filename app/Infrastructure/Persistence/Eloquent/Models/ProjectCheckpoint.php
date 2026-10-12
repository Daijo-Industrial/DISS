<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Project\Enums\CheckpointStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectCheckpoint extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'project_checkpoints';

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'sequence_order',
        'is_required',
        'status',
        'due_date',
        'completed_date',
        'checked_by',
        'notes',
    ];

    protected $casts = [
        'status' => CheckpointStatus::class,
        'is_required' => 'boolean',
        'sequence_order' => 'integer',
        'due_date' => 'date',
        'completed_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CheckpointAttachment::class, 'checkpoint_id');
    }
}
