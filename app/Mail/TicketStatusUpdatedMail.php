<?php

namespace App\Mail;

use App\Domains\Ticketing\Entities\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public string $status,
        public ?string $reason = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[DISS Support] Ticket #{$this->ticket->ticket_number} Updated: {$this->status}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ticketing.ticket-status-updated',
            with: [
                'ticket' => $this->ticket,
                'status' => $this->status,
                'reason' => $this->reason,
                'ticketUrl' => route('ticketing.show', $this->ticket->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
