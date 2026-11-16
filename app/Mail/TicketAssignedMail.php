<?php

namespace App\Mail;

use App\Domains\Ticketing\Entities\Ticket;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public User $assignee,
        public ?User $assignedBy = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[DISS Support] Ticket Assigned: #{$this->ticket->ticket_number} - {$this->ticket->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ticketing.ticket-assigned',
            with: [
                'ticket' => $this->ticket,
                'assignee' => $this->assignee,
                'assignedBy' => $this->assignedBy,
                'ticketUrl' => route('ticketing.show', $this->ticket->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
