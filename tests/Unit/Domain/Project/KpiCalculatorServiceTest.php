<?php

declare(strict_types=1);

use App\Domain\Project\Enums\KpiStatus;
use App\Domain\Project\Enums\ProjectPriority;
use App\Domain\Project\Enums\ProjectStage;
use App\Domain\Project\Enums\ProjectStatus;
use App\Domain\Project\Services\KpiCalculatorService;
use App\Infrastructure\Persistence\Eloquent\Models\Department;
use App\Infrastructure\Persistence\Eloquent\Models\Project;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

describe('KpiCalculatorService', function () {
    beforeEach(function () {
        $this->service = new KpiCalculatorService;
        $this->department = Department::first() ?? Department::factory()->create();
        $this->user = User::first() ?? User::factory()->create();
    });

    it('computes health status correctly for active projects', function () {
        // ON_TRACK: due in 20 days
        $onTrack = new Project([
            'status' => ProjectStatus::ACTIVE,
            'due_date' => Carbon::today()->addDays(20)->toDateString(),
            'progress_percent' => 50.00,
        ]);
        expect($this->service->computeHealthStatus($onTrack))->toBe(KpiStatus::ON_TRACK);

        // AT_RISK: due in 5 days and progress < 80%
        $atRisk = new Project([
            'status' => ProjectStatus::ACTIVE,
            'due_date' => Carbon::today()->addDays(5)->toDateString(),
            'progress_percent' => 40.00,
        ]);
        expect($this->service->computeHealthStatus($atRisk))->toBe(KpiStatus::AT_RISK);

        // OVERDUE: due 2 days ago
        $overdue = new Project([
            'status' => ProjectStatus::ACTIVE,
            'due_date' => Carbon::today()->subDays(2)->toDateString(),
            'progress_percent' => 70.00,
        ]);
        expect($this->service->computeHealthStatus($overdue))->toBe(KpiStatus::OVERDUE);
    });

    it('computes health status for on-hold and cancelled projects', function () {
        $onHold = new Project([
            'status' => ProjectStatus::ON_HOLD,
            'due_date' => Carbon::today()->subDays(5)->toDateString(),
        ]);
        expect($this->service->computeHealthStatus($onHold))->toBe(KpiStatus::ON_HOLD);

        $cancelled = new Project([
            'status' => ProjectStatus::CANCELLED,
            'due_date' => Carbon::today()->subDays(5)->toDateString(),
        ]);
        expect($this->service->computeHealthStatus($cancelled))->toBe(KpiStatus::CANCELLED);
    });

    it('distinguishes original SLA vs revised schedule adherence on completed projects', function () {
        // Project A: finished on or before original due date -> ON_TIME for both
        $projectA = new Project([
            'status' => ProjectStatus::COMPLETED,
            'original_due_date' => '2026-05-01',
            'due_date' => '2026-05-01',
            'finished_date' => '2026-04-28',
        ]);
        expect($this->service->computeHealthStatus($projectA))->toBe(KpiStatus::ON_TIME);

        // Project B: finished after original due date, but within approved revised due date
        // In health status it is ON_TIME (against approved due_date)
        $projectB = new Project([
            'status' => ProjectStatus::COMPLETED,
            'original_due_date' => '2026-05-01',
            'due_date' => '2026-05-20',
            'finished_date' => '2026-05-15',
        ]);
        expect($this->service->computeHealthStatus($projectB))->toBe(KpiStatus::ON_TIME);

        // Project C: finished after revised due date -> LATE
        $projectC = new Project([
            'status' => ProjectStatus::COMPLETED,
            'original_due_date' => '2026-05-01',
            'due_date' => '2026-05-20',
            'finished_date' => '2026-05-25',
        ]);
        expect($this->service->computeHealthStatus($projectC))->toBe(KpiStatus::LATE);
    });

    it('calculates dual SLA rates across persisted projects', function () {
        // Create Project 1: Finished on-time for both (finished <= original <= current)
        Project::create([
            'project_code' => 'TEST-001',
            'name' => 'Project 1',
            'requester_name' => 'Requester 1',
            'requester_department_id' => $this->department->id,
            'primary_pic_id' => $this->user->id,
            'priority' => ProjectPriority::HIGH,
            'stage' => ProjectStage::CLOSURE,
            'status' => ProjectStatus::COMPLETED,
            'start_date' => '2026-01-01',
            'original_due_date' => '2026-02-01',
            'due_date' => '2026-02-01',
            'finished_date' => '2026-01-25',
            'created_by' => $this->user->id,
        ]);

        // Create Project 2: Finished late for original SLA, but on-time for revised schedule
        Project::create([
            'project_code' => 'TEST-002',
            'name' => 'Project 2',
            'requester_name' => 'Requester 2',
            'requester_department_id' => $this->department->id,
            'primary_pic_id' => $this->user->id,
            'priority' => ProjectPriority::MEDIUM,
            'stage' => ProjectStage::CLOSURE,
            'status' => ProjectStatus::COMPLETED,
            'start_date' => '2026-01-01',
            'original_due_date' => '2026-02-01',
            'due_date' => '2026-03-01',
            'finished_date' => '2026-02-15',
            'deadline_extension_count' => 1,
            'created_by' => $this->user->id,
        ]);

        // Create Project 3: Finished late for both
        Project::create([
            'project_code' => 'TEST-003',
            'name' => 'Project 3',
            'requester_name' => 'Requester 3',
            'requester_department_id' => $this->department->id,
            'primary_pic_id' => $this->user->id,
            'priority' => ProjectPriority::LOW,
            'stage' => ProjectStage::CLOSURE,
            'status' => ProjectStatus::COMPLETED,
            'start_date' => '2026-01-01',
            'original_due_date' => '2026-02-01',
            'due_date' => '2026-03-01',
            'finished_date' => '2026-03-10',
            'deadline_extension_count' => 1,
            'created_by' => $this->user->id,
        ]);

        $metrics = $this->service->getSummaryMetrics();

        // 3 completed projects:
        // Original SLA on time: Project 1 only (1/3 = 33.33%)
        // Revised Schedule on time: Project 1 & Project 2 (2/3 = 66.67%)
        expect($metrics['completed_projects'])->toBe(3);
        expect($metrics['dual_sla']['original_sla_rate'])->toBe(33.33);
        expect($metrics['dual_sla']['revised_schedule_rate'])->toBe(66.67);
    });
});
