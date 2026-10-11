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

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['date.nager.at/*' => Http::response([], 200)]);
    }

    public function test_guests_cannot_access_the_api(): void
    {
        $project = Project::factory()->create();

        $this->getJson("/api/v1/projects/{$project->id}/tasks")->assertUnauthorized();
    }

    public function test_owner_can_create_and_list_tasks(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/projects/{$project->id}/tasks", [
            'title' => 'Write PHPUnit tests',
            'priority' => 'high',
            'assignee_id' => $owner->id,
        ])->assertCreated()
            ->assertJsonPath('data.title', 'Write PHPUnit tests')
            ->assertJsonPath('data.status', 'todo')
            ->assertJsonPath('data.assignee.id', $owner->id);

        $this->getJson("/api/v1/projects/{$project->id}/tasks")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_validation_errors_are_returned_as_422(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/projects/{$project->id}/tasks", ['title' => '', 'status' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'status']);
    }

    public function test_outsiders_cannot_view_or_modify_tasks(): void
    {
        $project = Project::factory()->create();
        $task = Task::factory()->for($project)->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/projects/{$project->id}/tasks")->assertForbidden();
        $this->postJson("/api/v1/projects/{$project->id}/tasks", ['title' => 'x'])->assertForbidden();
        $this->patchJson("/api/v1/tasks/{$task->id}", ['status' => 'done'])->assertForbidden();
        $this->deleteJson("/api/v1/tasks/{$task->id}")->assertForbidden();
    }

    public function test_members_can_move_tasks_between_columns(): void
    {
        $project = Project::factory()->create();
        $member = User::factory()->create();
        $project->members()->attach($member);
        $task = Task::factory()->for($project)->create(['status' => TaskStatus::Todo]);
        Sanctum::actingAs($member);

        $this->patchJson("/api/v1/tasks/{$task->id}", ['status' => 'done'])
            ->assertOk()
            ->assertJsonPath('data.status', 'done');

        $this->assertSame(TaskStatus::Done, $task->fresh()->status);
    }

    public function test_assignee_must_belong_to_the_project_team(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $stranger = User::factory()->create();
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/projects/{$project->id}/tasks", ['title' => 'x', 'assignee_id' => $stranger->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('assignee_id');
    }

    public function test_tasks_can_be_filtered_by_status_and_search(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        Task::factory()->for($project)->create(['title' => 'Fix login bug', 'status' => 'todo']);
        Task::factory()->for($project)->create(['title' => 'Deploy app', 'status' => 'done']);
        Sanctum::actingAs($owner);

        $this->getJson("/api/v1/projects/{$project->id}/tasks?status=done")->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/projects/{$project->id}/tasks?search=login")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Fix login bug');
    }

    public function test_token_endpoint_issues_a_working_token(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);
        Project::factory()->for($user, 'owner')->create();

        $token = $this->postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'secret-pass'])
            ->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/v1/projects')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
    }

    public function test_comments_can_be_added_to_a_task(): void
    {
        $owner = User::factory()->create();
        $task = Task::factory()->for(Project::factory()->for($owner, 'owner'))->create();
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/tasks/{$task->id}/comments", ['body' => 'Looks good'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Looks good');

        $this->getJson("/api/v1/tasks/{$task->id}")->assertJsonCount(1, 'data.comments');
    }
}
