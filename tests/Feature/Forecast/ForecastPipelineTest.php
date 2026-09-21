<?php

namespace Tests\Feature\Forecast;

use App\Services\Forecast\ForecastDataExplosionService;
use App\Services\Forecast\ForecastMaterialPredictionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ForecastPipelineTest extends TestCase
{
    use DatabaseTransactions;

    protected ForecastDataExplosionService $explosionService;
    protected ForecastMaterialPredictionService $predictionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->explosionService = app(ForecastDataExplosionService::class);
        $this->predictionService = app(ForecastMaterialPredictionService::class);
    }

    public function test_forecast_explosion_and_prediction_pipeline(): void
    {
        // Setup minimal FG forecast and raw material inventory
        DB::table('sap_forecast')->insert([
            'forecast_code' => 'TEST-CUST',
            'forecast_name' => 'TEST FORECAST',
            'item_no' => 'FG-TEST-EXP-01',
            'forecast_date' => '2026-10-01',
            'quantity' => 1000,
        ]);

        DB::table('sap_fct_inventory_mtr')->insert([
            'fg_code' => 'FG-TEST-EXP-01',
            'material_code' => 'MTR-EXP-01',
            'material_name' => 'Explosion Material A',
            'material_quantity' => 2.0,
            'in_stock' => 500.0,
            'item_group' => 104,
            'vendor_code' => 'VML0000999',
            'vendor_name' => 'PIPELINE TEST VENDOR',
            'Measure' => 'PCS',
        ]);

        // Step 1: Run explosion
        $explosionResult = $this->explosionService->explodeForecastData();
        $this->assertTrue($explosionResult['success']);

        $foremindRecord = DB::table('foremind_final')
            ->where('material_code', 'MTR-EXP-01')
            ->first();

        $this->assertNotNull($foremindRecord);
        $this->assertEquals('VML0000999', $foremindRecord->vendor_code);
        $this->assertEquals('PIPELINE TEST VENDOR', $foremindRecord->vendor_name);
        $this->assertEquals(2000, (float) $foremindRecord->material_prediction); // 1000 * 2.0

        // Step 2: Run predictions
        $predictionResult = $this->predictionService->generatePredictions();
        $this->assertTrue($predictionResult['success']);

        $predictionRecord = DB::table('forecast_material_predictions')
            ->where('material_code', 'MTR-EXP-01')
            ->where('vendor_code', 'VML0000999')
            ->first();

        $this->assertNotNull($predictionRecord);
        $this->assertEquals('PIPELINE TEST VENDOR', $predictionRecord->vendor_name);

        $months = json_decode($predictionRecord->months, true);
        $this->assertArrayHasKey('2026-10', $months);
        $this->assertEquals(2000, $months['2026-10']);
    }
}
