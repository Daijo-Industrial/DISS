<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Project;

use App\Domain\Project\Enums\ProjectStatus;
use App\Domain\Project\Services\KpiCalculatorService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\V1\Project\ProjectResource;
use App\Infrastructure\Persistence\Eloquent\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ProjectDashboardController extends Controller
{
    public function __construct(
        private readonly KpiCalculatorService $kpiService
    ) {}

    /**
     * Get executive dashboard KPI summary cards (Dual SLA, counts, health).
     */
    public function summary(Request $request): JsonResponse
    {
        $filters = $request->only(['department_id', 'primary_pic_id', 'priority', 'from_date', 'to_date']);

        $metrics = $this->kpiService->getSummaryMetrics($filters);

        return $this->successResponse($metrics, 'KPI summary retrieved successfully');
    }

    /**
     * Get distribution of projects across lifecycle stages for mobile charts.
     */
    public function stageDistribution(Request $request): JsonResponse
    {
        $query = Project::query();

        if ($request->filled('department_id')) {
            $query->where('requester_department_id', $request->department_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $distribution = $query->select('stage', DB::raw('count(*) as count'))
            ->groupBy('stage')
            ->pluck('count', 'stage')
            ->toArray();

        return $this->successResponse($distribution, 'Stage distribution retrieved successfully');
    }

    /**
     * Get PIC leaderboard ranking.
     */
    public function picLeaderboard(Request $request): JsonResponse
    {
        $filters = $request->only(['department_id', 'from_date', 'to_date']);

        $leaderboard = $this->kpiService->getPicLeaderboard($filters);

        return $this->successResponse($leaderboard, 'PIC leaderboard retrieved successfully');
    }

    /**
     * Get active projects approaching deadlines within the next 30 days.
     */
    public function upcomingDeadlines(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);
        $today = Carbon::today()->toDateString();
        $targetDate = Carbon::today()->addDays($days)->toDateString();

        $projects = Project::with(['department', 'pic'])
            ->where('status', ProjectStatus::ACTIVE->value)
            ->where('due_date', '>=', $today)
            ->where('due_date', '<=', $targetDate)
            ->orderBy('due_date', 'asc')
            ->limit(20)
            ->get();

        return $this->successResponse(
            ProjectResource::collection($projects),
            'Upcoming deadlines retrieved successfully'
        );
    }
}
