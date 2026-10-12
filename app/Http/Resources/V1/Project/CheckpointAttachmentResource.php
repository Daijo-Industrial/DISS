<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckpointAttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'download_url' => route('api.v1.checkpoints.attachments.download', ['attachmentId' => $this->id]),
            'uploaded_by' => [
                'id' => $this->uploader?->id,
                'name' => $this->uploader?->name,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
