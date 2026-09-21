<?php

namespace App\Http\Controllers;

use App\Services\Forecast\ForecastDataExplosionService;
use Illuminate\Http\JsonResponse;

class PurchasingMaterialController extends Controller
{
    public function __construct(
        private readonly ForecastDataExplosionService $explosionService
    ) {
    }

    public function storeDataInNewTable(): JsonResponse
    {
        $result = $this->explosionService->explodeForecastData();

        return response()->json($result);
    }
}
