<?php

declare(strict_types=1);

namespace App\Domain\Project\Observers;

use App\Domain\Project\Enums\ProjectStage;
use App\Infrastructure\Persistence\Eloquent\Models\Project;
use DomainException;

class ProjectObserver
{
    /**
     * Handle the Project "creating" event.
     */
    public function creating(Project $project): void
    {
        if (empty($project->project_code)) {
            $year = now()->format('Y');
            $count = Project::whereYear('created_at', $year)->count() + 1;
            $project->project_code = sprintf('IT-%s-%04d', $year, $count);
        }

        if (empty($project->original_due_date) && ! empty($project->due_date)) {
            $project->original_due_date = $project->due_date;
        }
    }

    /**
     * Handle the Project "updating" event.
     */
    public function updating(Project $project): void
    {
        if ($project->isDirty('original_due_date')) {
            $originalStage = $project->getOriginal('stage');
            $originalStageEnum = $originalStage instanceof ProjectStage ? $originalStage : ProjectStage::tryFrom((string) $originalStage);

            // Once past planning, original due date cannot be mutated
            if ($originalStageEnum && in_array($originalStageEnum, [
                ProjectStage::DEVELOPMENT,
                ProjectStage::MONITORING,
                ProjectStage::REVIEW,
                ProjectStage::CLOSURE,
            ], true)) {
                throw new DomainException('Original due date cannot be modified once project exits Planning stage.');
            }
        }
    }
}
