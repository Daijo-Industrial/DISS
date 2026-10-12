<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Project;

use App\Domain\Project\Enums\ProjectStatus;
use App\Domain\Project\Services\KpiCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var KpiCalculatorService $kpiService */
        $kpiService = app(KpiCalculatorService::class);
        $health = $kpiService->computeHealthStatus($this->resource);

        return [
            'id' => $this->id,
            'project_code' => $this->project_code,
            'name' => $this->name,
            'description' => $this->description,
            'priority' => $this->priority?->value ?? $this->priority,
            'stage' => $this->stage?->value ?? $this->stage,
            'status' => $this->status?->value ?? $this->status,
            'health_status' => $health->value,
            'health_status_label' => $health->label(),
            'start_date' => $this->start_date?->toDateString(),
            'original_due_date' => $this->original_due_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'finished_date' => $this->finished_date?->toDateString(),
            'progress_percent' => (float) $this->progress_percent,
            'deadline_extension_count' => (int) $this->deadline_extension_count,
            'hold_details' => $this->status === ProjectStatus::ON_HOLD ? [
                'paused_at' => $this->paused_at?->toIso8601String(),
                'hold_reason' => $this->hold_reason,
            ] : null,
            'cancellation_details' => $this->status === ProjectStatus::CANCELLED ? [
                'cancelled_at' => $this->cancelled_at?->toIso8601String(),
                'reason_category' => $this->cancellation_reason_category,
                'notes' => $this->cancellation_notes,
            ] : null,
            'requester_name' => $this->requester_name,
            'department' => [
                'id' => $this->department?->id,
                'name' => $this->department?->name,
                'code' => $this->department?->code,
            ],
            'pic' => [
                'id' => $this->pic?->id,
                'name' => $this->pic?->name,
                'email' => $this->pic?->email,
                'employee_id' => $this->pic?->employee_id,
            ],
            'creator' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ],
            'checkpoints' => CheckpointResource::collection($this->whenLoaded('checkpoints')),
            'extensions' => ProjectDeadlineExtensionResource::collection($this->whenLoaded('extensions')),
            'stage_histories' => ProjectStageHistoryResource::collection($this->whenLoaded('stageHistories')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
