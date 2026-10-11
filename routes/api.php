<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/token', [AuthTokenController::class, 'store'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::delete('auth/token', [AuthTokenController::class, 'destroy']);

        Route::apiResource('projects', ProjectController::class)->except('update');
        Route::apiResource('projects.tasks', TaskController::class)->shallow();
        Route::post('tasks/{task}/comments', [CommentController::class, 'store']);
    });
});
