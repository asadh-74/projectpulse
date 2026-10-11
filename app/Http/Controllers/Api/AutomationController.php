<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints consumed by the n8n "Team Task Tracker" workflow.
 * The response is a bare JSON array so n8n turns every element into one item.
 */
class AutomationController extends Controller
{
    public function taskDigest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'completed_within_days' => ['nullable', 'integer', 'between:0,30'],
            // Test mode: send every notification to this address instead of the real people.
            'test_recipient' => ['nullable', 'email'],
        ]);

        $days = (int) ($data['completed_within_days'] ?? 1);
        $override = $data['test_recipient'] ?? null;

        $rows = Task::query()
            ->whereHas('project', fn ($q) => $q->visibleTo($request->user()))
            ->whereNotNull('assignee_id')
            ->with(['assignee:id,name,email', 'project:id,name,user_id', 'project.owner:id,name,email'])
            ->get()
            ->map(function (Task $task) use ($days, $override) {
                [$status, $daysOverdue] = match (true) {
                    $task->status === TaskStatus::Done
                        => $task->updated_at->gte(now()->subDays($days)) ? ['completed', 0] : [null, 0],
                    $task->isOverdue()
                        => ['overdue', (int) abs(today()->diffInDays($task->due_date))],
                    $task->status === TaskStatus::InProgress
                        => ['in progress', 0],
                    default => [null, 0],
                };

                if ($status === null) {
                    return null;
                }

                return [
                    'taskId' => $task->id,
                    'task' => $task->title,
                    'project' => $task->project->name,
                    'status' => $status,
                    'daysOverdue' => $daysOverdue,
                    'dueDate' => $task->due_date?->toDateString(),
                    'employeeName' => $task->assignee->name,
                    'employeeEmail' => $override ?? $task->assignee->email,
                    'managerName' => $task->project->owner->name,
                    'managerEmail' => $override ?? $task->project->owner->email,
                ];
            })
            ->filter()
            ->values();

        return response()->json($rows);
    }
}
