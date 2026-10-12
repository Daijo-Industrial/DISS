<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Project;

use App\Domain\Project\Enums\CheckpointStatus;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Project\StoreCheckpointRequest;
use App\Http\Requests\Api\V1\Project\UpdateCheckpointRequest;
use App\Http\Resources\V1\Project\CheckpointAttachmentResource;
use App\Http\Resources\V1\Project\CheckpointResource;
use App\Infrastructure\Persistence\Eloquent\Models\CheckpointAttachment;
use App\Infrastructure\Persistence\Eloquent\Models\Project;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectCheckpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CheckpointController extends Controller
{
    /**
     * List checkpoints for a project.
     */
    public function index(string $projectId): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        $checkpoints = $project->checkpoints()
            ->with(['attachments.uploader', 'checkedBy'])
            ->orderBy('sequence_order')
            ->get();

        return CheckpointResource::collection($checkpoints);
    }

    /**
     * Create a new checkpoint for a project.
     */
    public function store(StoreCheckpointRequest $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $data = $request->validated();
        $data['project_id'] = $project->id;

        if (empty($data['sequence_order'])) {
            $maxSequence = (int) $project->checkpoints()->max('sequence_order');
            $data['sequence_order'] = $maxSequence + 1;
        }

        if (empty($data['status'])) {
            $data['status'] = CheckpointStatus::PENDING;
        }

        $checkpoint = ProjectCheckpoint::create($data);

        return $this->successResponse(
            new CheckpointResource($checkpoint->load(['attachments', 'checkedBy'])),
            'Checkpoint created successfully',
            201
        );
    }

    /**
     * Update checkpoint details.
     */
    public function update(UpdateCheckpointRequest $request, string $id): JsonResponse
    {
        $checkpoint = ProjectCheckpoint::findOrFail($id);

        $checkpoint->update($request->validated());

        return $this->successResponse(
            new CheckpointResource($checkpoint->fresh(['attachments', 'checkedBy'])),
            'Checkpoint updated successfully'
        );
    }

    /**
     * Mark a checkpoint as completed.
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        $checkpoint = ProjectCheckpoint::findOrFail($id);

        $checkpoint->update([
            'status' => CheckpointStatus::COMPLETED,
            'completed_date' => now()->toDateString(),
            'checked_by' => $request->user()->id,
        ]);

        return $this->successResponse(
            new CheckpointResource($checkpoint->fresh(['attachments', 'checkedBy'])),
            'Checkpoint marked as completed successfully'
        );
    }

    /**
     * Upload multipart evidence attachment for a checkpoint.
     */
    public function uploadAttachment(Request $request, string $id): JsonResponse
    {
        $checkpoint = ProjectCheckpoint::findOrFail($id);

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:15360', // 15MB max limit
                'mimes:jpg,jpeg,png,pdf,docx,xlsx,zip',
            ],
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getClientMimeType() ?: 'application/octet-stream';

        // Safe storage path: store outside web root using random UUID file name
        $safeFileName = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("project-evidence/{$checkpoint->id}", $safeFileName, 'local');

        $attachment = CheckpointAttachment::create([
            'checkpoint_id' => $checkpoint->id,
            'file_name' => $originalName,
            'file_path' => $path,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'uploaded_by' => $request->user()->id,
        ]);

        return $this->successResponse(
            new CheckpointAttachmentResource($attachment->load('uploader')),
            'Evidence attachment uploaded successfully',
            201
        );
    }

    /**
     * Download or stream evidence attachment.
     */
    public function downloadAttachment(string $attachmentId): StreamedResponse|JsonResponse
    {
        $attachment = CheckpointAttachment::findOrFail($attachmentId);

        if (! Storage::disk('local')->exists($attachment->file_path)) {
            return $this->errorResponse('Attachment file not found on storage disk', 404);
        }

        return Storage::disk('local')->download(
            $attachment->file_path,
            $attachment->file_name,
            [
                'Content-Type' => $attachment->mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Soft delete a checkpoint.
     */
    public function destroy(string $id): JsonResponse
    {
        $checkpoint = ProjectCheckpoint::findOrFail($id);
        $checkpoint->delete();

        return $this->successResponse(null, 'Checkpoint deleted successfully');
    }
}
