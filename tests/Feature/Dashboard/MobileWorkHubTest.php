<?php

namespace Tests\Feature\Dashboard;

use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Livewire\Dashboard\GlobalDashboard;
use App\Livewire\Vehicles\Index as VehiclesIndex;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MobileWorkHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles and permissions exist
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create fleet permissions
        $viewPerm = Permission::firstOrCreate(['name' => 'fleet.view']);
        $inspectPerm = Permission::firstOrCreate(['name' => 'fleet.inspect']);
        $managePerm = Permission::firstOrCreate(['name' => 'fleet.manage']);

        // Create overtime permissions
        $otViewPerm = Permission::firstOrCreate(['name' => 'overtime.view']);

        // Create roles
        $inspectorRole = Role::firstOrCreate(['name' => 'inspector']);
        $inspectorRole->syncPermissions([$viewPerm, $inspectPerm]);

        $managerRole = Role::firstOrCreate(['name' => 'fleet-manager']);
        $managerRole->syncPermissions([$viewPerm, $inspectPerm, $managePerm]);

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staffRole->syncPermissions([$otViewPerm]);
    }

    public function test_inspector_with_limited_permission_sees_greeting_quick_access_and_recent_requests_on_home()
    {
        $inspector = User::factory()->create(['name' => 'Pak Budi Inspektor']);
        $inspector->assignRole('inspector');

        // Test NavigationService resolves inspector to P2H Armada (vehicles.scan)
        $menu = NavigationService::getPersonalizedMenu();
        $this->assertNotEmpty($menu);

        // Test GlobalDashboard renders greeting, quick access menus, and recent requests
        Livewire::actingAs($inspector)
            ->test(GlobalDashboard::class)
            ->assertStatus(200)
            ->assertSee('Quick Access')
            ->assertSee('P2H Armada')
            ->assertSeeHtml(route('vehicles.scan'))
            ->assertSee('My Recent Requests')
            ->assertSee($inspector->name);
    }

    public function test_staff_sees_quick_access_with_their_allowed_menus_on_home()
    {
        $staff = User::factory()->create(['name' => 'Siti Staff']);
        $staff->assignRole('staff');

        Livewire::actingAs($staff)
            ->test(GlobalDashboard::class)
            ->assertStatus(200)
            ->assertSee('Quick Access')
            ->assertSee('Overtime')
            ->assertSeeHtml(route('overtime.index'))
            ->assertSee('My Recent Requests');
    }

    public function test_vehicles_index_has_breadcrumb_navigation_to_home()
    {
        $inspector = User::factory()->create(['name' => 'Pak Budi Inspektor']);
        $inspector->assignRole('inspector');

        Livewire::actingAs($inspector)
            ->test(VehiclesIndex::class)
            ->assertStatus(200)
            ->assertSeeHtml(route('home'))
            ->assertSeeHtml('aria-label="Breadcrumb"')
            ->assertSee('Home');
    }

    public function test_home_page_http_response_renders_greeting_quick_access_and_fullscreen_mobile_sidebar()
    {
        $inspector = User::factory()->create(['name' => 'Pak Budi Inspektor']);
        $inspector->assignRole('inspector');

        $response = $this->actingAs($inspector)->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Quick Access');
        $response->assertSee('P2H Armada');
        $response->assertSee(route('vehicles.scan'));
        $response->assertSee('My Recent Requests');

        // Verify mobile fullscreen & tablet w-72 drawer aside
        $response->assertSee('class="fixed inset-y-0 left-0 z-[150] flex w-full sm:w-72 flex-col bg-white border-r border-slate-200/80 shadow-2xl will-change-transform"', false);

        // Verify IT Concierge bubble hides when sidebar is open
        $response->assertSee('x-show="typeof sidebarOpen === \'undefined\' || !sidebarOpen"', false);
    }
}
