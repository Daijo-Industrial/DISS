<?php

namespace Tests\Feature\Ticketing;

use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Enums\TicketStatus;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Mail\ITSpecialistDailyDigestMail;
use App\Mail\ITSupportDailyDigestMail;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DailyITSupportDigestTest extends TestCase
{
    public function test_team_digest_mail_renders_properly(): void
    {
        $ticket1 = new Ticket([
            'ticket_number' => 'IT-202611-0001',
            'title' => 'Network down on Line 3',
            'reporter_id' => 'EMP001',
        ]);
        $ticket1->id = 1;
        $ticket1->created_at = now()->subHours(2);
        $ticket1->updated_at = now()->subHours(2);
        $ticket1->status = TicketStatus::OPEN;
        $ticket1->setRelation('reporter', null);

        $ticket2 = new Ticket([
            'ticket_number' => 'IT-202611-0002',
            'title' => 'Printer jammed',
            'reporter_id' => 'EMP002',
        ]);
        $ticket2->id = 2;
        $ticket2->created_at = now()->subDays(2);
        $ticket2->updated_at = now()->subHours(30);
        $ticket2->status = TicketStatus::IN_PROGRESS;
        $ticket2->setRelation('reporter', null);

        $unassigned = collect([$ticket1]);
        $stalled = collect([$ticket2]);
        $workload = ['Specialist Andi' => 2];

        $mailable = new ITSupportDailyDigestMail($unassigned, $stalled, $workload, 3, '16 Nov 2026');

        $mailable->assertHasSubject('[DISS IT Morning Digest] 16 Nov 2026 — 1 Unassigned, 1 Stalled (3 Active)');
        $mailable->assertSeeInHtml('IT Support Morning Queue Digest');
        $mailable->assertSeeInHtml('Network down on Line 3');
        $mailable->assertSeeInHtml('Printer jammed');
        $mailable->assertSeeInHtml('Specialist Andi');
        $mailable->assertSeeInHtml('Open Unassigned Queue');
    }

    public function test_specialist_digest_mail_renders_properly(): void
    {
        $technician = new User([
            'name' => 'Budi IT',
            'email' => 'budi@daijo.co.id',
        ]);
        $technician->id = 15;

        $ticket = new Ticket([
            'ticket_number' => 'IT-202611-0005',
            'title' => 'SAP license activation',
            'sla_minutes' => 240,
        ]);
        $ticket->id = 5;
        $ticket->status = TicketStatus::IN_PROGRESS;

        $mailable = new ITSpecialistDailyDigestMail($technician, collect([$ticket]), 2, '16 Nov 2026');

        $mailable->assertHasSubject('[My IT Queue] 16 Nov 2026 — You have 1 active ticket(s)');
        $mailable->assertSeeInHtml('Daily Queue: Budi IT');
        $mailable->assertSeeInHtml('SAP license activation');
        $mailable->assertSeeInHtml('4');
        $mailable->assertSeeInHtml('There are currently');
        $mailable->assertSeeInHtml('unassigned ticket(s)');
    }

    public function test_command_is_registered_in_artisan(): void
    {
        $this->assertTrue(array_key_exists('ticketing:daily-digest', Artisan::all()));
    }
}
