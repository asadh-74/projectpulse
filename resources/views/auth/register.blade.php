@extends('layouts.app')
@section('title', 'Register')
@section('content')
<div class="max-w-sm mx-auto bg-white p-6 rounded-lg shadow-sm border">
    <h1 class="text-xl font-semibold mb-4">Create account</h1>
    <form method="POST" action="{{ url('register') }}" class="space-y-3">
        @csrf
        <input name="name" value="{{ old('name') }}" placeholder="Name" required class="w-full border rounded px-3 py-2">
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required class="w-full border rounded px-3 py-2">
        <input type="password" name="password" placeholder="Password (min 8)" required class="w-full border rounded px-3 py-2">
        <input type="password" name="password_confirmation" placeholder="Confirm password" required class="w-full border rounded px-3 py-2">
        <button class="w-full bg-indigo-600 text-white rounded py-2 hover:bg-indigo-700">Register</button>
    </form>
    <p class="text-sm mt-4 text-slate-500">Have an account? <a class="text-indigo-600" href="{{ route('login') }}">Log in</a></p>
</div>
@endsection
