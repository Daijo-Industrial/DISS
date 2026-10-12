<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Project;

use App\Domain\Project\Enums\ExtensionReasonCategory;
use App\Domain\Project\Services\ProjectWorkflowService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Project\StoreExtensionRequest;
use App\Http\Resources\V1\Project\ProjectDeadlineExtensionResource;
use App\Infrastructure\Persistence\Eloquent\Models\Project;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProjectExtensionController extends Controller
{
    public function __construct(
        private readonly ProjectWorkflowService $workflowService
    ) {}

    /**
     * List extensions for a project.
     */
    public function index(string $projectId): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        $extensions = $project->extensions()
            ->with(['requester', 'approver'])
            ->latest('created_at')
            ->get();

        return ProjectDeadlineExtensionResource::collection($extensions);
    }

    /**
     * Request and apply a deadline extension.
     */
    public function store(StoreExtensionRequest $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        try {
            $category = ExtensionReasonCategory::from($request->reason_category);

            $extension = $this->workflowService->requestDeadlineExtension(
                $project,
                $request->new_due_date,
                $category,
                $request->reason_details,
                $request->user(),
                $request->user() // In MVP, authorized approver records the extension
            );

            return $this->successResponse(
                new ProjectDeadlineExtensionResource($extension->load(['requester', 'approver'])),
                'Deadline extension applied successfully',
                201
            );
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
