<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('view', $task);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $comment = $task->comments()->make($data);
        $comment->user_id = $request->user()->id;
        $comment->save();

        return (new CommentResource($comment->load('author:id,name')))
            ->response()->setStatusCode(201);
    }
}
