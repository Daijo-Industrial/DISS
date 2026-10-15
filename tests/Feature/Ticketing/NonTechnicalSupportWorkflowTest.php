<?php

namespace Tests\Feature\Ticketing;

use App\Domains\Ticketing\Actions\TransitionTicketStatus;
use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Entities\TicketCategory;
use App\Domains\Ticketing\Enums\TicketPriority;
use App\Domains\Ticketing\Enums\TicketStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Employee;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Livewire\Ticketing\SupportBubble;
use App\Livewire\Ticketing\TicketDetail;
use App\Mail\NewTicketSubmittedMail;
use App\Mail\TicketStatusUpdatedMail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NonTechnicalSupportWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('public');
    }

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

    public function test_support_bubble_submits_ticket_with_url_and_screenshot_and_dispatches_mail(): void
    {
        Config::set('mail.support_address', 'it-support@daijo.co.id');
        [$employee, $user] = $this->createEmployeeAndUser();
        $category = $this->getOrCreateCategory('Software / Application Bug');

        $screenshot = UploadedFile::fake()->image('error_screenshot.png', 800, 600);

        $this->actingAs($user);

        Livewire::test(SupportBubble::class)
            ->set('isOpen', true)
            ->set('title', 'Vehicle inspection form crashes on submit')
            ->set('description', 'Every time I tap submit on mobile, the screen goes white.')
            ->set('category_id', $category->id)
            ->set('priority', 'High')
            ->set('current_url', 'http://localhost/fleet/inspections/create')
            ->set('screenshot', $screenshot)
            ->call('submitTicket')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'my_tickets')
            ->assertSee(__('ticketing.concierge.messages.ticket_submitted'));

        $ticket = Ticket::where('title', 'Vehicle inspection form crashes on submit')->latest()->first();
        $this->assertNotNull($ticket);
        $this->assertEquals($employee->nik, $ticket->reporter_id);
        $this->assertEquals($user->email, $ticket->reporter_email);
        $this->assertEquals('http://localhost/fleet/inspections/create', $ticket->current_url);
        $this->assertNotNull($ticket->screenshot_path);
        Storage::disk('public')->assertExists($ticket->screenshot_path);

        // Verify IT Support team received the alert email
        Mail::assertSent(NewTicketSubmittedMail::class, function ($mail) use ($ticket) {
            return $mail->hasTo('it-support@daijo.co.id') && $mail->ticket->id === $ticket->id;
        });
    }

    public function test_login_page_renders_assistance_trigger_and_submits_request_successfully(): void
    {
        Config::set('mail.support_address', 'it-support@daijo.co.id');
        $uniqueNik = 'EML' . rand(100000, 999999);
        [$employee, $user] = $this->createEmployeeAndUser($uniqueNik, 'locked.out.' . rand(1000, 9999) . '@daijo.co.id');
        $this->getOrCreateCategory('Access & Permissions');

        // Check login page renders help trigger
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee(__('ticketing.login_help.trigger'), false);
        $response->assertSee(__('ticketing.login_help.modal_title'), false);

        // Post unauthenticated login assistance request
        $postResponse = $this->post(route('support.login-assistance'), [
            'nik_or_email' => $uniqueNik,
            'issue_type' => 'Password Reset / Account Locked',
            'description' => 'I cannot remember my password after returning from leave.',
            'contact_number' => '081234567890',
        ]);

        $postResponse->assertRedirect(route('login'));
        $postResponse->assertSessionHas('status');

        $ticket = Ticket::where('reporter_id', $uniqueNik)->latest()->first();
        $this->assertNotNull($ticket);
        $this->assertEquals($user->email, $ticket->reporter_email);
        $this->assertEquals(TicketPriority::HIGH, $ticket->priority);
        $this->assertStringContainsString('Password Reset / Account Locked', $ticket->title);
        $this->assertStringContainsString('081234567890', $ticket->description);

        // Verify mail sent to IT
        Mail::assertSent(NewTicketSubmittedMail::class, function ($mail) use ($ticket) {
            return $mail->ticket->id === $ticket->id;
        });
    }

    public function test_login_assistance_fails_when_employee_nik_is_invalid(): void
    {
        $nonExistentNik = 'NON_EXISTENT_NIK_' . rand(10000, 99999);
        $this->getOrCreateCategory('Access & Permissions');

        $response = $this->from(route('login'))->post(route('support.login-assistance'), [
            'nik_or_email' => $nonExistentNik,
            'issue_type' => 'Password Reset / Account Locked',
            'description' => 'Cannot log in with bad NIK',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('nik_or_email');

        $ticket = Ticket::where('reporter_id', $nonExistentNik)->first();
        $this->assertNull($ticket);
    }

    public function test_public_verify_nik_route_does_not_exist_to_prevent_user_enumeration_iso27001(): void
    {
        // ISO 27001 Control A.8.26 Anti-Enumeration requirement:
        // Ensure unauthenticated NIK lookup endpoint does not exist on public web routes
        $response = $this->postJson('/support/verify-nik', ['nik' => '12345']);
        $response->assertStatus(404);
    }

    public function test_ticket_resolution_triggers_status_updated_email_to_reporter(): void
    {
        [$employee, $user] = $this->createEmployeeAndUser();
        $category = $this->getOrCreateCategory('Software / Application Bug');

        $ticket = Ticket::create([
            'ticket_number' => 'IT-' . rand(100000, 999999),
            'reporter_id' => $employee->nik,
            'reporter_email' => $user->email,
            'category_id' => $category->id,
            'title' => 'Cannot view invoice list',
            'description' => 'Returns 500 error',
            'status' => TicketStatus::OPEN,
            'priority' => TicketPriority::HIGH,
        ]);

        $transitionAction = app(TransitionTicketStatus::class);
        $transitionAction->execute($ticket, TicketStatus::RESOLVED, $user->id, 'Resolved via backend patch.');

        // Verify reporter email sent
        Mail::assertSent(TicketStatusUpdatedMail::class, function ($mail) use ($ticket, $user) {
            return $mail->hasTo($user->email) && $mail->ticket->id === $ticket->id;
        });
    }

    public function test_ticket_detail_generates_github_issue_url_and_saves_linked_url(): void
    {
        [$employee, $user] = $this->createEmployeeAndUser();
        $category = $this->getOrCreateCategory('Software / Application Bug');

        $ticket = Ticket::create([
            'ticket_number' => 'IT-' . rand(100000, 999999),
            'reporter_id' => $employee->nik,
            'reporter_email' => $user->email,
            'category_id' => $category->id,
            'title' => 'DataTables search throws uncaught syntax error',
            'description' => 'Uncaught TypeError in vendor bundle on query input',
            'current_url' => 'http://localhost/procurement/orders',
            'device_info' => 'Mozilla/5.0 Chrome/120.0',
            'status' => TicketStatus::OPEN,
            'priority' => TicketPriority::CRITICAL,
        ]);

        $this->actingAs($user);

        Config::set('services.github.repo_url', 'https://github.com/your-org/your-repo');

        // Test English locale rendering
        app()->setLocale('en');
        $testEn = Livewire::test(TicketDetail::class, ['ticket' => $ticket]);
        $testEn->assertSee(__('ticketing.detail.escalate_to_github'));
        $testEn->assertSee(__('ticketing.detail.record_github_url'));

        // Test Indonesian locale rendering
        app()->setLocale('id');
        $testId = Livewire::test(TicketDetail::class, ['ticket' => $ticket]);
        $testId->assertSee(__('ticketing.detail.escalate_to_github'));
        $testId->assertSee(__('ticketing.detail.record_github_url'));

        $githubUrl = $testId->instance()->getGitHubIssueUrl();
        $this->assertStringContainsString('https://github.com/your-org/your-repo/issues/new?', $githubUrl);
        $this->assertStringContainsString('template=bug_report.yml', $githubUrl);
        $this->assertStringContainsString(urlencode('[Bug]: DataTables search throws uncaught syntax error'), $githubUrl);
        $this->assertStringContainsString(urlencode('http://localhost/procurement/orders'), $githubUrl);

        // Test saving the linked GitHub Issue URL
        $testId->set('githubIssueUrlInput', 'https://github.com/your-org/your-repo/issues/105')
            ->call('saveGithubIssueUrl')
            ->assertHasNoErrors()
            ->assertSee(__('ticketing.detail.github_url_updated'));

        $ticket->refresh();
        $this->assertEquals('https://github.com/your-org/your-repo/issues/105', $ticket->github_issue_url);
    }

    public function test_simplified_support_bubble_submission_auto_categorizes_and_captures_url(): void
    {
        [$employee, $user] = $this->createEmployeeAndUser();
        $this->getOrCreateCategory('Feature Request');

        $this->actingAs($user);

        Livewire::test(SupportBubble::class)
            ->set('isOpen', true)
            ->set('request_type', 'feature')
            ->set('custom_title', 'Export PR to CSV format')
            ->set('description', 'Need a button to export all purchase requests into CSV')
            ->set('current_url', 'http://localhost/purchase-requests')
            ->call('submitTicket')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'my_tickets')
            ->assertSee(__('ticketing.concierge.messages.ticket_submitted'));

        $ticket = Ticket::where('title', 'Export PR to CSV format')->latest()->first();
        $this->assertNotNull($ticket);
        $this->assertStringContainsString('Feature', $ticket->category->name);
        $this->assertEquals(TicketPriority::MEDIUM, $ticket->priority);
        $this->assertEquals('http://localhost/purchase-requests', $ticket->current_url);
    }

    public function test_it_support_teams_and_super_admin_can_update_category_and_priority_on_ticket_detail(): void
    {
        [$employee, $reporter] = $this->createEmployeeAndUser();
        $catBug = $this->getOrCreateCategory('Software / Application Bug');
        $catFeature = $this->getOrCreateCategory('Feature Request');

        $ticket = Ticket::create([
            'ticket_number' => 'IT-' . rand(100000, 999999),
            'reporter_id' => $employee->nik,
            'reporter_email' => $reporter->email,
            'category_id' => $catBug->id,
            'title' => 'Sample Triage Ticket',
            'description' => 'Awaiting triage from IT support',
            'status' => TicketStatus::OPEN,
            'priority' => TicketPriority::MEDIUM,
        ]);

        // 1. Regular reporter cannot manage or triage
        $this->actingAs($reporter);
        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->assertSet('canManage', false);

        // 2. Super Administrator / IT Support can update category & priority
        $admin = User::factory()->create(['name' => 'IT Admin Lead']);
        $adminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin->assignRole('super-admin');

        $this->actingAs($admin);

        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->assertSet('canManage', true)
            ->call('updateCategory', $catFeature->id)
            ->assertHasNoErrors()
            ->call('updatePriority', 'Critical')
            ->assertHasNoErrors();

        $ticket->refresh();
        $this->assertEquals($catFeature->id, $ticket->category_id);
        $this->assertEquals(TicketPriority::CRITICAL, $ticket->priority);

        // Verify activities were recorded
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'new_state' => 'Feature Request',
        ]);
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'new_state' => 'Critical',
        ]);
    }

    public function test_it_support_teams_and_super_admin_can_update_sla_target_on_ticket_detail(): void
    {
        [$employee, $reporter] = $this->createEmployeeAndUser();
        $catBug = $this->getOrCreateCategory('Software / Application Bug');

        $ticket = Ticket::create([
            'ticket_number' => 'IT-' . rand(100000, 999999),
            'reporter_id' => $employee->nik,
            'reporter_email' => $reporter->email,
            'category_id' => $catBug->id,
            'title' => 'SLA Target Triage Test in Minutes',
            'description' => 'Verify IT Team can customize SLA target minutes without presets',
            'status' => TicketStatus::OPEN,
            'priority' => TicketPriority::HIGH,
            'sla_minutes' => null,
        ]);

        // 1. Regular reporter cannot manage or update SLA target
        $this->actingAs($reporter);
        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->assertSet('canManage', false);

        // 2. IT Admin / Super Admin can set SLA target in minutes
        $admin = User::factory()->create(['name' => 'IT SLA Manager']);
        $adminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin->assignRole('super-admin');

        $this->actingAs($admin);

        // Test update in minutes (e.g. 120 minutes = 2 hours)
        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->assertSet('canManage', true)
            ->call('updateSlaMinutes', 120)
            ->assertHasNoErrors()
            ->assertSee(__('ticketing.detail.sla_minutes_updated', ['minutes' => 120]));

        $ticket->refresh();
        $this->assertEquals(120, $ticket->sla_minutes);
        $this->assertEquals(120, $ticket->getEffectiveSlaMinutes());
        $this->assertEquals(2.0, $ticket->getEffectiveSlaHours());
        $this->assertEquals('2 ' . __('ticketing.detail.hours'), $ticket->getFormattedSlaTarget());

        // Test custom minutes input and saveSlaMinutes (e.g. 45 minutes)
        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->set('customSlaMinutes', 45)
            ->call('saveSlaMinutes')
            ->assertHasNoErrors()
            ->assertSee(__('ticketing.detail.sla_minutes_updated', ['minutes' => 45]));

        $ticket->refresh();
        $this->assertEquals(45, $ticket->sla_minutes);
        $this->assertEquals(45, $ticket->getEffectiveSlaMinutes());
        $this->assertEquals('45 ' . __('ticketing.detail.minutes'), $ticket->getFormattedSlaTarget());

        // Verify activity log recorded the SLA change in minutes
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'new_state' => '45 ' . __('ticketing.detail.minutes'),
            'reason' => 'SLA target adjusted by IT Support.',
        ]);

        // Test Indonesian locale rendering for SLA target update
        app()->setLocale('id');
        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->call('updateSlaMinutes', 30)
            ->assertHasNoErrors()
            ->assertSee(__('ticketing.detail.sla_minutes_updated', ['minutes' => 30]));

        // Test validation rejects out-of-range minutes (min: 1, max: 43200)
        Livewire::test(TicketDetail::class, ['ticket' => $ticket])
            ->set('customSlaMinutes', 0)
            ->call('saveSlaMinutes')
            ->assertHasErrors(['customSlaMinutes']);
    }

    public function test_user_can_switch_language_in_login_screen(): void
    {
        // 1. Visit login in English
        $responseEn = $this->get(route('locale.switch', 'en'));
        $responseEn->assertRedirect();

        $loginEn = $this->get(route('login'));
        $loginEn->assertStatus(200);
        $loginEn->assertSee('Welcome Back');
        $loginEn->assertSee('Sign in to access your dashboard');
        $loginEn->assertSee('🇮🇩 ID');
        $loginEn->assertSee('🇬🇧 EN');

        // 2. Switch to Indonesian
        $responseId = $this->get(route('locale.switch', 'id'));
        $responseId->assertRedirect();

        $loginId = $this->get(route('login'));
        $loginId->assertStatus(200);
        $loginId->assertSee('Selamat Datang Kembali');
        $loginId->assertSee('Masuk untuk mengakses dashboard Anda');
        $loginId->assertSee('Bantuan Login IT');
    }

    public function test_login_assistance_rejects_corporate_email_and_strictly_requires_nik(): void
    {
        [$employee, $user] = $this->createEmployeeAndUser();
        $this->getOrCreateCategory('Access & Permissions');

        // Attempting submission with email instead of NIK must fail
        $response = $this->from(route('login'))->post(route('support.login-assistance'), [
            'nik' => $user->email,
            'issue_type' => 'Password Reset / Account Locked',
            'description' => 'Trying to use company email instead of NIK',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['nik']);
    }

    public function test_public_ticket_tracking_page_allows_employee_to_view_progress_with_ticket_number_and_nik(): void
    {
        [$employee, $user] = $this->createEmployeeAndUser();
        $category = $this->getOrCreateCategory('Software / Application Bug');

        $ticket = Ticket::create([
            'ticket_number' => 'IT-TRACK-' . rand(10000, 99999),
            'reporter_id' => $employee->nik,
            'reporter_email' => $user->email,
            'category_id' => $category->id,
            'title' => 'Cannot Access Procurement Module',
            'description' => 'System gives 403 Forbidden when clicking Procurement.',
            'status' => TicketStatus::IN_PROGRESS,
            'priority' => TicketPriority::HIGH,
            'sla_minutes' => 60,
        ]);

        // 1. Visit tracking page with matching Ticket Number and Employee NIK
        $response = $this->get(route('support.track', [
            'ticket' => $ticket->ticket_number,
            'nik' => $employee->nik,
        ]));

        $response->assertStatus(200);
        $response->assertSee($ticket->ticket_number);
        $response->assertSee('Cannot Access Procurement Module');
        $response->assertSee('In Progress');
        $response->assertSee($ticket->getFormattedSlaTarget());
    }

    public function test_public_ticket_tracking_rejects_unauthorized_or_mismatched_nik_iso27001(): void
    {
        [$employee, $user] = $this->createEmployeeAndUser();
        $category = $this->getOrCreateCategory('Software / Application Bug');

        $ticket = Ticket::create([
            'ticket_number' => 'IT-SEC-' . rand(10000, 99999),
            'reporter_id' => $employee->nik,
            'reporter_email' => $user->email,
            'category_id' => $category->id,
            'title' => 'Sensitive Payroll Issue',
            'description' => 'Private information about discrepancy.',
            'status' => TicketStatus::OPEN,
            'priority' => TicketPriority::CRITICAL,
        ]);

        // Wrong NIK attempt must be rejected (ISO 27001 data isolation)
        $response = $this->post(route('support.track'), [
            'ticket' => $ticket->ticket_number,
            'nik' => 'WRONG_NIK_999',
        ]);

        $response->assertStatus(200);
        $response->assertSee(__('ticketing.track.not_found'));
        $response->assertDontSee('Sensitive Payroll Issue');
    }
}
