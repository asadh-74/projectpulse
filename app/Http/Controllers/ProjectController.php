<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::visibleTo($request->user())
            ->withCount('tasks')->with('owner:id,name')->latest()->get();

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = $request->user()->ownedProjects()->create($request->validated());

        return redirect()->route('projects.show', $project)->with('status', 'Project created.');
    }

    public function show(Project $project)
    {
        Gate::authorize('view', $project);

        return view('projects.show', [
            'project' => $project->load('owner:id,name'),
            'team' => $project->team()->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])->values(),
        ]);
    }

    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);
        $project->delete();

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }

    public function addMember(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $data = $request->validate(['email' => ['required', 'email', 'exists:users,email']]);
        $user = User::where('email', $data['email'])->firstOrFail();

        if ($user->id !== $project->user_id) {
            $project->members()->syncWithoutDetaching([$user->id]);
        }

        return back()->with('status', "{$user->name} added to the project.");
    }
}
