<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Project;

use App\Domain\Project\Enums\CancellationCategory;
use App\Domain\Project\Enums\ProjectStage;
use App\Domain\Project\Services\ProjectWorkflowService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Project\ApproveClosureRequest;
use App\Http\Requests\Api\V1\Project\CancelProjectRequest;
use App\Http\Requests\Api\V1\Project\HoldProjectRequest;
use App\Http\Requests\Api\V1\Project\RejectClosureRequest;
use App\Http\Requests\Api\V1\Project\ResumeProjectRequest;
use App\Http\Requests\Api\V1\Project\TransitionStageRequest;
use App\Http\Resources\V1\Project\ProjectResource;
use App\Infrastructure\Persistence\Eloquent\Models\Project;
use DomainException;
use Illuminate\Http\JsonResponse;

final class ProjectWorkflowController extends Controller
{
    public function __construct(
        private readonly ProjectWorkflowService $workflowService
    ) {}

    /**
     * Transition a project's lifecycle stage.
     */
    public function transitionStage(TransitionStageRequest $request, string $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        try {
            $targetStage = ProjectStage::from($request->target_stage);
            $updated = $this->workflowService->transitionStage(
                $project,
                $targetStage,
                $request->user(),
                $request->reason,
                (bool) $request->is_override
            );

            return $this->successResponse(
                new ProjectResource($updated->load(['department', 'pic'])),
                "Project stage transitioned to {$targetStage->value} successfully"
            );
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Put a project on hold.
     */
    public function hold(HoldProjectRequest $request, string $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        try {
            $updated = $this->workflowService->hold(
                $project,
                $request->reason,
                $request->user()
            );

            return $this->successResponse(
                new ProjectResource($updated->load(['department', 'pic'])),
                'Project placed on hold successfully'
            );
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Resume an on-hold project.
     */
    public function resume(ResumeProjectRequest $request, string $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        try {
            $updated = $this->workflowService->resume(
                $project,
                (bool) $request->extend_due_date,
                $request->user()
            );

            return $this->successResponse(
                new ProjectResource($updated->load(['department', 'pic'])),
                'Project resumed successfully'
            );
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Cancel a project.
     */
    public function cancel(CancelProjectRequest $request, string $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        try {
            $category = CancellationCategory::from($request->reason_category);
            $updated = $this->workflowService->cancel(
                $project,
                $category,
                $request->notes,
                $request->user()
            );

            return $this->successResponse(
                new ProjectResource($updated->load(['department', 'pic'])),
                'Project cancelled successfully'
            );
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Approve project closure.
     */
    public function approveClosure(ApproveClosureRequest $request, string $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        try {
            $updated = $this->workflowService->approveClosure(
                $project,
                $request->user(),
                $request->comments
            );

            return $this->successResponse(
                new ProjectResource($updated->load(['department', 'pic'])),
                'Project closure approved successfully'
            );
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Reject project closure and revert stage.
     */
    public function rejectClosure(RejectClosureRequest $request, string $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        try {
            $returnStage = $request->return_stage
                ? ProjectStage::from($request->return_stage)
                : ProjectStage::MONITORING;

            $updated = $this->workflowService->rejectClosure(
                $project,
                $request->user(),
                $request->comments,
                $returnStage
            );

            return $this->successResponse(
                new ProjectResource($updated->load(['department', 'pic'])),
                "Project closure rejected; stage reverted to {$returnStage->value}"
            );
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
