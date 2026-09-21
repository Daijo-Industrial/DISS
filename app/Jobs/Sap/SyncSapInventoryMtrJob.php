<?php

declare(strict_types=1);

namespace App\Jobs\Sap;

use App\Services\Sap\SapSyncService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SyncSapInventoryMtrJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 3;
    public array $backoff = [15, 30];

    public function __construct(
        public readonly string $startDate
    ) {
    }

    public function handle(SapSyncService $service): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        Log::info('[SyncSapInventoryMtrJob] Starting Inventory MTR synchronization...', [
            'startDate' => $this->startDate,
        ]);

        $res = $service->syncInventoryMtrUnion($this->startDate);

        if (! $res['success']) {
            Log::error('[SyncSapInventoryMtrJob] Failed: ' . $res['message']);
            throw new RuntimeException("SyncSapInventoryMtrJob failed: {$res['message']}");
        }

        Log::info('[SyncSapInventoryMtrJob] Completed: ' . $res['message']);
    }
}
