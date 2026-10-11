<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebAuthAndProjectsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['date.nager.at/*' => Http::response([], 200)]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_can_register_and_reach_the_dashboard(): void
    {
        $this->post('/register', [
            'name' => 'Asad',
            'email' => 'asad@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $this->get('/dashboard')->assertOk()->assertSee('Welcome back, Asad');
    }

    public function test_login_fails_with_bad_credentials(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_create_a_project_and_only_owner_can_delete_it(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($owner)->post('/projects', ['name' => 'Demo', 'color' => '#112233'])
            ->assertRedirect();
        $project = Project::firstOrFail();
        $this->assertTrue($project->owner->is($owner));

        $this->actingAs($other)->get("/projects/{$project->id}")->assertForbidden();
        $this->actingAs($other)->delete("/projects/{$project->id}")->assertForbidden();

        $this->actingAs($owner)->delete("/projects/{$project->id}")->assertRedirect('/projects');
        $this->assertModelMissing($project);
    }

    public function test_owner_can_invite_a_member_who_then_gets_access(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();

        $this->actingAs($owner)->post("/projects/{$project->id}/members", ['email' => $member->email])
            ->assertSessionHasNoErrors();

        $this->actingAs($member)->get("/projects/{$project->id}")->assertOk();
    }
}
