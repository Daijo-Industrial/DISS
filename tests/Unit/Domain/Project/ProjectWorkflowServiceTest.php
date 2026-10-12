<?php

declare(strict_types=1);

use App\Domain\Project\Enums\CancellationCategory;
use App\Domain\Project\Enums\CheckpointStatus;
use App\Domain\Project\Enums\ExtensionReasonCategory;
use App\Domain\Project\Enums\ProjectPriority;
use App\Domain\Project\Enums\ProjectStage;
use App\Domain\Project\Enums\ProjectStatus;
use App\Domain\Project\Services\ProjectWorkflowService;
use App\Infrastructure\Persistence\Eloquent\Models\Department;
use App\Infrastructure\Persistence\Eloquent\Models\Project;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectCheckpoint;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

describe('ProjectWorkflowService', function () {
    beforeEach(function () {
        $this->service = new ProjectWorkflowService;
        $this->department = Department::first() ?? Department::factory()->create();
        $this->user = User::first() ?? User::factory()->create();

        $this->project = Project::create([
            'project_code' => 'TEST-WF-001',
            'name' => 'Workflow Test Project',
            'requester_name' => 'Test Requester',
            'requester_department_id' => $this->department->id,
            'primary_pic_id' => $this->user->id,
            'priority' => ProjectPriority::HIGH,
            'stage' => ProjectStage::DRAFT,
            'status' => ProjectStatus::ACTIVE,
            'start_date' => Carbon::today()->toDateString(),
            'original_due_date' => Carbon::today()->addDays(30)->toDateString(),
            'due_date' => Carbon::today()->addDays(30)->toDateString(),
            'created_by' => $this->user->id,
        ]);
    });

    it('advances through normal lifecycle stages sequentially', function () {
        // DRAFT -> INITIATION
        $p = $this->service->transitionStage($this->project, ProjectStage::INITIATION, $this->user);
        expect($p->stage)->toBe(ProjectStage::INITIATION);

        // INITIATION -> PLANNING
        $p = $this->service->transitionStage($p, ProjectStage::PLANNING, $this->user);
        expect($p->stage)->toBe(ProjectStage::PLANNING);

        // PLANNING -> DEVELOPMENT
        $p = $this->service->transitionStage($p, ProjectStage::DEVELOPMENT, $this->user);
        expect($p->stage)->toBe(ProjectStage::DEVELOPMENT);

        // DEVELOPMENT -> MONITORING
        $p = $this->service->transitionStage($p, ProjectStage::MONITORING, $this->user);
        expect($p->stage)->toBe(ProjectStage::MONITORING);

        // MONITORING -> REVIEW
        $p = $this->service->transitionStage($p, ProjectStage::REVIEW, $this->user);
        expect($p->stage)->toBe(ProjectStage::REVIEW);
    });

    it('blocks out of order transitions without override', function () {
        expect(fn () => $this->service->transitionStage($this->project, ProjectStage::DEVELOPMENT, $this->user))
            ->toThrow(DomainException::class);
    });

    it('allows out of order transitions with explicit override and reason', function () {
        $p = $this->service->transitionStage(
            $this->project,
            ProjectStage::DEVELOPMENT,
            $this->user,
            'Fast-tracked by IT Head due to urgent regulatory need',
            true
        );

        expect($p->stage)->toBe(ProjectStage::DEVELOPMENT);
        expect($p->stageHistories()->where('is_override', true)->count())->toBe(1);
    });

    it('blocks stage transitions when on hold', function () {
        $this->service->hold($this->project, 'Waiting on client budget approval', $this->user);

        expect(fn () => $this->service->transitionStage($this->project, ProjectStage::INITIATION, $this->user))
            ->toThrow(DomainException::class, 'Cannot transition stage: Project is currently On-Hold.');
    });

    it('handles hold and resume with schedule adjustment', function () {
        $p = $this->service->hold($this->project, 'Awaiting hardware arrival', $this->user);
        expect($p->status)->toBe(ProjectStatus::ON_HOLD);
        expect($p->hold_reason)->toBe('Awaiting hardware arrival');

        // Simulate 10 days paused
        $p->paused_at = Carbon::now()->subDays(10);
        $p->save();

        $originalDue = $p->due_date->toDateString();
        $resumed = $this->service->resume($p, true, $this->user);

        expect($resumed->status)->toBe(ProjectStatus::ACTIVE);
        expect($resumed->deadline_extension_count)->toBe(1);
        expect($resumed->due_date->toDateString())->not->toBe($originalDue);
    });

    it('cancels project with mandatory category', function () {
        $cancelled = $this->service->cancel(
            $this->project,
            CancellationCategory::REQUESTER_DEPEN_DEP_PRIORITIZED,
            'Business sponsor cancelled initiative',
            $this->user
        );

        expect($cancelled->status)->toBe(ProjectStatus::CANCELLED);
        expect($cancelled->cancellation_reason_category)->toBe(CancellationCategory::REQUESTER_DEPEN_DEP_PRIORITIZED->value);
    });

    it('enforces closure gate: blocks closure if required checkpoints incomplete', function () {
        // Advance to REVIEW
        $this->project->stage = ProjectStage::REVIEW;
        $this->project->save();

        // Create an uncompleted required checkpoint
        ProjectCheckpoint::create([
            'project_id' => $this->project->id,
            'title' => 'Critical Security Audit',
            'is_required' => true,
            'status' => CheckpointStatus::PENDING,
        ]);

        expect(fn () => $this->service->approveClosure($this->project, $this->user, 'All done'))
            ->toThrow(DomainException::class, 'remain uncompleted');
    });

    it('approves closure when all required checkpoints are complete', function () {
        $this->project->stage = ProjectStage::REVIEW;
        $this->project->save();

        // Create and complete required checkpoint
        ProjectCheckpoint::create([
            'project_id' => $this->project->id,
            'title' => 'Critical Security Audit',
            'is_required' => true,
            'status' => CheckpointStatus::COMPLETED,
            'completed_date' => Carbon::today()->toDateString(),
        ]);

        $closed = $this->service->approveClosure($this->project, $this->user, 'Manager signed off');

        expect($closed->stage)->toBe(ProjectStage::CLOSURE);
        expect($closed->status)->toBe(ProjectStatus::COMPLETED);
        expect($closed->finished_date)->not->toBeNull();
        expect($closed->approvals()->where('decision', 'APPROVED')->count())->toBe(1);
    });

    it('rejects closure and reverts stage to monitoring', function () {
        $this->project->stage = ProjectStage::REVIEW;
        $this->project->save();

        $rejected = $this->service->rejectClosure(
            $this->project,
            $this->user,
            'User acceptance testing failed on line 3'
        );

        expect($rejected->stage)->toBe(ProjectStage::MONITORING);
        expect($rejected->approvals()->where('decision', 'REJECTED')->count())->toBe(1);
    });

    it('records and applies deadline extension audits', function () {
        $newDue = Carbon::parse($this->project->due_date)->addDays(14)->toDateString();

        $extension = $this->service->requestDeadlineExtension(
            $this->project,
            $newDue,
            ExtensionReasonCategory::SCOPE_CHANGE,
            'Additional SAP RFC interface requested by warehouse',
            $this->user,
            $this->user
        );

        expect($extension->new_due_date->toDateString())->toBe($newDue);
        expect($this->project->fresh()->due_date->toDateString())->toBe($newDue);
        expect($this->project->fresh()->deadline_extension_count)->toBe(1);
    });
});
