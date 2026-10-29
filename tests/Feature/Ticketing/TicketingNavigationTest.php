<?php

namespace Tests\Feature\Ticketing;

use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketingNavigationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_standard_staff_sees_only_support_tickets_in_it_support_nav(): void
    {
        Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']);
        $staff = User::factory()->create(['name' => 'Staff Regular']);
        $staff->givePermissionTo('dashboard.view');

        $this->actingAs($staff);

        $menu = NavigationService::getPersonalizedMenu();
        $itSupport = collect($menu)->firstWhere('label', 'IT Support');

        $this->assertNotNull($itSupport, 'IT Support menu group should be visible for staff');
        $childLabels = array_column($itSupport['children'], 'label');
        $childRoutes = array_column($itSupport['children'], 'route');

        $this->assertContains('Support Tickets', $childLabels);
        $this->assertContains('ticketing.list', $childRoutes);

        // Standard staff should NOT see admin/operations views
        $this->assertNotContains('Queue & Operations', $childLabels);
        $this->assertNotContains('KPI & SLA Analytics', $childLabels);
        $this->assertNotContains('ticketing.dashboard', $childRoutes);
        $this->assertNotContains('ticketing.admin.kpi', $childRoutes);
    }

    public function test_superadmin_sees_all_three_ticketing_modules_in_it_support_nav(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['name' => 'IT Super Admin']);
        $admin->assignRole('super-admin');

        $this->actingAs($admin);

        $menu = NavigationService::getPersonalizedMenu();
        $itSupport = collect($menu)->firstWhere('label', 'IT Support');

        $this->assertNotNull($itSupport, 'IT Support menu group should be visible for super-admin');
        $childLabels = array_column($itSupport['children'], 'label');
        $childRoutes = array_column($itSupport['children'], 'route');

        $this->assertContains('Support Tickets', $childLabels);
        $this->assertContains('Queue & Operations', $childLabels);
        $this->assertContains('KPI & SLA Analytics', $childLabels);

        $this->assertContains('ticketing.list', $childRoutes);
        $this->assertContains('ticketing.dashboard', $childRoutes);
        $this->assertContains('ticketing.admin.kpi', $childRoutes);
    }

    public function test_sidebar_nav_view_renders_it_support_properly_for_authenticated_users(): void
    {
        Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']);
        $user = User::factory()->create(['name' => 'General Employee']);
        $user->givePermissionTo('dashboard.view');

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('IT Support');
        $response->assertSee('Support Tickets');
    }
}
