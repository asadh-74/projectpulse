<?php

use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('projects', ProjectController::class)->except(['edit', 'update']);
    Route::post('projects/{project}/members', [ProjectController::class, 'addMember'])->name('projects.members.store');

    // Session + CSRF protected JSON endpoints used by the Kanban board (same controller as the token API).
    Route::prefix('ajax')->name('ajax.')->group(function () {
        Route::apiResource('projects.tasks', TaskController::class)->shallow();
    });
});
