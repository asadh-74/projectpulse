@extends('layouts.app')
@section('title', 'New project')
@section('content')
<div class="max-w-lg bg-white border rounded-lg p-6">
    <h1 class="text-xl font-semibold mb-4">New project</h1>
    <form method="POST" action="{{ route('projects.store') }}" class="space-y-3">
        @csrf
        <input name="name" value="{{ old('name') }}" placeholder="Project name" required class="w-full border rounded px-3 py-2">
        <textarea name="description" rows="3" placeholder="Description" class="w-full border rounded px-3 py-2">{{ old('description') }}</textarea>
        <label class="flex items-center gap-3 text-sm">Colour <input type="color" name="color" value="{{ old('color', '#4f46e5') }}"></label>
        <button class="bg-indigo-600 text-white rounded px-4 py-2 hover:bg-indigo-700">Create</button>
    </form>
</div>
@endsection
