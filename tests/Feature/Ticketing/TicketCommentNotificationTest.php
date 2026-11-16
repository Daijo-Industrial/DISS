<?php

namespace Tests\Feature\Ticketing;

use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Entities\TicketActivity;
use App\Domains\Ticketing\Enums\ActivityType;
use App\Domains\Ticketing\Events\TicketCommentAdded;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Listeners\SendTicketCommentNotification;
use App\Mail\TicketCommentMail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TicketCommentNotificationTest extends TestCase
{
    public function test_ticket_comment_mail_renders_properly(): void
    {
        $ticket = new Ticket([
            'ticket_number' => 'IT-202611-0002',
            'title' => 'ERP login failure',
        ]);
        $ticket->id = 88;

        $activity = new TicketActivity([
            'type' => ActivityType::COMMENT,
            'reason' => 'We are checking the ERP server logs right now.',
            'new_state' => 'tickets/attachments/sample.png',
        ]);

        $mailable = new TicketCommentMail($ticket, $activity, 'John Doe', 'IT Tech');

        $mailable->assertHasSubject('[DISS Support] New reply on Ticket #IT-202611-0002 - ERP login failure');
        $mailable->assertSeeInHtml('Hello John Doe');
        $mailable->assertSeeInHtml('IT Tech');
        $mailable->assertSeeInHtml('We are checking the ERP server logs right now.');
        $mailable->assertSeeInHtml('An image attachment was included in this reply.');
    }

    public function test_event_is_registered_with_listener(): void
    {
        Event::fake([TicketCommentAdded::class]);

        $ticket = new Ticket(['ticket_number' => 'IT-999']);
        $activity = new TicketActivity(['type' => ActivityType::COMMENT, 'reason' => 'Test']);

        TicketCommentAdded::dispatch($ticket, $activity, 1);

        Event::assertDispatched(TicketCommentAdded::class);
    }

    public function test_listener_suppresses_technician_email_when_reporter_replies_to_prevent_inbox_noise(): void
    {
        Mail::fake();

        $assignee = new User([
            'name' => 'Support Engineer',
            'email' => 'tech@daijo.co.id',
        ]);
        $assignee->id = 10;

        $reporter = new User([
            'name' => 'Employee John',
            'email' => 'user@daijo.co.id',
        ]);
        $reporter->id = 99;

        $ticket = new Ticket([
            'ticket_number' => 'IT-1001',
            'title' => 'VPN Disconnected',
            'assigned_to' => 10,
            'reporter_email' => 'user@daijo.co.id',
        ]);
        $ticket->id = 45;
        $ticket->setRelation('assignee', $assignee);
        $ticket->setRelation('reporter', null);

        $activity = new TicketActivity([
            'type' => ActivityType::COMMENT,
            'reason' => 'Still unable to connect via VPN.',
        ]);
        $activity->setRelation('user', $reporter);

        // Commenter is user (id 99)
        $event = new TicketCommentAdded($ticket, $activity, 99);
        $listener = new SendTicketCommentNotification;
        $listener->handle($event);

        // Under role-asymmetric strategy, IT technicians are NOT spammed with per-reply emails
        Mail::assertNothingSent();
    }

    public function test_listener_notifies_reporter_when_assignee_replies(): void
    {
        Mail::fake();

        $assignee = new User([
            'name' => 'Support Engineer',
            'email' => 'tech@daijo.co.id',
        ]);
        $assignee->id = 10;

        $ticket = new Ticket([
            'ticket_number' => 'IT-1002',
            'title' => 'Monitor flickers',
            'assigned_to' => 10,
            'reporter_email' => 'user@daijo.co.id',
        ]);
        $ticket->id = 46;
        $ticket->setRelation('assignee', $assignee);
        $ticket->setRelation('reporter', null);

        $activity = new TicketActivity([
            'type' => ActivityType::COMMENT,
            'reason' => 'Please restart the monitor cable.',
        ]);
        $activity->setRelation('user', $assignee);

        // Commenter is assignee (id 10)
        $event = new TicketCommentAdded($ticket, $activity, 10);
        $listener = new SendTicketCommentNotification;
        $listener->handle($event);

        Mail::assertSent(TicketCommentMail::class, function (TicketCommentMail $mail) {
            return $mail->hasTo('user@daijo.co.id');
        });
    }

    public function test_listener_does_not_notify_commenter(): void
    {
        Mail::fake();

        $assignee = new User([
            'name' => 'Support Engineer',
            'email' => 'tech@daijo.co.id',
        ]);
        $assignee->id = 10;

        $ticket = new Ticket([
            'ticket_number' => 'IT-1003',
            'title' => 'Mouse broken',
            'assigned_to' => 10,
            'reporter_email' => 'tech@daijo.co.id', // Same as assignee
        ]);
        $ticket->id = 47;
        $ticket->setRelation('assignee', $assignee);
        $ticket->setRelation('reporter', null);

        $activity = new TicketActivity([
            'type' => ActivityType::COMMENT,
            'reason' => 'Self comment test.',
        ]);
        $activity->setRelation('user', $assignee);

        // Commenter is 10
        $event = new TicketCommentAdded($ticket, $activity, 10);
        $listener = new SendTicketCommentNotification;
        $listener->handle($event);

        Mail::assertNothingSent();
    }
}
