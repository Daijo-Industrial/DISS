<?php

declare(strict_types=1);

namespace App\Domain\Project\Services;

use App\Domain\Project\Enums\CancellationCategory;
use App\Domain\Project\Enums\CheckpointStatus;
use App\Domain\Project\Enums\ExtensionReasonCategory;
use App\Domain\Project\Enums\ProjectStage;
use App\Domain\Project\Enums\ProjectStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Project;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectApproval;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectDeadlineExtension;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectStageHistory;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ProjectWorkflowService
{
    /**
     * Stage progression map for normal, non-override transitions.
     *
     * @var array<string, string>
     */
    private const NORMAL_STAGE_FLOW = [
        ProjectStage::DRAFT->value => ProjectStage::INITIATION->value,
        ProjectStage::INITIATION->value => ProjectStage::PLANNING->value,
        ProjectStage::PLANNING->value => ProjectStage::DEVELOPMENT->value,
        ProjectStage::DEVELOPMENT->value => ProjectStage::MONITORING->value,
        ProjectStage::MONITORING->value => ProjectStage::REVIEW->value,
    ];

    /**
     * Transition a project to a new lifecycle stage.
     */
    public function transitionStage(
        Project $project,
        ProjectStage $targetStage,
        User $user,
        ?string $reason = null,
        bool $isOverride = false
    ): Project {
        if ($project->status === ProjectStatus::ON_HOLD) {
            throw new DomainException('Cannot transition stage: Project is currently On-Hold.');
        }

        if ($project->status->isTerminal()) {
            throw new DomainException("Cannot transition stage: Project is in terminal status ({$project->status->value}).");
        }

        if ($targetStage === ProjectStage::CLOSURE) {
            throw new DomainException('Review to Closure transition requires formal manager approval via approveClosure().');
        }

        $currentStage = $project->stage;
        $expectedNext = self::NORMAL_STAGE_FLOW[$currentStage->value] ?? null;

        if ($expectedNext !== $targetStage->value && ! $isOverride) {
            throw new DomainException(
                "Invalid stage transition from {$currentStage->value} to {$targetStage->value}. Next expected stage is {$expectedNext}. Use override flag with justification to bypass."
            );
        }

        if ($isOverride && empty(trim((string) $reason))) {
            throw new DomainException('A mandatory justification reason is required when performing a workflow override.');
        }

        return DB::transaction(function () use ($project, $currentStage, $targetStage, $user, $isOverride, $reason) {
            ProjectStageHistory::create([
                'project_id' => $project->id,
                'from_stage' => $currentStage,
                'to_stage' => $targetStage,
                'changed_by' => $user->id,
                'is_override' => $isOverride,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            $project->update([
                'stage' => $targetStage,
                'updated_by' => $user->id,
            ]);

            return $project->fresh();
        });
    }

    /**
     * Put a project on hold.
     */
    public function hold(Project $project, string $reason, User $user): Project
    {
        if ($project->status !== ProjectStatus::ACTIVE) {
            throw new DomainException("Only Active projects can be put On-Hold. Current status is {$project->status->value}.");
        }

        if (empty(trim($reason))) {
            throw new DomainException('A hold reason is mandatory.');
        }

        $project->update([
            'status' => ProjectStatus::ON_HOLD,
            'paused_at' => now(),
            'hold_reason' => $reason,
            'updated_by' => $user->id,
        ]);

        return $project->fresh();
    }

    /**
     * Resume an on-hold project with optional deadline adjustment.
     */
    public function resume(Project $project, bool $extendDueDate, User $user): Project
    {
        if ($project->status !== ProjectStatus::ON_HOLD) {
            throw new DomainException("Only On-Hold projects can be resumed. Current status is {$project->status->value}.");
        }

        return DB::transaction(function () use ($project, $extendDueDate, $user) {
            $pausedAt = $project->paused_at ?? now();
            $pauseDays = max(1, (int) ceil(Carbon::parse($pausedAt)->diffInDays(now())));

            if ($extendDueDate) {
                $oldDue = Carbon::parse($project->due_date);
                $newDue = (clone $oldDue)->addDays($pauseDays);

                ProjectDeadlineExtension::create([
                    'project_id' => $project->id,
                    'old_due_date' => $oldDue->toDateString(),
                    'new_due_date' => $newDue->toDateString(),
                    'reason_category' => ExtensionReasonCategory::PAUSE_EXTENSION,
                    'reason_details' => "Automatic schedule adjustment for {$pauseDays} day(s) On-Hold pause.",
                    'requested_by' => $user->id,
                    'approved_by' => $user->id,
                    'created_at' => now(),
                ]);

                $project->due_date = $newDue;
                $project->deadline_extension_count += 1;
            }

            $project->status = ProjectStatus::ACTIVE;
            $project->resumed_at = now();
            $project->updated_by = $user->id;
            $project->save();

            return $project->fresh();
        });
    }

    /**
     * Cancel a project with mandatory category and notes.
     */
    public function cancel(
        Project $project,
        CancellationCategory $category,
        ?string $notes,
        User $user
    ): Project {
        if ($project->status === ProjectStatus::COMPLETED) {
            throw new DomainException('Completed projects cannot be cancelled.');
        }

        $project->update([
            'status' => ProjectStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason_category' => $category->value,
            'cancellation_notes' => $notes,
            'updated_by' => $user->id,
        ]);

        return $project->fresh();
    }

    /**
     * Formal approval gate: Transition from Review to Closure.
     */
    public function approveClosure(Project $project, User $approver, ?string $comments = null): Project
    {
        if ($project->stage !== ProjectStage::REVIEW) {
            throw new DomainException("Closure approval can only occur when project is in REVIEW stage. Current stage: {$project->stage->value}.");
        }

        if ($project->status === ProjectStatus::ON_HOLD) {
            throw new DomainException('Cannot close project while On-Hold.');
        }

        // Validate 100% required checkpoints are completed
        $pendingRequired = $project->checkpoints()
            ->where('is_required', true)
            ->where('status', '!=', CheckpointStatus::COMPLETED->value)
            ->count();

        if ($pendingRequired > 0) {
            throw new DomainException(
                "Cannot approve project closure: {$pendingRequired} required checkpoint(s) remain uncompleted."
            );
        }

        return DB::transaction(function () use ($project, $approver, $comments) {
            ProjectApproval::create([
                'project_id' => $project->id,
                'stage_target' => ProjectStage::CLOSURE->value,
                'decision' => 'APPROVED',
                'decided_by' => $approver->id,
                'comments' => $comments,
                'created_at' => now(),
            ]);

            ProjectStageHistory::create([
                'project_id' => $project->id,
                'from_stage' => ProjectStage::REVIEW,
                'to_stage' => ProjectStage::CLOSURE,
                'changed_by' => $approver->id,
                'is_override' => false,
                'reason' => 'Formal project closure approved: ' . ($comments ?? 'No comments'),
                'created_at' => now(),
            ]);

            $now = now();
            $project->update([
                'stage' => ProjectStage::CLOSURE,
                'status' => ProjectStatus::COMPLETED,
                'finished_date' => $project->finished_date ?? $now->toDateString(),
                'closed_at' => $now,
                'updated_by' => $approver->id,
            ]);

            return $project->fresh();
        });
    }

    /**
     * Reject project closure during Review stage.
     */
    public function rejectClosure(
        Project $project,
        User $approver,
        string $comments,
        ProjectStage $returnStage = ProjectStage::MONITORING
    ): Project {
        if ($project->stage !== ProjectStage::REVIEW) {
            throw new DomainException("Closure rejection can only occur when project is in REVIEW stage. Current stage: {$project->stage->value}.");
        }

        if (empty(trim($comments))) {
            throw new DomainException('Rejection feedback comments are required.');
        }

        return DB::transaction(function () use ($project, $approver, $comments, $returnStage) {
            ProjectApproval::create([
                'project_id' => $project->id,
                'stage_target' => ProjectStage::CLOSURE->value,
                'decision' => 'REJECTED',
                'decided_by' => $approver->id,
                'comments' => $comments,
                'created_at' => now(),
            ]);

            ProjectStageHistory::create([
                'project_id' => $project->id,
                'from_stage' => ProjectStage::REVIEW,
                'to_stage' => $returnStage,
                'changed_by' => $approver->id,
                'is_override' => false,
                'reason' => 'Closure rejected: ' . $comments,
                'created_at' => now(),
            ]);

            $project->update([
                'stage' => $returnStage,
                'updated_by' => $approver->id,
            ]);

            return $project->fresh();
        });
    }

    /**
     * Request and apply an approved deadline extension.
     */
    public function requestDeadlineExtension(
        Project $project,
        string $newDueDate,
        ExtensionReasonCategory $category,
        string $details,
        User $requester,
        User $approver
    ): ProjectDeadlineExtension {
        $oldDue = Carbon::parse($project->due_date);
        $newDue = Carbon::parse($newDueDate);

        if ($newDue->lte($oldDue)) {
            throw new DomainException("The new due date ({$newDue->toDateString()}) must be after current due date ({$oldDue->toDateString()}).");
        }

        return DB::transaction(function () use ($project, $oldDue, $newDue, $category, $details, $requester, $approver) {
            $extension = ProjectDeadlineExtension::create([
                'project_id' => $project->id,
                'old_due_date' => $oldDue->toDateString(),
                'new_due_date' => $newDue->toDateString(),
                'reason_category' => $category,
                'reason_details' => $details,
                'requested_by' => $requester->id,
                'approved_by' => $approver->id,
                'created_at' => now(),
            ]);

            $project->update([
                'due_date' => $newDue->toDateString(),
                'deadline_extension_count' => $project->deadline_extension_count + 1,
                'updated_by' => $approver->id,
            ]);

            return $extension;
        });
    }
}
