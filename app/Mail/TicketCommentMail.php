<?php

declare(strict_types=1);

namespace App\Mail;

use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Entities\TicketActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketCommentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public TicketActivity $activity,
        public string $recipientName,
        public string $authorName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[DISS Support] New reply on Ticket #{$this->ticket->ticket_number} - {$this->ticket->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ticketing.ticket-comment',
            with: [
                'ticket' => $this->ticket,
                'activity' => $this->activity,
                'recipientName' => $this->recipientName,
                'authorName' => $this->authorName,
                'ticketUrl' => route('ticketing.show', $this->ticket->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
