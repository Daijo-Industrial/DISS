<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Project\Enums\ProjectStage;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStageHistory extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $table = 'project_stage_histories';

    protected $fillable = [
        'project_id',
        'from_stage',
        'to_stage',
        'changed_by',
        'is_override',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'from_stage' => ProjectStage::class,
        'to_stage' => ProjectStage::class,
        'is_override' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
