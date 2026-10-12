<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectStageHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_stage' => $this->from_stage?->value ?? $this->from_stage,
            'to_stage' => $this->to_stage?->value ?? $this->to_stage,
            'is_override' => (bool) $this->is_override,
            'reason' => $this->reason,
            'changed_by' => [
                'id' => $this->changedBy?->id,
                'name' => $this->changedBy?->name,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
