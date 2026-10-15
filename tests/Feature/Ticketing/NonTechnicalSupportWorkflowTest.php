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
            ->assertSee('Ticket submitted successfully!');

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
        $response->assertSee('Request IT Support Help', false);
        $response->assertSee('IT Login Assistance');

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

        $test = Livewire::test(TicketDetail::class, ['ticket' => $ticket]);
        $test->assertSee('Escalate to GitHub Issue');
        $test->assertSee('Record GitHub Issue URL');

        $githubUrl = $test->instance()->getGitHubIssueUrl();
        $this->assertStringContainsString('https://github.com/Daijo-Industrial/DISS/issues/new?', $githubUrl);
        $this->assertStringContainsString('template=bug_report.yml', $githubUrl);
        $this->assertStringContainsString(urlencode('[Bug]: DataTables search throws uncaught syntax error'), $githubUrl);
        $this->assertStringContainsString(urlencode('http://localhost/procurement/orders'), $githubUrl);

        // Test saving the linked GitHub Issue URL
        $test->set('githubIssueUrlInput', 'https://github.com/Daijo-Industrial/DISS/issues/105')
            ->call('saveGithubIssueUrl')
            ->assertHasNoErrors()
            ->assertSee('GitHub issue link updated successfully.');

        $ticket->refresh();
        $this->assertEquals('https://github.com/Daijo-Industrial/DISS/issues/105', $ticket->github_issue_url);
    }
}
