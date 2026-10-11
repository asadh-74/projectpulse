<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        return ProjectResource::collection(
            Project::visibleTo($request->user())->with('owner:id,name')->withCount('tasks')->latest()->paginate(20)
        );
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $request->user()->ownedProjects()->create($request->validated());

        return (new ProjectResource($project))->response()->setStatusCode(201);
    }

    public function show(Project $project): ProjectResource
    {
        Gate::authorize('view', $project);

        return new ProjectResource($project->load('owner:id,name')->loadCount('tasks'));
    }

    public function destroy(Project $project): Response
    {
        Gate::authorize('delete', $project);
        $project->delete();

        return response()->noContent();
    }
}
