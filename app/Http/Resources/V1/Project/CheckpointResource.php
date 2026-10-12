<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckpointResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'sequence_order' => $this->sequence_order,
            'is_required' => (bool) $this->is_required,
            'status' => $this->status?->value ?? $this->status,
            'due_date' => $this->due_date?->toDateString(),
            'completed_date' => $this->completed_date?->toDateString(),
            'checked_by' => $this->checkedBy ? [
                'id' => $this->checkedBy->id,
                'name' => $this->checkedBy->name,
            ] : null,
            'notes' => $this->notes,
            'attachments' => CheckpointAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
