<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Project;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Project\StoreProjectRequest;
use App\Http\Requests\Api\V1\Project\UpdateProjectRequest;
use App\Http\Resources\V1\Project\ProjectDetailResource;
use App\Http\Resources\V1\Project\ProjectResource;
use App\Infrastructure\Persistence\Eloquent\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProjectController extends Controller
{
    /**
     * List projects with pagination and filtering.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Project::with(['department', 'pic'])->latest('created_at');

        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department_id')) {
            $query->where('requester_department_id', $request->department_id);
        }

        if ($request->filled('pic_id')) {
            $query->where('primary_pic_id', $request->pic_id);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('project_code', 'like', $search);
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $projects = $query->paginate($perPage);

        return ProjectResource::collection($projects);
    }

    /**
     * Create a new project.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $project = Project::create($data);
        $project->refresh();

        return $this->successResponse(
            new ProjectResource($project->load(['department', 'pic'])),
            'Project created successfully',
            201
        );
    }

    /**
     * Retrieve single project detail.
     */
    public function show(string $id): JsonResponse
    {
        $project = Project::with([
            'department',
            'pic',
            'creator',
            'checkpoints.attachments.uploader',
            'checkpoints.checkedBy',
            'extensions.requester',
            'extensions.approver',
            'stageHistories.changedBy',
        ])->findOrFail($id);

        return $this->successResponse(
            new ProjectDetailResource($project),
            'Project retrieved successfully'
        );
    }

    /**
     * Update project metadata.
     */
    public function update(UpdateProjectRequest $request, string $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $project->update($data);

        return $this->successResponse(
            new ProjectResource($project->fresh(['department', 'pic'])),
            'Project updated successfully'
        );
    }

    /**
     * Soft delete a project.
     */
    public function destroy(string $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $project->delete();

        return $this->successResponse(null, 'Project deleted successfully');
    }
}
