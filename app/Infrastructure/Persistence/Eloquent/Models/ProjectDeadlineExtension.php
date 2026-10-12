<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Project\Enums\ExtensionReasonCategory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDeadlineExtension extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $table = 'project_deadline_extensions';

    protected $fillable = [
        'project_id',
        'old_due_date',
        'new_due_date',
        'reason_category',
        'reason_details',
        'requested_by',
        'approved_by',
        'created_at',
    ];

    protected $casts = [
        'old_due_date' => 'date',
        'new_due_date' => 'date',
        'reason_category' => ExtensionReasonCategory::class,
        'created_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
