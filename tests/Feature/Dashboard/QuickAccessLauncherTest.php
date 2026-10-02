<?php

namespace Tests\Feature\Dashboard;

use App\Infrastructure\Persistence\Eloquent\Models\ApprovalRequest;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Livewire\Dashboard\Widgets\MySubmissions;
use App\Livewire\Dashboard\Widgets\QuickAccessLauncher;
use App\Models\UserPageVisit;
use App\Models\UserPinnedRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuickAccessLauncherTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_access_launcher_component_renders_successfully()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QuickAccessLauncher::class)
            ->assertStatus(200)
            ->assertSee('Quick Launch');
    }

    public function test_user_can_pin_and_unpin_routes_up_to_max_limit()
    {
        $user = User::factory()->create();

        $test = Livewire::actingAs($user)
            ->test(QuickAccessLauncher::class)
            ->call('pinRoute', 'home')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('user_pinned_routes', [
            'user_id' => $user->id,
            'route_name' => 'home',
        ]);

        $test->call('unpinRoute', 'home')
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('user_pinned_routes', [
            'user_id' => $user->id,
            'route_name' => 'home',
        ]);
    }

    public function test_user_cannot_exceed_max_pinned_shortcuts()
    {
        $user = User::factory()->create();

        // Pre-fill 8 pinned routes
        for ($i = 1; $i <= 8; $i++) {
            UserPinnedRoute::create([
                'user_id' => $user->id,
                'route_name' => "route.test.{$i}",
                'pinned_at' => now(),
            ]);
        }

        Livewire::actingAs($user)
            ->test(QuickAccessLauncher::class)
            ->call('pinRoute', 'home')
            ->assertDispatched('toast', fn ($event, $params) => $params['type'] === 'warning');

        $this->assertEquals(8, UserPinnedRoute::where('user_id', $user->id)->count());
    }

    public function test_user_can_remove_a_visited_route_from_quick_access()
    {
        $user = User::factory()->create();

        UserPageVisit::create([
            'user_id' => $user->id,
            'route_name' => 'home',
            'visit_count' => 10,
            'last_visited_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(QuickAccessLauncher::class)
            ->call('removeVisit', 'home')
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('user_page_visits', [
            'user_id' => $user->id,
            'route_name' => 'home',
        ]);
    }

    public function test_my_submissions_widget_renders_user_requests()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(MySubmissions::class)
            ->assertStatus(200)
            ->assertSee('My Recent Requests');
    }

    public function test_home_page_renders_compact_header_and_my_submissions()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/home');

        $response->assertStatus(200);
        $response->assertSee('My Recent Requests');
        // Sidebar retains Quick Access
        $response->assertSee('Quick Access');
        // Redundant home tiles should not be rendered
        $response->assertDontSee('Quick Launch');
    }
}
