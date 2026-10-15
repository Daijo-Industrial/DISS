<?php

namespace App\Mail;

use App\Domains\Ticketing\Entities\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewTicketSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[DISS Support] New Ticket #{$this->ticket->ticket_number}: {$this->ticket->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ticketing.new-ticket-alert',
            with: [
                'ticket' => $this->ticket,
                'ticketUrl' => route('ticketing.show', $this->ticket->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
