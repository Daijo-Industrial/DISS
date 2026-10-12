<?php

declare(strict_types=1);

namespace App\Domain\Project\Observers;

use App\Domain\Project\Enums\CheckpointStatus;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectCheckpoint;

class ProjectCheckpointObserver
{
    /**
     * Handle the ProjectCheckpoint "saved" event.
     */
    public function saved(ProjectCheckpoint $checkpoint): void
    {
        $this->recalculateProgress($checkpoint);
    }

    /**
     * Handle the ProjectCheckpoint "deleted" event.
     */
    public function deleted(ProjectCheckpoint $checkpoint): void
    {
        $this->recalculateProgress($checkpoint);
    }

    /**
     * Handle the ProjectCheckpoint "restored" event.
     */
    public function restored(ProjectCheckpoint $checkpoint): void
    {
        $this->recalculateProgress($checkpoint);
    }

    private function recalculateProgress(ProjectCheckpoint $checkpoint): void
    {
        $project = $checkpoint->project;
        if (! $project) {
            return;
        }

        $requiredCount = $project->checkpoints()->where('is_required', true)->count();
        if ($requiredCount === 0) {
            $project->updateQuietly(['progress_percent' => 0.00]);

            return;
        }

        $completedCount = $project->checkpoints()
            ->where('is_required', true)
            ->where('status', CheckpointStatus::COMPLETED->value)
            ->count();

        $percentage = round(($completedCount / $requiredCount) * 100, 2);

        $project->updateQuietly(['progress_percent' => $percentage]);
    }
}
