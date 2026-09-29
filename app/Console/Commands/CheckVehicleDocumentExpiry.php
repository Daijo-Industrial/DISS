<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VehicleDocument;
use App\Notifications\VehicleDocumentExpiryNotification;
use Illuminate\Console\Command;

class CheckVehicleDocumentExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fleet:check-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa masa berlaku dokumen kendaraan (KIR & STNK) dan kirimkan notifikasi reminder ke tim GA/Admin.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memeriksa dokumen legalitas armada (KIR, STNK 1th & 5th)...');

        $cutoffDate = now()->addDays(30)->toDateString();

        // Cari dokumen yang expired atau mendekati expired (H-30) pada kendaraan aktif
        $documents = VehicleDocument::with(['vehicle'])
            ->whereHas('vehicle', function ($q) {
                $q->whereNull('deleted_at')
                    ->whereNotIn('status', ['sold', 'retired']);
            })
            ->where('expired_date', '<=', $cutoffDate)
            ->orderBy('expired_date')
            ->get();

        if ($documents->isEmpty()) {
            $this->info('Semua dokumen legalitas armada dalam kondisi aman (> 30 hari).');

            return Command::SUCCESS;
        }

        $this->warn(sprintf('Ditemukan %d dokumen yang membutuhkan perhatian.', $documents->count()));

        // Ambil penerima notifikasi (Super Admin & Personalia/GA)
        $recipients = User::query()
            ->where(function ($query) {
                $query->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))
                    ->orWhereHas('department', fn ($q) => $q->whereIn('name', ['PERSONALIA', 'GA']));
            })
            ->get();

        if ($recipients->isEmpty()) {
            // Fallback ke user pertama jika role/departemen belum diisi
            $recipients = User::take(1)->get();
        }

        $tableData = [];

        foreach ($documents as $doc) {
            $plate = $doc->vehicle?->plate_number ?? 'N/A';
            $days = $doc->days_remaining;
            $tableData[] = [
                'Plat' => $plate,
                'Jenis' => $doc->type_label,
                'Jatuh Tempo' => $doc->expired_date->format('d/m/Y'),
                'Sisa Hari' => $days < 0 ? abs($days) . ' hari lalu (EXPIRED)' : $days . ' hari lagi',
                'Status' => strtoupper($doc->status),
            ];

            foreach ($recipients as $recipient) {
                $recipient->notify(new VehicleDocumentExpiryNotification($doc));
            }
        }

        $this->table(['Plat Nomor', 'Jenis Dokumen', 'Jatuh Tempo', 'Sisa Hari', 'Status'], $tableData);
        $this->info(sprintf('Notifikasi berhasil dikirimkan ke %d penerima.', $recipients->count()));

        return Command::SUCCESS;
    }
}
