<?php

namespace App\Http\Controllers;

use App\Services\Forecast\ForecastMaterialPredictionService;
use Illuminate\Http\JsonResponse;

class MaterialPredictionController extends Controller
{
    public function __construct(
        private readonly ForecastMaterialPredictionService $predictionService
    ) {
    }

    public function processForemindFinalData(): JsonResponse
    {
        $result = $this->predictionService->generatePredictions();

        return response()->json($result);
    }
}
