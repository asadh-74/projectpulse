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

        Project::factory()->for($demo, 'owner')->create(['name' => 'API Integration', 'color' => '#059669']);
    }
}
