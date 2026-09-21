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

class SyncSapInventoryFgJob implements ShouldQueue
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

        Log::info('[SyncSapInventoryFgJob] Starting Inventory FG synchronization...', [
            'startDate' => $this->startDate,
        ]);

        $res = $service->syncInventoryFgUnion($this->startDate);

        if (! $res['success']) {
            Log::error('[SyncSapInventoryFgJob] Failed: ' . $res['message']);
            throw new RuntimeException("SyncSapInventoryFgJob failed: {$res['message']}");
        }

        Log::info('[SyncSapInventoryFgJob] Completed: ' . $res['message']);
    }
}
