<?php

declare(strict_types=1);

use App\Domain\Project\Enums\CheckpointStatus;
use App\Domain\Project\Enums\ProjectPriority;
use App\Domain\Project\Enums\ProjectStage;
use App\Domain\Project\Enums\ProjectStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Department;
use App\Infrastructure\Persistence\Eloquent\Models\Project;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectCheckpoint;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;

uses(DatabaseTransactions::class);

describe('Project REST API', function () {
    beforeEach(function () {
        $this->department = Department::first() ?? Department::factory()->create();
        $this->user = User::first() ?? User::factory()->create();

        Sanctum::actingAs($this->user);
    });

    it('requires authentication to access project endpoints', function () {
        auth()->forgetGuards();

        $response = $this->getJson('/api/v1/projects');
        $response->assertUnauthorized();
    });

    it('creates a new project via POST /api/v1/projects', function () {
        $payload = [
            'name' => 'MES SAP Interface Module',
            'description' => 'Real-time sync between shop floor and SAP Business One',
            'requester_name' => 'Production Dept',
            'requester_department_id' => $this->department->id,
            'primary_pic_id' => $this->user->id,
            'priority' => ProjectPriority::HIGH->value,
            'start_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(45)->toDateString(),
        ];

        $response = $this->postJson('/api/v1/projects', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'MES SAP Interface Module')
            ->assertJsonPath('data.priority', ProjectPriority::HIGH->value)
            ->assertJsonPath('data.stage', ProjectStage::DRAFT->value)
            ->assertJsonPath('data.status', ProjectStatus::ACTIVE->value);

        expect($response->json('data.project_code'))->toStartWith('IT-');
    });

    it('lists projects with pagination and filters', function () {
        Project::create([
            'project_code' => 'IT-FILTER-001',
            'name' => 'Alpha Project',
            'requester_name' => 'Dept Alpha',
            'requester_department_id' => $this->department->id,
            'primary_pic_id' => $this->user->id,
            'priority' => ProjectPriority::MEDIUM,
            'stage' => ProjectStage::DEVELOPMENT,
            'status' => ProjectStatus::ACTIVE,
            'due_date' => Carbon::today()->addDays(20)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/projects?stage=DEVELOPMENT&status=ACTIVE');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'project_code', 'name', 'health_status', 'progress_percent'],
                ],
                'links',
                'meta',
            ]);
    });

    it('retrieves detailed project payload via GET /api/v1/projects/{id}', function () {
        $project = Project::create([
            'project_code' => 'IT-DETAIL-001',
            'name' => 'Detailed Project',
            'requester_name' => 'QA Team',
            'requester_department_id' => $this->department->id,
            'primary_pic_id' => $this->user->id,
            'priority' => ProjectPriority::CRITICAL,
            'stage' => ProjectStage::PLANNING,
            'status' => ProjectStatus::ACTIVE,
            'due_date' => Carbon::today()->addDays(30)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/projects/{$project->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $project->id)
            ->assertJsonPath('data.name', 'Detailed Project')
            ->assertJsonStructure([
                'data' => ['id', 'checkpoints', 'extensions', 'stage_histories', 'health_status'],
            ]);
    });

    it('calculates progress percentage automatically on checkpoint completion', function () {
        $project = Project::create([
            'project_code' => 'IT-PROG-001',
            'name' => 'Progress Auto Calculation Project',
            'requester_name' => 'Line 1',
            'requester_department_id' => $this->department->id,
            'primary_pic_id' => $this->user->id,
            'priority' => ProjectPriority::LOW,
            'stage' => ProjectStage::DEVELOPMENT,
            'status' => ProjectStatus::ACTIVE,
            'due_date' => Carbon::today()->addDays(20)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // Add 2 required checkpoints via API
        $cp1 = $this->postJson("/api/v1/projects/{$project->id}/checkpoints", [
            'title' => 'Deliverable 1',
            'is_required' => true,
        ])->json('data.id');

        $cp2 = $this->postJson("/api/v1/projects/{$project->id}/checkpoints", [
            'title' => 'Deliverable 2',
            'is_required' => true,
        ])->json('data.id');

        expect($project->fresh()->progress_percent)->toBe(0.00);

        // Complete checkpoint 1 (1 of 2 completed = 50%)
        $this->postJson("/api/v1/checkpoints/{$cp1}/complete")->assertOk();
        expect($project->fresh()->progress_percent)->toBe(50.00);

        // Complete checkpoint 2 (2 of 2 completed = 100%)
        $this->postJson("/api/v1/checkpoints/{$cp2}/complete")->assertOk();
        expect($project->fresh()->progress_percent)->toBe(100.00);
    });

    it('transitions stages and enforces closure approval via workflow API', function () {
        $project = Project::create([
            'project_code' => 'IT-WF-API-001',
            'name' => 'Lifecycle API Project',
            'requester_name' => 'Warehouse',
            'requester_department_id' => $this->department->id,
            'primary_pic_id' => $this->user->id,
            'priority' => ProjectPriority::MEDIUM,
            'stage' => ProjectStage::DRAFT,
            'status' => ProjectStatus::ACTIVE,
            'due_date' => Carbon::today()->addDays(30)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // Transition DRAFT -> INITIATION
        $this->postJson("/api/v1/projects/{$project->id}/transition-stage", [
            'target_stage' => ProjectStage::INITIATION->value,
        ])->assertOk()->assertJsonPath('data.stage', ProjectStage::INITIATION->value);

        // Fast forward to REVIEW
        $project->update(['stage' => ProjectStage::REVIEW]);

        // Add incomplete required checkpoint
        ProjectCheckpoint::create([
            'project_id' => $project->id,
            'title' => 'Signoff Form',
            'is_required' => true,
            'status' => CheckpointStatus::PENDING,
        ]);

        // Attempt closure approval: should fail with 422
        $this->postJson("/api/v1/projects/{$project->id}/approve-closure")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        // Mark checkpoint complete
        $project->checkpoints()->update(['status' => CheckpointStatus::COMPLETED]);

        // Now approval succeeds
        $this->postJson("/api/v1/projects/{$project->id}/approve-closure", [
            'comments' => 'Final sign-off completed by IT Manager',
        ])->assertOk()
            ->assertJsonPath('data.stage', ProjectStage::CLOSURE->value)
            ->assertJsonPath('data.status', ProjectStatus::COMPLETED->value);
    });

    it('handles hold, resume, and cancellation endpoints', function () {
        $project = Project::create([
            'project_code' => 'IT-STATUS-API-001',
            'name' => 'Status API Project',
            'requester_name' => 'Purchasing',
            'requester_department_id' => $this->department->id,
            'primary_pic_id' => $this->user->id,
            'priority' => ProjectPriority::LOW,
            'stage' => ProjectStage::DEVELOPMENT,
            'status' => ProjectStatus::ACTIVE,
            'due_date' => Carbon::today()->addDays(15)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // Put on hold
        $this->postJson("/api/v1/projects/{$project->id}/hold", [
            'reason' => 'Vendor API offline',
        ])->assertOk()->assertJsonPath('data.status', ProjectStatus::ON_HOLD->value);

        // Resume
        $this->postJson("/api/v1/projects/{$project->id}/resume", [
            'extend_due_date' => false,
        ])->assertOk()->assertJsonPath('data.status', ProjectStatus::ACTIVE->value);

        // Cancel
        $this->postJson("/api/v1/projects/{$project->id}/cancel", [
            'reason_category' => 'REQUESTER_DEPEN_DEP_PRIORITIZED',
            'notes' => 'Scope shifted to Q3',
        ])->assertOk()->assertJsonPath('data.status', ProjectStatus::CANCELLED->value);
    });

    it('returns dashboard summary, stage distribution, and PIC leaderboard', function () {
        $this->getJson('/api/v1/projects/dashboard/summary')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_projects',
                    'active_projects',
                    'health_breakdown' => ['on_track', 'at_risk', 'overdue'],
                    'dual_sla' => ['original_sla_rate', 'revised_schedule_rate'],
                ],
            ]);

        $this->getJson('/api/v1/projects/dashboard/stage-distribution')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/projects/dashboard/pic-leaderboard')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/projects/dashboard/upcoming-deadlines')
            ->assertOk()
            ->assertJsonPath('success', true);
    });

    it('generates unique project code even when previous project was soft deleted', function () {
        $year = now()->format('Y');

        $p1 = Project::create([
            'name' => 'Project Alpha',
            'requester_name' => 'Requester 1',
            'requester_department_id' => $this->department->id,
            'primary_pic_id' => $this->user->id,
            'priority' => ProjectPriority::MEDIUM,
            'due_date' => Carbon::today()->addDays(15)->toDateString(),
        ]);

        $code1 = $p1->project_code;
        expect($code1)->toStartWith("IT-{$year}-");

        // Soft delete the project
        $p1->delete();
        expect(Project::find($p1->id))->toBeNull();
        expect(Project::withTrashed()->find($p1->id))->not->toBeNull();

        // Create second project - must not collide with $code1
        $p2 = Project::create([
            'name' => 'Project Beta',
            'requester_name' => 'Requester 2',
            'requester_department_id' => $this->department->id,
            'primary_pic_id' => $this->user->id,
            'priority' => ProjectPriority::HIGH,
            'due_date' => Carbon::today()->addDays(20)->toDateString(),
        ]);

        $code2 = $p2->project_code;
        expect($code2)->toStartWith("IT-{$year}-");
        expect($code2)->not->toEqual($code1);

        $num1 = (int) substr($code1, -4);
        $num2 = (int) substr($code2, -4);
        expect($num2)->toBeGreaterThan($num1);
    });
});

