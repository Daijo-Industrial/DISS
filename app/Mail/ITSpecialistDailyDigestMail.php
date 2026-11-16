<?php

declare(strict_types=1);

namespace App\Mail;

use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ITSpecialistDailyDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $technician,
        public Collection $assignedTickets,
        public int $unassignedCount,
        public string $dateStr
    ) {}

    public function envelope(): Envelope
    {
        $myCount = $this->assignedTickets->count();

        return new Envelope(
            subject: "[My IT Queue] {$this->dateStr} — You have {$myCount} active ticket(s)",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ticketing.daily-digest-specialist',
            with: [
                'technician' => $this->technician,
                'assignedTickets' => $this->assignedTickets,
                'unassignedCount' => $this->unassignedCount,
                'dateStr' => $this->dateStr,
                'myTicketsUrl' => route('ticketing.list', ['filter' => 'assigned_to_me']),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
