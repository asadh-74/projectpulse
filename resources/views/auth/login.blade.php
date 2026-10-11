@extends('layouts.app')
@section('title', 'Log in')
@section('content')
<div class="max-w-sm mx-auto bg-white p-6 rounded-lg shadow-sm border">
    <h1 class="text-xl font-semibold mb-4">Log in</h1>
    <form method="POST" action="{{ url('login') }}" class="space-y-3">
        @csrf
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required autofocus class="w-full border rounded px-3 py-2">
        <input type="password" name="password" placeholder="Password" required class="w-full border rounded px-3 py-2">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember"> Remember me</label>
        <button class="w-full bg-indigo-600 text-white rounded py-2 hover:bg-indigo-700">Log in</button>
    </form>
    <p class="text-sm mt-4 text-slate-500">No account? <a class="text-indigo-600" href="{{ route('register') }}">Register</a></p>
    <p class="text-xs mt-3 text-slate-400">Demo: demo@projectpulse.test / password</p>
</div>
@endsection
