<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ForecastPostProcessingJob;
use App\Jobs\Sap\SyncSapBomWipJob;
use App\Jobs\Sap\SyncSapForecastJob;
use App\Jobs\Sap\SyncSapInventoryFgJob;
use App\Jobs\Sap\SyncSapInventoryMtrJob;
use App\Jobs\Sap\SyncSapLineProductionJob;
use App\Services\Sap\SapSyncService;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SyncSapData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:sync 
                            {--endpoint=all : Specific endpoint or group to sync, or "all" to sync everything} 
                            {--date= : Custom start date in YYYY-MM-DD format}
                            {--queue : Dispatch sync as an asynchronous batch to background queue workers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data from SAP API endpoints into local database tables';

    /**
     * Execute the console command.
     */
    public function handle(SapSyncService $service): int
    {
        $endpointOpt = $this->option('endpoint');
        $dateOpt = $this->option('date');
        $startDate = $dateOpt ?: $service->getStartDate();

        $this->info("SAP Sync started using startDate: {$startDate}");

        // If --queue is requested and syncing all endpoints, dispatch as a resilient Bus::batch
        if ($this->option('queue')) {
            if ($endpointOpt !== 'all') {
                $this->warn('--queue option is designed for full sync (--endpoint=all). Dispatching full batch...');
            }

            $batch = Bus::batch([
                new SyncSapBomWipJob($startDate),
                new SyncSapInventoryMtrJob($startDate),
                new SyncSapInventoryFgJob($startDate),
                new SyncSapLineProductionJob($startDate),
                new SyncSapForecastJob($startDate),
            ])->then(function (Batch $batch) {
                Log::info('[SAP Sync Batch] All 5 sync groups completed successfully. Dispatching ForecastPostProcessingJob.');
                ForecastPostProcessingJob::dispatch();
            })->catch(function (Batch $batch, Throwable $e) {
                Log::error('[SAP Sync Batch] Batch encountered a failure: ' . $e->getMessage());
            })->name("SAP Sync Batch ({$startDate})")
              ->dispatch();

            $this->info("SAP Sync batch [{$batch->id}] dispatched to background queue!");
            $this->info('ForecastPostProcessingJob will automatically run once all 5 groups complete successfully.');

            return 0;
        }

        // Synchronous execution
        if ($endpointOpt === 'all') {
            $this->info('Syncing all SAP endpoints synchronously...');

            $groups = [
                'BOM WIP Group'       => fn () => $service->syncBomWipGroup($startDate),
                'Inventory MTR Union' => fn () => $service->syncInventoryMtrUnion($startDate),
                'Inventory FG Union'  => fn () => $service->syncInventoryFgUnion($startDate),
                'Line Production'     => fn () => $service->syncLineProductionUnion($startDate),
                'Forecast Demands'    => fn () => $service->syncForecastGroup($startDate),
            ];

            $errors = 0;
            $step = 1;
            $total = count($groups);

            foreach ($groups as $label => $syncAction) {
                $this->info("[{$step}/{$total}] Syncing: {$label}...");
                $res = $syncAction();

                if ($res['success']) {
                    $this->info("  -> {$res['message']}");
                } else {
                    $this->error("  -> Failed: {$res['message']}");
                    $errors++;
                }
                $step++;
            }

            if ($errors > 0) {
                $this->error("SAP Sync finished with {$errors} error(s). Post-processing skipped.");
                return 1;
            }

            $this->info('All SAP endpoint groups synced successfully!');

            // Dispatch forecast post-processing job
            ForecastPostProcessingJob::dispatch();
            $this->info('ForecastPostProcessingJob dispatched to queue.');

            return 0;
        }

        // Sync a specific endpoint
        $this->info("Syncing specific endpoint: {$endpointOpt}...");
        $res = $service->syncEndpoint($endpointOpt, $startDate);

        if (! $res['success']) {
            $this->error("Failed: {$res['message']}");
            return 1;
        }

        $this->info($res['message']);

        if (str_contains($endpointOpt, 'sap_fct_bom_wip')) {
            $this->info('Performing BOM WIP post-processing union...');
            $unionRes = $service->processBomWipUnion();
            if ($unionRes['success']) {
                $this->info("  -> {$unionRes['message']}");
            } else {
                $this->error("  -> Failed: {$unionRes['message']}");
                return 1;
            }
        }

        $this->info('Endpoint sync completed successfully!');
        return 0;
    }
}
