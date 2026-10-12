<?php

declare(strict_types=1);

namespace App\Domain\Project\Services;

use App\Domain\Project\Enums\KpiStatus;
use App\Domain\Project\Enums\ProjectStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class KpiCalculatorService
{
    /**
     * Compute real-time KPI health status for a single project.
     */
    public function computeHealthStatus(Project $project): KpiStatus
    {
        if ($project->status === ProjectStatus::COMPLETED) {
            $finished = $project->finished_date ?? $project->closed_at?->toDateString();
            if (! $finished) {
                return KpiStatus::ON_TIME;
            }

            return Carbon::parse($finished)->lte(Carbon::parse($project->due_date))
                ? KpiStatus::ON_TIME
                : KpiStatus::LATE;
        }

        if ($project->status === ProjectStatus::ON_HOLD) {
            return KpiStatus::ON_HOLD;
        }

        if ($project->status === ProjectStatus::CANCELLED) {
            return KpiStatus::CANCELLED;
        }

        $today = Carbon::today();
        $dueDate = Carbon::parse($project->due_date)->startOfDay();

        if ($today->gt($dueDate)) {
            return KpiStatus::OVERDUE;
        }

        $daysRemaining = $today->diffInDays($dueDate, false);
        if ($daysRemaining <= 7 && $project->progress_percent < 80.00) {
            return KpiStatus::AT_RISK;
        }

        return KpiStatus::ON_TRACK;
    }

    /**
     * Calculate aggregated KPI summary metrics across projects matching query filters.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getSummaryMetrics(array $filters = []): array
    {
        $baseQuery = $this->applyFilters(Project::query(), $filters);

        $totalProjects = (clone $baseQuery)->count();
        $activeCount = (clone $baseQuery)->where('status', ProjectStatus::ACTIVE->value)->count();
        $onHoldCount = (clone $baseQuery)->where('status', ProjectStatus::ON_HOLD->value)->count();
        $cancelledCount = (clone $baseQuery)->where('status', ProjectStatus::CANCELLED->value)->count();
        $completedCount = (clone $baseQuery)->where('status', ProjectStatus::COMPLETED->value)->count();

        // Active project health breakdowns
        $today = Carbon::today()->toDateString();
        $sevenDaysFromNow = Carbon::today()->addDays(7)->toDateString();

        $overdueCount = (clone $baseQuery)
            ->where('status', ProjectStatus::ACTIVE->value)
            ->where('due_date', '<', $today)
            ->count();

        $atRiskCount = (clone $baseQuery)
            ->where('status', ProjectStatus::ACTIVE->value)
            ->where('due_date', '>=', $today)
            ->where('due_date', '<=', $sevenDaysFromNow)
            ->where('progress_percent', '<', 80.00)
            ->count();

        $onTrackCount = max(0, $activeCount - $overdueCount - $atRiskCount);

        // Dual SLA calculations (only across COMPLETED projects)
        $completedQuery = (clone $baseQuery)->where('status', ProjectStatus::COMPLETED->value);
        $originalSlaOnTime = (clone $completedQuery)
            ->whereRaw('COALESCE(finished_date, DATE(closed_at)) <= original_due_date')
            ->count();

        $revisedScheduleOnTime = (clone $completedQuery)
            ->whereRaw('COALESCE(finished_date, DATE(closed_at)) <= due_date')
            ->count();

        $originalSlaRate = $completedCount > 0
            ? round(($originalSlaOnTime / $completedCount) * 100, 2)
            : 0.00;

        $revisedScheduleRate = $completedCount > 0
            ? round(($revisedScheduleOnTime / $completedCount) * 100, 2)
            : 0.00;

        // Anti-gaming / Attrition metrics
        $cancellationRate = $totalProjects > 0
            ? round(($cancelledCount / $totalProjects) * 100, 2)
            : 0.00;

        $totalExtensions = (int) (clone $baseQuery)->sum('deadline_extension_count');
        $avgExtensions = $totalProjects > 0
            ? round($totalExtensions / $totalProjects, 2)
            : 0.00;

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeCount,
            'on_hold_projects' => $onHoldCount,
            'cancelled_projects' => $cancelledCount,
            'completed_projects' => $completedCount,
            'health_breakdown' => [
                'on_track' => $onTrackCount,
                'at_risk' => $atRiskCount,
                'overdue' => $overdueCount,
            ],
            'dual_sla' => [
                'original_sla_rate' => $originalSlaRate,
                'original_sla_on_time_count' => $originalSlaOnTime,
                'revised_schedule_rate' => $revisedScheduleRate,
                'revised_schedule_on_time_count' => $revisedScheduleOnTime,
            ],
            'governance' => [
                'cancellation_rate' => $cancellationRate,
                'total_extensions' => $totalExtensions,
                'average_extensions_per_project' => $avgExtensions,
            ],
        ];
    }

    /**
     * Get PIC leaderboard and individual performance statistics.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, mixed>
     */
    public function getPicLeaderboard(array $filters = []): Collection
    {
        $query = $this->applyFilters(Project::query(), $filters)
            ->with(['pic:id,name,email,employee_id'])
            ->get();

        return $query->groupBy('primary_pic_id')->map(function (Collection $picProjects) {
            /** @var Project $first */
            $first = $picProjects->first();
            $pic = $first->pic;

            $total = $picProjects->count();
            $active = $picProjects->where('status', ProjectStatus::ACTIVE)->count();
            $completed = $picProjects->where('status', ProjectStatus::COMPLETED);
            $completedCount = $completed->count();

            $today = Carbon::today()->toDateString();
            $overdueActive = $picProjects
                ->where('status', ProjectStatus::ACTIVE)
                ->filter(fn (Project $p) => Carbon::parse($p->due_date)->toDateString() < $today)
                ->count();

            $originalOnTime = $completed->filter(function (Project $p) {
                $finish = $p->finished_date ?? $p->closed_at?->toDateString();

                return $finish && Carbon::parse($finish)->lte(Carbon::parse($p->original_due_date));
            })->count();

            $revisedOnTime = $completed->filter(function (Project $p) {
                $finish = $p->finished_date ?? $p->closed_at?->toDateString();

                return $finish && Carbon::parse($finish)->lte(Carbon::parse($p->due_date));
            })->count();

            $originalRate = $completedCount > 0 ? round(($originalOnTime / $completedCount) * 100, 2) : 0.00;
            $revisedRate = $completedCount > 0 ? round(($revisedOnTime / $completedCount) * 100, 2) : 0.00;

            return [
                'pic_id' => $pic?->id,
                'pic_name' => $pic?->name ?? 'Unassigned',
                'employee_id' => $pic?->employee_id,
                'total_assigned' => $total,
                'active_count' => $active,
                'completed_count' => $completedCount,
                'overdue_active_count' => $overdueActive,
                'original_sla_rate' => $originalRate,
                'revised_schedule_rate' => $revisedRate,
            ];
        })->values();
    }

    /**
     * Apply common query filters (department, pic, priority, date ranges).
     *
     * @param Builder<Project> $query
     * @param array<string, mixed> $filters
     * @return Builder<Project>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['department_id'])) {
            $query->where('requester_department_id', $filters['department_id']);
        }

        if (! empty($filters['primary_pic_id'])) {
            $query->where('primary_pic_id', $filters['primary_pic_id']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query;
    }
}
