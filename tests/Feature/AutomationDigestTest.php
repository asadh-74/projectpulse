<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AutomationDigestTest extends TestCase
{
    use RefreshDatabase;

    private function setUpProject(): array
    {
        Http::fake(['date.nager.at/*' => Http::response([], 200)]);

        $manager = User::factory()->create(['email' => 'boss@example.com']);
        $employee = User::factory()->create(['email' => 'dev@example.com', 'name' => 'Dev']);
        $project = Project::factory()->for($manager, 'owner')->create();
        $project->members()->attach($employee);

        return [$manager, $employee, $project];
    }

    public function test_digest_maps_tasks_to_the_n8n_status_vocabulary(): void
    {
        [$manager, $employee, $project] = $this->setUpProject();

        Task::factory()->for($project)->create([
            'title' => 'Late', 'status' => TaskStatus::InProgress,
            'due_date' => today()->subDays(5), 'assignee_id' => $employee->id,
        ]);
        Task::factory()->for($project)->create([
            'title' => 'Working', 'status' => TaskStatus::InProgress,
            'due_date' => today()->addDays(3), 'assignee_id' => $employee->id,
        ]);
        Task::factory()->for($project)->create([
            'title' => 'Just done', 'status' => TaskStatus::Done, 'assignee_id' => $employee->id,
        ]);
        Task::factory()->for($project)->create([
            'title' => 'Done long ago', 'status' => TaskStatus::Done,
            'assignee_id' => $employee->id, 'updated_at' => now()->subDays(10),
        ]);
        Task::factory()->for($project)->create([
            'title' => 'Not started', 'status' => TaskStatus::Todo,
            'due_date' => today()->addDays(9), 'assignee_id' => $employee->id,
        ]);
        Task::factory()->for($project)->create([
            'title' => 'Nobody owns it', 'status' => TaskStatus::InProgress, 'assignee_id' => null,
        ]);

        Sanctum::actingAs($manager);

        $rows = collect($this->getJson('/api/v1/automation/task-digest')->assertOk()->json())->keyBy('task');

        $this->assertCount(3, $rows);
        $this->assertSame('overdue', $rows['Late']['status']);
        $this->assertSame(5, $rows['Late']['daysOverdue']);
        $this->assertSame('in progress', $rows['Working']['status']);
        $this->assertSame('completed', $rows['Just done']['status']);
        $this->assertSame('dev@example.com', $rows['Late']['employeeEmail']);
        $this->assertSame('boss@example.com', $rows['Late']['managerEmail']);
    }

    public function test_test_recipient_overrides_all_addresses(): void
    {
        [$manager, $employee, $project] = $this->setUpProject();
        Task::factory()->for($project)->create([
            'status' => TaskStatus::InProgress, 'assignee_id' => $employee->id,
        ]);
        Sanctum::actingAs($manager);

        $row = $this->getJson('/api/v1/automation/task-digest?test_recipient=me@example.com')
            ->assertOk()->json('0');

        $this->assertSame('me@example.com', $row['employeeEmail']);
        $this->assertSame('me@example.com', $row['managerEmail']);
    }

    public function test_digest_only_includes_projects_the_token_owner_can_see(): void
    {
        [, $employee, $project] = $this->setUpProject();
        Task::factory()->for($project)->create(['status' => TaskStatus::InProgress, 'assignee_id' => $employee->id]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/automation/task-digest')->assertOk()->assertExactJson([]);
    }

    public function test_digest_requires_authentication(): void
    {
        $this->getJson('/api/v1/automation/task-digest')->assertUnauthorized();
    }
}
