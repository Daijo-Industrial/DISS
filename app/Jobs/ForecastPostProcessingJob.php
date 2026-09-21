<?php

namespace App\Jobs;

use App\Services\Forecast\ForecastDataExplosionService;
use App\Services\Forecast\ForecastMaterialPredictionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ForecastPostProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum job execution time (seconds).
     */
    public int $timeout = 1800;

    /**
     * Tries count.
     */
    public int $tries = 1;

    public function __construct()
    {
        //
    }

    public function handle(
        ForecastDataExplosionService $explosionService,
        ForecastMaterialPredictionService $predictionService
    ): void {
        Log::info('[ForecastPostProcessingJob] Job started.');

        try {
            // Step 1: Explode SAP forecast data into foremind_final
            $explosionResult = $explosionService->explodeForecastData();
            Log::info('[ForecastPostProcessingJob] Step 1 complete: ' . $explosionResult['message']);

            // Step 2: Generate monthly material predictions into forecast_material_predictions
            $predictionResult = $predictionService->generatePredictions();
            Log::info('[ForecastPostProcessingJob] Step 2 complete: ' . $predictionResult['message']);

            Log::info('[ForecastPostProcessingJob] Job completed successfully.');
        } catch (\Throwable $e) {
            Log::error('[ForecastPostProcessingJob] Job failed: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
