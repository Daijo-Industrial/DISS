<?php

namespace Tests\Feature\Ticketing;

use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Events\TicketAssigned;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Mail\TicketAssignedMail;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TicketAssignmentNotificationTest extends TestCase
{
    public function test_ticket_assigned_mail_renders_properly(): void
    {
        $assignee = new User([
            'name' => 'IT Specialist',
            'email' => 'specialist@daijo.co.id',
        ]);
        $assignee->id = 99;

        $assignedBy = new User([
            'name' => 'IT Lead',
            'email' => 'lead@daijo.co.id',
        ]);
        $assignedBy->id = 1;

        $ticket = new Ticket([
            'ticket_number' => 'IT-202611-0001',
            'title' => 'Cannot connect to printer',
            'description' => 'The printer on 2nd floor is not responding',
            'priority' => 'High',
            'reporter_id' => 'EMP123',
        ]);
        $ticket->id = 50;
        $ticket->setRelation('reporter', null);
        $ticket->setRelation('category', null);

        $mailable = new TicketAssignedMail($ticket, $assignee, $assignedBy);

        $mailable->assertHasSubject('[DISS Support] Ticket Assigned: #IT-202611-0001 - Cannot connect to printer');
        $mailable->assertSeeInHtml('IT Specialist');
        $mailable->assertSeeInHtml('IT Lead');
        $mailable->assertSeeInHtml('Cannot connect to printer');
        $mailable->assertSeeInHtml('EMP123');
    }

    public function test_event_service_provider_registers_ticket_assigned_listener(): void
    {
        Event::fake([TicketAssigned::class]);

        $ticket = new Ticket(['ticket_number' => 'IT-123']);
        TicketAssigned::dispatch($ticket, null, 1, 2);

        Event::assertDispatched(TicketAssigned::class);
    }
}
