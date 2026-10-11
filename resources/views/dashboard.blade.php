@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<h1 class="text-2xl font-semibold mb-6">Welcome back, {{ auth()->user()->name }}</h1>

<div class="grid sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-white border rounded-lg p-4"><div class="text-sm text-slate-500">Projects</div><div class="text-3xl font-bold">{{ $projectCount }}</div></div>
    <div class="bg-white border rounded-lg p-4"><div class="text-sm text-slate-500">My open tasks</div><div class="text-3xl font-bold">{{ $openCount }}</div></div>
    <div class="bg-white border rounded-lg p-4"><div class="text-sm text-slate-500">Overdue</div><div class="text-3xl font-bold text-red-600">{{ $overdueCount }}</div></div>
</div>

<div class="grid md:grid-cols-3 gap-6">
    <section class="md:col-span-2 bg-white border rounded-lg p-4">
        <h2 class="font-semibold mb-3">My upcoming tasks</h2>
        @forelse ($upcomingTasks as $task)
            <a href="{{ route('projects.show', $task->project) }}" class="flex items-center justify-between py-2 border-t first:border-0 hover:bg-slate-50">
                <span class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full" style="background: {{ $task->project->color }}"></span>
                    {{ $task->title }}
                </span>
                <span class="text-xs {{ $task->isOverdue() ? 'text-red-600 font-medium' : 'text-slate-500' }}">
                    {{ $task->due_date?->format('d M Y') ?? 'No due date' }}
                </span>
            </a>
        @empty
            <p class="text-sm text-slate-500">Nothing assigned to you. 🎉</p>
        @endforelse
    </section>

    <section class="bg-white border rounded-lg p-4">
        <h2 class="font-semibold mb-1">Upcoming public holidays</h2>
        <p class="text-xs text-slate-400 mb-3">Live from the Nager.Date API (cached 24h)</p>
        @forelse ($holidays as $h)
            <div class="flex justify-between text-sm py-1"><span>{{ $h['name'] }}</span><span class="text-slate-500">{{ \Illuminate\Support\Carbon::parse($h['date'])->format('d M') }}</span></div>
        @empty
            <p class="text-sm text-slate-500">Holiday service unavailable.</p>
        @endforelse
    </section>
</div>
@endsection
