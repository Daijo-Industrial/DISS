<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Enums\TicketStatus;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Mail\ITSpecialistDailyDigestMail;
use App\Mail\ITSupportDailyDigestMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailyITSupportDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ticketing:daily-digest {--force : Send digest even if active queue is completely empty}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send 07:30 AM morning IT support queue digest to IT team and active specialists';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Gathering active IT support tickets for morning digest...');

        $activeStatuses = [
            TicketStatus::OPEN->value,
            TicketStatus::IN_PROGRESS->value,
            TicketStatus::ON_HOLD->value,
        ];

        $activeTickets = Ticket::query()
            ->with(['reporter', 'assignee', 'category'])
            ->whereIn('status', $activeStatuses)
            ->orderBy('created_at', 'asc')
            ->get();

        $unassignedTickets = $activeTickets->whereNull('assigned_to')->values();

        $stalledCutoff = Carbon::now('Asia/Jakarta')->subHours(24);
        $stalledTickets = $activeTickets->filter(function ($t) use ($stalledCutoff) {
            return $t->updated_at && $t->updated_at <= $stalledCutoff;
        })->values();

        $totalActiveCount = $activeTickets->count();

        // Fatigue Prevention: Skip if there are zero active tickets, unless explicitly forced
        if ($totalActiveCount === 0 && ! $this->option('force')) {
            $this->info('Active IT ticket queue is empty. Skipping daily digest dispatch.');

            return Command::SUCCESS;
        }

        $dateStr = Carbon::now('Asia/Jakarta')->translatedFormat('d M Y');

        // Build workload stats
        $workloadStats = [];
        foreach ($activeTickets->whereNotNull('assigned_to') as $ticket) {
            $techName = optional($ticket->assignee)->name ?? 'Specialist #' . $ticket->assigned_to;
            $workloadStats[$techName] = ($workloadStats[$techName] ?? 0) + 1;
        }

        // 1. Send Consolidated Team Digest to IT Support Mailbox
        $supportEmail = config('mail.support_address');
        $notifiedTeamEmails = [];

        if (! empty($supportEmail) && filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($supportEmail)->send(new ITSupportDailyDigestMail(
                    $unassignedTickets,
                    $stalledTickets,
                    $workloadStats,
                    $totalActiveCount,
                    $dateStr
                ));
                $notifiedTeamEmails[] = $supportEmail;
                $this->info("Dispatched consolidated IT team digest to: {$supportEmail}");
            } catch (\Throwable $e) {
                Log::error('Failed to send daily IT support team digest: ' . $e->getMessage(), [
                    'email' => $supportEmail,
                ]);
                $this->error("Failed to send team digest to {$supportEmail}: {$e->getMessage()}");
            }
        }

        // Also notify IT administrators who have permission to manage ticketing
        $itAdmins = User::query()
            ->where(function ($query) {
                $query->whereHas('roles', fn ($q) => $q->whereIn('name', ['super-admin', 'admin']))
                    ->orWhereHas('permissions', fn ($q) => $q->whereIn('name', ['ticketing.admin', 'ticketing.manage']));
            })
            ->get();

        foreach ($itAdmins as $admin) {
            if (! empty($admin->email) && filter_var($admin->email, FILTER_VALIDATE_EMAIL) && ! in_array($admin->email, $notifiedTeamEmails, true)) {
                try {
                    Mail::to($admin->email)->send(new ITSupportDailyDigestMail(
                        $unassignedTickets,
                        $stalledTickets,
                        $workloadStats,
                        $totalActiveCount,
                        $dateStr
                    ));
                    $notifiedTeamEmails[] = $admin->email;
                } catch (\Throwable $e) {
                    Log::error('Failed to send daily IT support admin digest: ' . $e->getMessage(), [
                        'admin_id' => $admin->id,
                        'email' => $admin->email,
                    ]);
                }
            }
        }

        // 2. Send Individual Digest to Each Active Specialist
        $assignedGroups = $activeTickets->whereNotNull('assigned_to')->groupBy('assigned_to');
        $dispatchedSpecialists = 0;

        foreach ($assignedGroups as $technicianId => $tickets) {
            $technician = $tickets->first()->assignee ?? User::find($technicianId);

            if ($technician && ! empty($technician->email) && filter_var($technician->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::to($technician->email)->send(new ITSpecialistDailyDigestMail(
                        $technician,
                        $tickets->values(),
                        $unassignedTickets->count(),
                        $dateStr
                    ));
                    $dispatchedSpecialists++;
                } catch (\Throwable $e) {
                    Log::error('Failed to send daily IT specialist digest: ' . $e->getMessage(), [
                        'technician_id' => $technician->id,
                        'email' => $technician->email,
                    ]);
                }
            }
        }

        $this->info("Completed daily IT support digest dispatch (Specialists notified: {$dispatchedSpecialists}).");

        return Command::SUCCESS;
    }
}
