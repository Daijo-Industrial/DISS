<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectApproval extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $table = 'project_approvals';

    protected $fillable = [
        'project_id',
        'stage_target',
        'decision',
        'decided_by',
        'comments',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
