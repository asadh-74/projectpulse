<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ProjectPulse') - ProjectPulse</title>
    {{-- CDN Tailwind keeps the demo zero-build. For production, use the Vite pipeline. --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
<nav class="bg-white border-b">
    <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <a href="{{ route('dashboard') }}" class="font-bold text-indigo-600 text-lg">ProjectPulse</a>
            @auth
                <a href="{{ route('dashboard') }}" class="text-sm hover:text-indigo-600">Dashboard</a>
                <a href="{{ route('projects.index') }}" class="text-sm hover:text-indigo-600">Projects</a>
            @endauth
        </div>
        @auth
            <form method="POST" action="{{ route('logout') }}" class="flex items-center gap-3">
                @csrf
                <span class="text-sm text-slate-500">{{ auth()->user()->name }}</span>
                <button class="text-sm px-3 py-1 rounded border hover:bg-slate-100">Log out</button>
            </form>
        @endauth
    </div>
</nav>

<main class="max-w-6xl mx-auto px-4 py-8">
    @if (session('status'))
        <div class="mb-4 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2 text-sm">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded bg-red-50 border border-red-200 text-red-700 px-4 py-2 text-sm">
            <ul class="list-disc ml-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>
