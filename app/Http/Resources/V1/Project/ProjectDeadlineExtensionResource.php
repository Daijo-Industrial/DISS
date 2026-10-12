<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectDeadlineExtensionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'old_due_date' => $this->old_due_date?->toDateString(),
            'new_due_date' => $this->new_due_date?->toDateString(),
            'reason_category' => $this->reason_category?->value ?? $this->reason_category,
            'reason_category_label' => method_exists($this->reason_category, 'label') ? $this->reason_category->label() : null,
            'reason_details' => $this->reason_details,
            'requested_by' => [
                'id' => $this->requester?->id,
                'name' => $this->requester?->name,
            ],
            'approved_by' => [
                'id' => $this->approver?->id,
                'name' => $this->approver?->name,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
