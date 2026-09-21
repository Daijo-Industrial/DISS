<?php

declare(strict_types=1);

namespace App\Services\Forecast;

use App\Models\PurchasingUpdateLog;
use App\Models\sapForecast;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ForecastDataExplosionService
{
    /**
     * Explode SAP forecasts and multi-level BOMs into foremind_final table atomically.
     *
     * @return array{success: bool, message: string, count: int}
     */
    public function explodeForecastData(): array
    {
        Log::info('[ForecastDataExplosionService] Starting forecast data explosion...');
        DB::disableQueryLog();

        // Update purchasing update log
        try {
            $log = PurchasingUpdateLog::find(1);
            if ($log) {
                $log->updated_at = Carbon::now();
                $log->save();
            }
        } catch (Throwable $e) {
            Log::warning('[ForecastDataExplosionService] Could not update PurchasingUpdateLog: ' . $e->getMessage());
        }

        try {
            // Case 1: Forecasts without WIP (directly mapped to raw materials)
            $case1Data = sapForecast::whereNotIn('item_no', function ($query) {
                $query->select('fg_code')->from('sap_fct_bom_wip');
            })
                ->with('inventoryMtr')
                ->get();

            // Case 2: Level 0 WIP with raw materials
            $case2Data = sapForecast::whereHas('bomWip', function ($query) {
                $query->whereHas('rawMaterialFgcode');
            })
                ->with('bomWip.rawMaterialFgcode')
                ->get();

            // Case 3: Level 1 BOM WIP
            $case3Data = sapForecast::whereHas('firstBomWip', function ($query) {
                $query->where('level', 1)->whereHas('semiFirstInventoryMtrForecast');
            })
                ->with('firstBomWip.semiFirstInventoryMtrForecast')
                ->get();

            // Case 4: Level 2 BOM WIP
            $case4Data = sapForecast::whereHas('secondBomWip', function ($query) {
                $query->where('level', 2)->whereHas('semiSecondInventoryMtrForecast');
            })
                ->with('secondBomWip.semiSecondInventoryMtrForecast')
                ->get();

            // Case 5: Level 3 BOM WIP
            $case5Data = sapForecast::whereHas('thirdBomWip', function ($query) {
                $query->where('level', 3)->whereHas('semiThirdInventoryMtrForecast');
            })
                ->with('thirdBomWip.semiThirdInventoryMtrForecast')
                ->get();

            $insertedCount = 0;

            // Execute all insertions inside a transaction
            DB::transaction(function () use ($case1Data, $case2Data, $case3Data, $case4Data, $case5Data, &$insertedCount) {
                DB::table('foremind_final')->delete();

                $insertedCount += $this->insertFinal($case1Data);
                $insertedCount += $this->insertFinalRest($case2Data);
                $insertedCount += $this->insertFinalRest1($case3Data);
                $insertedCount += $this->insertFinalRest2($case4Data);
                $insertedCount += $this->insertFinalRest3($case5Data);
            });

            Log::info("[ForecastDataExplosionService] Explosion completed successfully. Total records: {$insertedCount}");

            return [
                'success' => true,
                'message' => "Successfully exploded {$insertedCount} forecast records into foremind_final.",
                'count'   => $insertedCount,
            ];
        } catch (Throwable $e) {
            Log::error('[ForecastDataExplosionService] Explosion failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function insertFinal($data): int
    {
        $count = 0;
        $inserts = [];
        foreach ($data as $item) {
            $inventoryMtra = $item->inventoryMtr;
            foreach ($inventoryMtra as $inventoryMtrData) {
                $inserts[] = [
                    'forecast_code'       => $item->forecast_code,
                    'forecast_name'       => $item->forecast_name,
                    'vendor_code'         => $inventoryMtrData->vendor_code,
                    'vendor_name'         => $inventoryMtrData->vendor_name,
                    'day_forecast'        => $item->forecast_date,
                    'Item_no'             => $item->item_no,
                    'quantity_forecast'   => $item->quantity,
                    'item_group'          => $inventoryMtrData->item_group,
                    'material_code'       => $inventoryMtrData->material_code,
                    'material_name'       => $inventoryMtrData->material_name,
                    'quantity_material'   => $inventoryMtrData->material_quantity,
                    'material_prediction' => $inventoryMtrData->material_quantity * $item->quantity,
                    'U/M'                 => $inventoryMtrData->Measure,
                    'forecast_date'       => $item->forecast_date,
                    'quantity'            => $item->quantity,
                ];

                if (count($inserts) >= 500) {
                    DB::table('foremind_final')->insert($inserts);
                    $count += count($inserts);
                    $inserts = [];
                }
            }
        }
        if (count($inserts) > 0) {
            DB::table('foremind_final')->insert($inserts);
            $count += count($inserts);
        }

        return $count;
    }

    private function insertFinalRest($data): int
    {
        $count = 0;
        $inserts = [];
        foreach ($data as $item) {
            $bomWipI = $item->bomWip;
            foreach ($bomWipI as $bomWipItem) {
                $inventoryQu = $bomWipItem->rawMaterialFgcode;

                foreach ($inventoryQu as $inventoryQuantity) {
                    $inserts[] = [
                        'forecast_code'       => $item->forecast_code,
                        'forecast_name'       => $item->forecast_name,
                        'vendor_code'         => $inventoryQuantity->vendor_code,
                        'vendor_name'         => $inventoryQuantity->vendor_name,
                        'day_forecast'        => $item->forecast_date,
                        'Item_no'             => $item->item_no,
                        'quantity_forecast'   => $item->quantity,
                        'item_group'          => $inventoryQuantity->item_group,
                        'material_code'       => $inventoryQuantity->material_code,
                        'material_name'       => $inventoryQuantity->material_name,
                        'quantity_material'   => $inventoryQuantity->material_quantity,
                        'material_prediction' => $inventoryQuantity->material_quantity * $item->quantity,
                        'U/M'                 => $inventoryQuantity->Measure,
                        'forecast_date'       => $item->forecast_date,
                        'quantity'            => $item->quantity,
                    ];

                    if (count($inserts) >= 500) {
                        DB::table('foremind_final')->insert($inserts);
                        $count += count($inserts);
                        $inserts = [];
                    }
                }
            }
        }
        if (count($inserts) > 0) {
            DB::table('foremind_final')->insert($inserts);
            $count += count($inserts);
        }

        return $count;
    }

    private function insertFinalRest1($data): int
    {
        $count = 0;
        $inserts = [];
        foreach ($data as $item) {
            $bomWipI = $item->firstBomWip;

            foreach ($bomWipI as $bomWipItem) {
                $bom_quantity = $bomWipItem->bom_quantity;
                $semi_code = $bomWipItem->semi_first;
                $inventoryQu = $bomWipItem->semiFirstInventoryMtrForecast;

                foreach ($inventoryQu as $inventoryQuantity) {
                    $inserts[] = [
                        'forecast_code'       => $item->forecast_code,
                        'forecast_name'       => $item->forecast_name,
                        'vendor_code'         => $inventoryQuantity->vendor_code,
                        'vendor_name'         => $inventoryQuantity->vendor_name,
                        'day_forecast'        => $item->forecast_date,
                        'Item_no'             => $item->item_no,
                        'semi_code'           => $semi_code,
                        'quantity_forecast'   => $item->quantity,
                        'item_group'          => $inventoryQuantity->item_group,
                        'material_code'       => $inventoryQuantity->material_code,
                        'material_name'       => $inventoryQuantity->material_name,
                        'quantity_material'   => $inventoryQuantity->material_quantity * $bom_quantity,
                        'quantity_bomWip'     => $bom_quantity,
                        'material_prediction' => $inventoryQuantity->material_quantity * $bom_quantity * $item->quantity,
                        'U/M'                 => $inventoryQuantity->Measure,
                        'forecast_date'       => $item->forecast_date,
                        'quantity'            => $item->quantity,
                    ];

                    if (count($inserts) >= 500) {
                        DB::table('foremind_final')->insert($inserts);
                        $count += count($inserts);
                        $inserts = [];
                    }
                }
            }
        }
        if (count($inserts) > 0) {
            DB::table('foremind_final')->insert($inserts);
            $count += count($inserts);
        }

        return $count;
    }

    private function insertFinalRest2($data): int
    {
        $count = 0;
        $inserts = [];
        foreach ($data as $item) {
            $bomWipItem = $item->secondBomWip;

            foreach ($bomWipItem as $bomWipItems) {
                $bom_quantity = $bomWipItems->bom_quantity;
                $semi_code = $bomWipItems->semi_second;

                $inventoryQuantity = $bomWipItems->semiSecondInventoryMtrForecast;
                foreach ($inventoryQuantity as $secondInventory) {
                    $inserts[] = [
                        'forecast_code'       => $item->forecast_code,
                        'forecast_name'       => $item->forecast_name,
                        'vendor_code'         => $secondInventory->vendor_code,
                        'vendor_name'         => $secondInventory->vendor_name,
                        'day_forecast'        => $item->forecast_date,
                        'Item_no'             => $item->item_no,
                        'semi_code'           => $semi_code,
                        'quantity_forecast'   => $item->quantity,
                        'item_group'          => $secondInventory->item_group,
                        'material_code'       => $secondInventory->material_code,
                        'material_name'       => $secondInventory->material_name,
                        'quantity_material'   => $secondInventory->material_quantity * $bom_quantity,
                        'quantity_bomWip'     => $bom_quantity,
                        'material_prediction' => $secondInventory->material_quantity * $bom_quantity * $item->quantity,
                        'U/M'                 => $secondInventory->Measure,
                        'forecast_date'       => $item->forecast_date,
                        'quantity'            => $item->quantity,
                    ];

                    if (count($inserts) >= 500) {
                        DB::table('foremind_final')->insert($inserts);
                        $count += count($inserts);
                        $inserts = [];
                    }
                }
            }
        }
        if (count($inserts) > 0) {
            DB::table('foremind_final')->insert($inserts);
            $count += count($inserts);
        }

        return $count;
    }

    private function insertFinalRest3($data): int
    {
        $count = 0;
        $inserts = [];
        foreach ($data as $item) {
            $bomWipItem = $item->thirdBomWip;
            foreach ($bomWipItem as $bomWipItems) {
                $bom_quantity = $bomWipItems->bom_quantity;
                $semi_code = $bomWipItems->semi_third;
                $inventoryQuantity = $bomWipItems->semiThirdInventoryMtrForecast;
                foreach ($inventoryQuantity as $thirdInventory) {
                    $inserts[] = [
                        'forecast_code'       => $item->forecast_code,
                        'forecast_name'       => $item->forecast_name,
                        'vendor_code'         => $thirdInventory->vendor_code,
                        'vendor_name'         => $thirdInventory->vendor_name,
                        'day_forecast'        => $item->forecast_date,
                        'Item_no'             => $item->item_no,
                        'semi_code'           => $semi_code,
                        'quantity_forecast'   => $item->quantity,
                        'item_group'          => $thirdInventory->item_group,
                        'material_code'       => $thirdInventory->material_code,
                        'material_name'       => $thirdInventory->material_name,
                        'quantity_material'   => $thirdInventory->material_quantity * $bom_quantity,
                        'quantity_bomWip'     => $bom_quantity,
                        'material_prediction' => $thirdInventory->material_quantity * $bom_quantity * $item->quantity,
                        'U/M'                 => $thirdInventory->Measure,
                        'forecast_date'       => $item->forecast_date,
                        'quantity'            => $item->quantity,
                    ];

                    if (count($inserts) >= 500) {
                        DB::table('foremind_final')->insert($inserts);
                        $count += count($inserts);
                        $inserts = [];
                    }
                }
            }
        }
        if (count($inserts) > 0) {
            DB::table('foremind_final')->insert($inserts);
            $count += count($inserts);
        }

        return $count;
    }
}
