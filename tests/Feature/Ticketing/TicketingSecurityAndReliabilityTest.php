<?php

namespace Tests\Feature\Ticketing;

use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Entities\TicketCategory;
use App\Domains\Ticketing\Enums\TicketPriority;
use App\Domains\Ticketing\Enums\TicketStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Employee;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Livewire\Ticketing\Admin\KpiDashboard;
use App\Livewire\Ticketing\SupportBubble;
use App\Livewire\Ticketing\TicketDetail;
use App\Livewire\Ticketing\TicketList;
use App\Livewire\Ticketing\TicketStatusModal;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketingSecurityAndReliabilityTest extends TestCase
{
    use DatabaseTransactions;

    private function createEmployeeAndUser(?string $nik = null, ?string $email = null): array
    {
        $nik = $nik ?? ('EMP' . rand(100000, 999999));
        $email = $email ?? ('user_' . rand(100000, 999999) . '@daijo.co.id');

        $id = DB::table('employees')->insertGetId([
            'nik' => $nik,
            'name' => 'Test Employee',
            'gender' => 'L',
            'dept_code' => '001',
            'branch' => 'Jakarta',
            'employment_type' => 'Active',
            'employment_scheme' => 'YAYASAN',
            'grade_code' => 'A',
            'grade_level' => 1,
            'organization_structure' => 'HR',
            'jatah_cuti_tahun' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $employee = Employee::find($id);

        $user = User::factory()->create([
            'email' => $email,
            'employee_id' => $id,
        ]);

        return [$employee, $user];
    }

    private function getOrCreateCategory(string $name = 'Software / Application Bug'): TicketCategory
    {
        return TicketCategory::firstOrCreate(
            ['name' => $name],
            ['description' => 'Test category description', 'sla_hours' => 24]
        );
    }

    public function test_unauthorized_user_cannot_view_or_tamper_with_other_users_ticket(): void
    {
        [$ownerEmployee, $ownerUser] = $this->createEmployeeAndUser();
        [$otherEmployee, $attackerUser] = $this->createEmployeeAndUser();
        $cat = $this->getOrCreateCategory();

        $ticket = Ticket::create([
            'ticket_number' => 'IT-SEC-' . rand(100000, 999999),
            'reporter_id' => $ownerEmployee->nik,
            'reporter_email' => $ownerUser->email,
            'category_id' => $cat->id,
            'title' => 'Confidential Executive Issue',
            'description' => 'Payroll discrepancy details',
            'status' => TicketStatus::OPEN,
            'priority' => TicketPriority::CRITICAL,
        ]);

        // 1. Attacker attempting to mount TicketDetail must get 403 Forbidden
        $this->actingAs($attackerUser);
        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->assertStatus(403);

        // 2. Owner CAN access ticket detail
        $this->actingAs($ownerUser);
        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->assertStatus(200)
            ->assertSee('Confidential Executive Issue')
            ->assertSet('canManage', false);

        // 3. Super Admin CAN access ticket detail
        $admin = User::factory()->create(['name' => 'IT Super Admin']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin->assignRole('super-admin');

        $this->actingAs($admin);
        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->assertStatus(200)
            ->assertSee('Confidential Executive Issue')
            ->assertSet('canManage', true);
    }

    public function test_unauthorized_user_cannot_transition_status_via_modal(): void
    {
        [$ownerEmployee, $ownerUser] = $this->createEmployeeAndUser();
        [$otherEmployee, $attackerUser] = $this->createEmployeeAndUser();
        $cat = $this->getOrCreateCategory();

        $ticket = Ticket::create([
            'ticket_number' => 'IT-MODAL-' . rand(100000, 999999),
            'reporter_id' => $ownerEmployee->nik,
            'reporter_email' => $ownerUser->email,
            'category_id' => $cat->id,
            'title' => 'Status tampering test',
            'description' => 'Awaiting IT triage',
            'status' => TicketStatus::OPEN,
            'priority' => TicketPriority::HIGH,
        ]);

        // Attacker attempting to transition status must get 403 Forbidden
        $this->actingAs($attackerUser);
        Livewire::test(TicketStatusModal::class)
            ->set('ticket', $ticket)
            ->set('newStatus', 'Resolved')
            ->call('save')
            ->assertStatus(403);

        $ticket->refresh();
        $this->assertEquals(TicketStatus::OPEN, $ticket->status);
    }

    public function test_ticket_list_scopes_to_own_tickets_for_standard_staff_and_all_for_admin(): void
    {
        [$empA, $userA] = $this->createEmployeeAndUser();
        [$empB, $userB] = $this->createEmployeeAndUser();
        $cat = $this->getOrCreateCategory();

        $ticketA = Ticket::create([
            'ticket_number' => 'IT-USER-A-' . rand(1000, 9999),
            'reporter_id' => $empA->nik,
            'reporter_email' => $userA->email,
            'category_id' => $cat->id,
            'title' => 'User A Secret Ticket',
            'description' => 'Only User A should see this',
            'status' => TicketStatus::OPEN,
            'priority' => TicketPriority::MEDIUM,
        ]);

        $ticketB = Ticket::create([
            'ticket_number' => 'IT-USER-B-' . rand(1000, 9999),
            'reporter_id' => $empB->nik,
            'reporter_email' => $userB->email,
            'category_id' => $cat->id,
            'title' => 'User B Secret Ticket',
            'description' => 'Only User B should see this',
            'status' => TicketStatus::OPEN,
            'priority' => TicketPriority::MEDIUM,
        ]);

        // Standard user A should ONLY see Ticket A, not Ticket B
        $this->actingAs($userA);
        Livewire::test(TicketList::class)
            ->assertSee($ticketA->ticket_number)
            ->assertSee('User A Secret Ticket')
            ->assertDontSee($ticketB->ticket_number)
            ->assertDontSee('User B Secret Ticket');

        // Super Admin should see BOTH Ticket A and Ticket B
        $admin = User::factory()->create(['name' => 'IT Operations Lead']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin->assignRole('super-admin');

        $this->actingAs($admin);
        Livewire::test(TicketList::class)
            ->assertSee($ticketA->ticket_number)
            ->assertSee($ticketB->ticket_number);
    }

    public function test_kpi_dashboard_mounts_cleanly_for_admin_and_rejects_unauthorized_users(): void
    {
        [$emp, $staff] = $this->createEmployeeAndUser();

        // 1. Standard staff is rejected with 403 Forbidden
        $this->actingAs($staff);
        Livewire::test(KpiDashboard::class)
            ->assertStatus(403);

        // 2. Super admin can access and assignedTickets relationship works without crash
        $admin = User::factory()->create(['name' => 'IT KPI Admin']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin->assignRole('super-admin');

        $cat = $this->getOrCreateCategory();
        Ticket::create([
            'ticket_number' => 'IT-KPI-' . rand(1000, 9999),
            'reporter_id' => $emp->nik,
            'category_id' => $cat->id,
            'assigned_to' => $admin->id,
            'title' => 'Assigned ticket for KPI verification',
            'description' => 'Verifying assignedTickets relationship on User model',
            'status' => TicketStatus::IN_PROGRESS,
            'priority' => TicketPriority::HIGH,
        ]);

        $this->actingAs($admin);
        Livewire::test(KpiDashboard::class)
            ->assertStatus(200);

        $this->assertTrue($admin->assignedTickets()->exists());
    }

    public function test_support_bubble_rejects_javascript_scheme_in_current_url(): void
    {
        [$emp, $user] = $this->createEmployeeAndUser();
        $this->actingAs($user);

        Livewire::test(SupportBubble::class)
            ->set('isOpen', true)
            ->set('request_type', 'bug')
            ->set('description', 'XSS attempt in current url')
            ->set('current_url', 'javascript:alert(document.cookie)')
            ->call('submitTicket')
            ->assertHasErrors(['current_url']);
    }
}
