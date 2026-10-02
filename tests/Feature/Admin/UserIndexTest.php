<?php

namespace Tests\Feature\Admin;

use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Livewire\Admin\Users\UserEdit;
use App\Livewire\Admin\Users\UserIndex;
use App\Models\UserPageVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserIndexTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super-admin', 'web');
        Permission::findOrCreate('user.view', 'web');
        Permission::findOrCreate('user.update', 'web');

        $this->admin = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('super-admin');
    }

    public function test_user_index_renders_successfully()
    {
        Livewire::actingAs($this->admin)
            ->test(UserIndex::class)
            ->assertStatus(200)
            ->assertSee('User Management');
    }

    public function test_it_displays_verified_at_and_unverified_badges_correctly()
    {
        $verifiedUser = User::factory()->create([
            'name' => 'Verified Account',
            'email' => 'verified@example.com',
            'email_verified_at' => now()->subDays(5),
        ]);

        $unverifiedUser = User::factory()->unverified()->create([
            'name' => 'Unverified Account',
            'email' => 'unverified@example.com',
        ]);

        Livewire::actingAs($this->admin)
            ->test(UserIndex::class)
            ->assertSee('Verified Account')
            ->assertSee('Unverified Account')
            ->assertSee('Verified ' . $verifiedUser->email_verified_at->format('M d, Y'))
            ->assertSee('Unverified');
    }

    public function test_it_calculates_and_displays_last_active_timestamp_from_page_visits()
    {
        $activeUser = User::factory()->create([
            'name' => 'Active Worker',
            'email' => 'active@example.com',
        ]);

        $visitTime = now()->subHours(2);
        UserPageVisit::create([
            'user_id' => $activeUser->id,
            'route_name' => 'home',
            'visit_count' => 12,
            'last_visited_at' => $visitTime,
        ]);

        $diff = Carbon::parse($visitTime)->diffForHumans();

        Livewire::actingAs($this->admin)
            ->test(UserIndex::class)
            ->assertSee('Active Worker')
            ->assertSee('Active ' . $diff);
    }

    public function test_dormant_users_are_detected_and_can_be_filtered()
    {
        // 1. Dormant User: Active, created 40 days ago, never visited
        $dormantNeverVisited = User::factory()->create([
            'name' => 'Dormant Never Visited',
            'email' => 'never@example.com',
            'is_active' => true,
            'created_at' => now()->subDays(40),
        ]);

        // 2. Dormant User: Active, created 60 days ago, last visited 35 days ago
        $dormantOldVisit = User::factory()->create([
            'name' => 'Dormant Old Visit',
            'email' => 'oldvisit@example.com',
            'is_active' => true,
            'created_at' => now()->subDays(60),
        ]);
        UserPageVisit::create([
            'user_id' => $dormantOldVisit->id,
            'route_name' => 'home',
            'visit_count' => 3,
            'last_visited_at' => now()->subDays(35),
        ]);

        // 3. Active Recent User: Created 10 days ago (not dormant)
        $newActiveUser = User::factory()->create([
            'name' => 'New Active Worker',
            'email' => 'newworker@example.com',
            'is_active' => true,
            'created_at' => now()->subDays(10),
        ]);

        // 4. Inactive User: Deactivated user (should not count as active dormant)
        $inactiveUser = User::factory()->create([
            'name' => 'Suspended Worker',
            'email' => 'suspended@example.com',
            'is_active' => false,
            'created_at' => now()->subDays(50),
        ]);

        $test = Livewire::actingAs($this->admin)
            ->test(UserIndex::class);

        // Dormant count should be 2
        $test->assertSet('dormantCount', 2)
            ->assertSee('2 Dormant (>30d)')
            ->assertSee('Dormant Never Visited')
            ->assertSee('Dormant Old Visit')
            ->assertSee('New Active Worker');

        // Toggle onlyDormant filter
        $test->set('onlyDormant', true)
            ->assertSee('Dormant Never Visited')
            ->assertSee('Dormant Old Visit')
            ->assertDontSee('New Active Worker')
            ->assertDontSee('Suspended Worker');
    }

    public function test_user_edit_displays_navigation_and_usage_profile()
    {
        $targetUser = User::factory()->create([
            'name' => 'Tracked User',
            'email' => 'tracked@example.com',
            'email_verified_at' => now()->subMonths(1),
        ]);

        UserPageVisit::create([
            'user_id' => $targetUser->id,
            'route_name' => 'vehicles.index',
            'visit_count' => 45,
            'last_visited_at' => now()->subDays(1),
        ]);

        UserPageVisit::create([
            'user_id' => $targetUser->id,
            'route_name' => 'purchase.orders',
            'visit_count' => 12,
            'last_visited_at' => now()->subDays(3),
        ]);

        Livewire::actingAs($this->admin)
            ->test(UserEdit::class, ['userId' => $targetUser->id])
            ->assertStatus(200)
            ->assertSee('Edit User: Tracked User')
            ->assertSee('Navigation & Usage', escape: false)
            ->assertSee('57 Total Page Visits')
            ->assertSee('vehicles.index')
            ->assertSee('Vehicles')
            ->assertSee('45x')
            ->assertSee('purchase.orders')
            ->assertSee('12x');
    }
}
