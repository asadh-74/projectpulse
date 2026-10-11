<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Services\HolidayService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, HolidayService $holidays)
    {
        $user = $request->user();

        $myOpen = Task::where('assignee_id', $user->id)->where('status', '!=', TaskStatus::Done->value);

        return view('dashboard', [
            'projectCount' => Project::visibleTo($user)->count(),
            'openCount' => (clone $myOpen)->count(),
            'overdueCount' => (clone $myOpen)->whereDate('due_date', '<', today())->count(),
            'upcomingTasks' => (clone $myOpen)->with('project:id,name,color')->orderByRaw('due_date IS NULL')->orderBy('due_date')->limit(8)->get(),
            'holidays' => $holidays->upcoming(5),
        ]);
    }
}
