<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Shared by the token API (/api/v1, Sanctum) and the session-backed
 * AJAX routes (/ajax) used by the Kanban board.
 */
class TaskController extends Controller
{
    public function index(Request $request, Project $project)
    {
        Gate::authorize('view', $project);

        $request->validate([
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'between:1,200'],
        ]);

        $tasks = $project->tasks()
            ->with('assignee:id,name')
            ->withCount('comments')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('search'), fn ($q, $s) => $q->where('title', 'like', '%'.$s.'%'))
            ->orderBy('position')->orderBy('id')
            ->paginate($request->integer('per_page', 100));

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $task = $project->tasks()->create([
            ...$request->validated(),
            'position' => ($project->tasks()->max('position') ?? 0) + 1,
        ]);

        return (new TaskResource($task->load('assignee:id,name')->loadCount('comments')))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        return new TaskResource($task->load(['assignee:id,name', 'comments.author:id,name'])->loadCount('comments'));
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $task->update($request->validated());

        return new TaskResource($task->load('assignee:id,name')->loadCount('comments'));
    }

    public function destroy(Task $task): Response
    {
        Gate::authorize('delete', $task);
        $task->delete();

        return response()->noContent();
    }
}
