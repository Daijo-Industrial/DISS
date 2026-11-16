<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ITSupportDailyDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<string, int> $workloadStats
     */
    public function __construct(
        public Collection $unassignedTickets,
        public Collection $stalledTickets,
        public array $workloadStats,
        public int $totalActiveCount,
        public string $dateStr
    ) {}

    public function envelope(): Envelope
    {
        $unassignedCount = $this->unassignedTickets->count();
        $stalledCount = $this->stalledTickets->count();

        return new Envelope(
            subject: "[DISS IT Morning Digest] {$this->dateStr} — {$unassignedCount} Unassigned, {$stalledCount} Stalled ({$this->totalActiveCount} Active)",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ticketing.daily-digest-team',
            with: [
                'unassignedTickets' => $this->unassignedTickets,
                'stalledTickets' => $this->stalledTickets,
                'workloadStats' => $this->workloadStats,
                'totalActiveCount' => $this->totalActiveCount,
                'dateStr' => $this->dateStr,
                'queueUrl' => route('ticketing.list', ['filter' => 'unassigned']),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
