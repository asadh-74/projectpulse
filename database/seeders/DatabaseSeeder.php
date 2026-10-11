<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@projectpulse.test',
            'password' => 'password',
        ]);
        $teammate = User::factory()->create([
            'name' => 'Sara Khan',
            'email' => 'sara@projectpulse.test',
            'password' => 'password',
        ]);

        // Stable token for the n8n workflow (Bearer <N8N_API_TOKEN>).
        if ($plain = config('projectpulse.n8n_token')) {
            $demo->tokens()->create([
                'name' => 'n8n',
                'token' => hash('sha256', $plain),
                'abilities' => ['*'],
            ]);
        }

        $project = Project::factory()->for($demo, 'owner')->create([
            'name' => 'Website Redesign',
            'description' => 'Client portal revamp - sprint 4',
            'color' => '#4f46e5',
        ]);
        $project->members()->attach($teammate);

        foreach (TaskStatus::cases() as $status) {
            Task::factory()->count(3)->for($project)->create([
                'status' => $status,
                'assignee_id' => fake()->randomElement([$demo->id, $teammate->id]),
            ]);
        }

        // Deterministic rows so every n8n branch has something to act on.
        Task::factory()->for($project)->create([
            'title' => 'Fix login redirect bug', 'status' => TaskStatus::InProgress,
            'due_date' => today()->subDays(5), 'assignee_id' => $teammate->id,
        ]);
        Task::factory()->for($project)->create([
            'title' => 'Update API documentation', 'status' => TaskStatus::Todo,
            'due_date' => today()->subDays(1), 'assignee_id' => $teammate->id,
        ]);
        Task::factory()->for($project)->create([
            'title' => 'Ship Kanban drag-and-drop', 'status' => TaskStatus::Done,
            'due_date' => today(), 'assignee_id' => $demo->id,
        ]);

        Project::factory()->for($demo, 'owner')->create(['name' => 'API Integration', 'color' => '#059669']);
    }
}
