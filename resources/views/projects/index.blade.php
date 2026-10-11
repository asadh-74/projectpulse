@extends('layouts.app')
@section('title', 'Projects')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-semibold">Projects</h1>
    <a href="{{ route('projects.create') }}" class="bg-indigo-600 text-white text-sm px-4 py-2 rounded hover:bg-indigo-700">New project</a>
</div>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse ($projects as $project)
        <a href="{{ route('projects.show', $project) }}" class="block bg-white border rounded-lg p-4 hover:shadow-md transition" style="border-top: 4px solid {{ $project->color }}">
            <div class="font-semibold">{{ $project->name }}</div>
            <p class="text-sm text-slate-500 line-clamp-2 mt-1">{{ $project->description }}</p>
            <div class="text-xs text-slate-400 mt-3">{{ $project->tasks_count }} tasks · owner {{ $project->owner->name }}</div>
        </a>
    @empty
        <p class="text-slate-500">No projects yet.</p>
    @endforelse
</div>
@endsection
