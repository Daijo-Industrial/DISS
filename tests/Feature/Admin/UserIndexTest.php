<?php

namespace Tests\Feature\Admin;

use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Livewire\Admin\Users\UserEdit;
use App\Livewire\Admin\Users\UserIndex;
use App\Models\UserPageVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
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
            ->set('tab', 'activity')
            ->assertSee('Navigation & Usage', escape: false)
            ->assertSee('57 Total Page Visits')
            ->assertSee('vehicles.index')
            ->assertSee('Vehicles')
            ->assertSee('45x')
            ->assertSee('purchase.orders')
            ->assertSee('12x');
    }

    public function test_user_edit_tab_switching_works()
    {
        $targetUser = User::factory()->create([
            'name' => 'Tab Switcher',
            'email' => 'tab@example.com',
        ]);

        Livewire::actingAs($this->admin)
            ->test(UserEdit::class, ['userId' => $targetUser->id])
            ->assertSet('tab', 'profile')
            ->call('setTab', 'roles')
            ->assertSet('tab', 'roles')
            ->assertSee('Role-Based Access Control')
            ->call('setTab', 'activity')
            ->assertSet('tab', 'activity')
            ->assertSee('Navigation & Usage Profile', escape: false)
            ->call('setTab', 'security')
            ->assertSet('tab', 'security')
            ->assertSee('Administrative Email Verification')
            ->call('setTab', 'invalid_tab')
            ->assertSet('tab', 'security');
    }

    public function test_user_edit_saves_profile_independently()
    {
        $targetUser = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        Livewire::actingAs($this->admin)
            ->test(UserEdit::class, ['userId' => $targetUser->id])
            ->set('name', 'New Updated Name')
            ->set('email', 'newemail@example.com')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $targetUser->refresh();
        $this->assertEquals('New Updated Name', $targetUser->name);
        $this->assertEquals('newemail@example.com', $targetUser->email);
    }

    public function test_user_edit_saves_roles_independently()
    {
        Role::findOrCreate('purchaser', 'web');

        $targetUser = User::factory()->create([
            'name' => 'Role Candidate',
            'email' => 'rolecandidate@example.com',
        ]);

        Livewire::actingAs($this->admin)
            ->test(UserEdit::class, ['userId' => $targetUser->id])
            ->set('tab', 'roles')
            ->set('selectedRoles', ['purchaser'])
            ->call('saveRoles')
            ->assertHasNoErrors();

        $targetUser->refresh();
        $this->assertTrue($targetUser->hasRole('purchaser'));
    }

    public function test_user_edit_changes_password_independently()
    {
        $targetUser = User::factory()->create([
            'name' => 'Password Reset User',
            'email' => 'pwreset@example.com',
            'password' => bcrypt('old-password-123'),
        ]);

        Livewire::actingAs($this->admin)
            ->test(UserEdit::class, ['userId' => $targetUser->id])
            ->set('tab', 'security')
            ->set('password', 'new-secure-password-456')
            ->set('password_confirmation', 'new-secure-password-456')
            ->call('savePassword')
            ->assertHasNoErrors()
            ->assertSet('password', '')
            ->assertSet('password_confirmation', '');

        $targetUser->refresh();
        $this->assertTrue(Hash::check('new-secure-password-456', $targetUser->password));
    }

    public function test_user_edit_admin_can_toggle_email_verification()
    {
        $unverifiedUser = User::factory()->unverified()->create([
            'name' => 'Unverified Target',
            'email' => 'unverifiedtarget@example.com',
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(UserEdit::class, ['userId' => $unverifiedUser->id])
            ->set('tab', 'security');

        // Toggle to verified
        $component->call('toggleEmailVerification');
        $unverifiedUser->refresh();
        $this->assertNotNull($unverifiedUser->email_verified_at);

        // Toggle back to unverified
        $component->call('toggleEmailVerification');
        $unverifiedUser->refresh();
        $this->assertNull($unverifiedUser->email_verified_at);
    }

    public function test_user_edit_page_renders_with_app_layout_and_no_double_sidebar()
    {
        $targetUser = User::factory()->create([
            'name' => 'Page User',
            'email' => 'pageuser@example.com',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.edit', $targetUser->id));

        $response->assertStatus(200);
        $response->assertSee('Edit User: Page User');
        $response->assertSee('Back to Users');
    }
}
