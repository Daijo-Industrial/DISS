<?php

declare(strict_types=1);

namespace App\Services\Forecast;

use App\Models\foremindFinal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ForecastMaterialPredictionService
{
    /**
     * Process foremind_final data and generate forecast_material_predictions atomically.
     *
     * @return array{success: bool, message: string, count: int}
     */
    public function generatePredictions(): array
    {
        Log::info('[ForecastMaterialPredictionService] Starting material prediction generation...');
        DB::disableQueryLog();

        try {
            // Retrieve unique months in a database query first to avoid multiple iterations
            $uniqueMonths = foremindFinal::query()
                ->select('day_forecast')
                ->whereNotNull('day_forecast')
                ->distinct()
                ->pluck('day_forecast')
                ->map(fn ($date) => Carbon::parse($date)->format('Y-m'))
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            // Stream foremind_final records via cursor
            $forecasts = foremindFinal::query()->select([
                'material_code',
                'material_name',
                'forecast_code',
                'Item_no',
                'U/M',
                'quantity_material',
                'vendor_name',
                'vendor_code',
                'day_forecast',
                'material_prediction',
                'quantity_forecast',
            ])->cursor();

            $transformedData = [];
            $monthYear = null;

            foreach ($forecasts as $forecast) {
                $materialCode     = $forecast->material_code;
                $materialName     = $forecast->material_name;
                $customer         = $forecast->forecast_code;
                $itemNo           = $forecast->Item_no;
                $unitOfMeasure    = $forecast->{'U/M'};
                $materialquantity = (float) $forecast->quantity_material;
                $vendorName       = $forecast->vendor_name;
                $vendorCode       = $forecast->vendor_code;

                if (! isset($transformedData[$materialCode])) {
                    $transformedData[$materialCode] = [];
                }
                if (! isset($transformedData[$materialCode][$materialName])) {
                    $transformedData[$materialCode][$materialName] = [];
                }
                if (! isset($transformedData[$materialCode][$materialName][$customer])) {
                    $transformedData[$materialCode][$materialName][$customer] = [];
                }
                if (! isset($transformedData[$materialCode][$materialName][$customer][$itemNo])) {
                    $transformedData[$materialCode][$materialName][$customer][$itemNo] = [];
                }
                if (! isset($transformedData[$materialCode][$materialName][$customer][$itemNo][$unitOfMeasure])) {
                    $transformedData[$materialCode][$materialName][$customer][$itemNo][$unitOfMeasure] = [];
                }

                $quantityKey = (string) $materialquantity;

                if (! isset($transformedData[$materialCode][$materialName][$customer][$itemNo][$unitOfMeasure][$quantityKey])) {
                    $transformedData[$materialCode][$materialName][$customer][$itemNo][$unitOfMeasure][$quantityKey] = [];
                }
                if (! isset($transformedData[$materialCode][$materialName][$customer][$itemNo][$unitOfMeasure][$quantityKey][$vendorCode])) {
                    $transformedData[$materialCode][$materialName][$customer][$itemNo][$unitOfMeasure][$quantityKey][$vendorCode] = [];
                }
                if (! isset($transformedData[$materialCode][$materialName][$customer][$itemNo][$unitOfMeasure][$quantityKey][$vendorCode][$vendorName])) {
                    $transformedData[$materialCode][$materialName][$customer][$itemNo][$unitOfMeasure][$quantityKey][$vendorCode][$vendorName] = [
                        'months'            => [],
                        'quantity_forecast' => [],
                    ];
                }

                $dayForecast = Carbon::parse($forecast->day_forecast);
                $monthYear   = $dayForecast->format('Y-m');

                foreach ($uniqueMonths as $uniqueMonth) {
                    if (! isset($transformedData[$materialCode][$materialName][$customer][$itemNo][$unitOfMeasure][$quantityKey][$vendorCode][$vendorName]['months'][$uniqueMonth])) {
                        $transformedData[$materialCode][$materialName][$customer][$itemNo][$unitOfMeasure][$quantityKey][$vendorCode][$vendorName]['months'][$uniqueMonth] = 0;
                        $transformedData[$materialCode][$materialName][$customer][$itemNo][$unitOfMeasure][$quantityKey][$vendorCode][$vendorName]['quantity_forecast'][$uniqueMonth] = 0;
                    }
                }

                $transformedData[$materialCode][$materialName][$customer][$itemNo][$unitOfMeasure][$quantityKey][$vendorCode][$vendorName]['months'][$monthYear] += $forecast->material_prediction;
                $transformedData[$materialCode][$materialName][$customer][$itemNo][$unitOfMeasure][$quantityKey][$vendorCode][$vendorName]['quantity_forecast'][$monthYear] = $forecast->quantity_forecast;
            }

            $totalInserted = 0;

            // Atomically replace prediction records in a database transaction
            DB::transaction(function () use ($transformedData, $monthYear, &$totalInserted) {
                DB::table('forecast_material_predictions')->delete();

                $inserts = [];
                foreach ($transformedData as $materialCode => $materialData) {
                    foreach ($materialData as $materialName => $unitData) {
                        foreach ($unitData as $customer => $nextdata) {
                            foreach ($nextdata as $itemNo => $unitDatas) {
                                foreach ($unitDatas as $unitOfMeasure => $UnitM) {
                                    foreach ($UnitM as $quantityKey => $vendorData) {
                                        foreach ($vendorData as $vendorCode => $dataVendor) {
                                            foreach ($dataVendor as $vendorName => $data) {
                                                $inserts[] = [
                                                    'material_code'     => $materialCode,
                                                    'material_name'     => $materialName,
                                                    'customer'          => $customer,
                                                    'item_no'           => $itemNo,
                                                    'unit_of_measure'   => $unitOfMeasure,
                                                    'quantity_material' => $quantityKey,
                                                    'vendor_code'       => $vendorCode,
                                                    'vendor_name'       => $vendorName,
                                                    'months'            => json_encode($data['months'] + ($monthYear !== null ? [$monthYear => 0] : [])),
                                                    'quantity_forecast' => json_encode($data['quantity_forecast']),
                                                ];

                                                if (count($inserts) >= 500) {
                                                    DB::table('forecast_material_predictions')->insert($inserts);
                                                    $totalInserted += count($inserts);
                                                    $inserts = [];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (count($inserts) > 0) {
                    DB::table('forecast_material_predictions')->insert($inserts);
                    $totalInserted += count($inserts);
                }
            });

            Log::info("[ForecastMaterialPredictionService] Material predictions generated successfully. Total records: {$totalInserted}");

            return [
                'success' => true,
                'message' => "Successfully generated {$totalInserted} material predictions.",
                'count'   => $totalInserted,
            ];
        } catch (Throwable $e) {
            Log::error('[ForecastMaterialPredictionService] Generation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
