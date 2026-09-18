<?php

namespace App\Http\Controllers;

use App\Enums\ToDepartment;
use App\Models\foremindFinal;
use App\Models\PurchaseRequest;
use App\Models\PurchasingContact;
use App\Models\PurchasingUpdateLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PurchasingController extends Controller
{
    public function index()
    {
        $statuses = [
            'approved' => 4,
            'rejected' => 5,
            'waitingDeptHead' => 1,
            'waitingPurchaser' => 6,
            'waitingGm' => 7,
            'waitingVerificator' => 2,
            'waitingDirector' => 3,
        ];

        $data = [];

        foreach ($statuses as $key => $status) {
            $data[$key] = PurchaseRequest::where('status', $status)
                ->where('to_department', ToDepartment::PURCHASING->value)
                ->whereHas('createdBy', function ($query) {
                    $query->orWhere('id', auth()->user()->id);
                })
                ->orWhere('from_department', ToDepartment::PURCHASING->value)
                ->get()
                ->count();
        }

        $twoDaysAgo = Carbon::now()->subDays(2);

        $prOver2Days = PurchaseRequest::where('status', $status)
            ->where('to_department', ToDepartment::PURCHASING->value)
            ->whereHas('createdBy', function ($query) {
                $query->orWhere('id', auth()->user()->id);
            })
            ->whereDate('created_at', '<=', $twoDaysAgo)
            ->get();

        return view('purchasing.purchasing_landing', compact('data', 'prOver2Days'));
    }

    public function indexhome()
    {
        $log = PurchasingUpdateLog::find(1);

        // Retrieve forecasts from the foremindFinal table
        $forecasts = ForemindFinal::all();
        // Retrieve distinct vendor codes that actually exist in forecast_material_predictions
        $availableVendorCodes = DB::table('forecast_material_predictions')
            ->distinct()
            ->pluck('vendor_code')
            ->filter()
            ->toArray();

        if (!empty($availableVendorCodes)) {
            $contacts = PurchasingContact::whereIn('vendor_code', $availableVendorCodes)->get();
            $existingCodes = $contacts->pluck('vendor_code')->toArray();
            $missingCodes = array_diff($availableVendorCodes, $existingCodes);
            if (!empty($missingCodes)) {
                $extraVendors = DB::table('forecast_material_predictions')
                    ->whereIn('vendor_code', $missingCodes)
                    ->select('vendor_code', 'vendor_name')
                    ->distinct()
                    ->get();
                $contacts = $contacts->concat($extraVendors);
            }
            $contacts = $contacts->sortBy('vendor_name')->values();
        } else {
            $contacts = collect();
        }

        // Get unique months from all forecasts
        $allMonths = [];

        foreach ($forecasts as $forecast) {
            $dayForecast = Carbon::parse($forecast->day_forecast);
            $allMonths[] = $dayForecast->format('Y-m');
        }

        // Ensure unique months and sort them
        $uniqueMonths = array_unique($allMonths);
        sort($uniqueMonths);

        // Fetch your materials data from the database
        $materials = DB::table('forecast_material_predictions')->paginate(10);
        $values = [];
        $qforecast = [];

        foreach ($materials as $material) {
            $stringForecast = json_decode($material->quantity_forecast, true) ?? [];
            $stringMonths = json_decode($material->months, true) ?? [];

            $values[] = array_values($stringMonths);
            $qforecast[] = array_values($stringForecast);
        }

        return view('purchasing.foremind_detail', [
            // 'monthm' => $monthm, // Ensure this is the correct data
            'materials' => $materials,
            'values' => $values,
            'mon' => $uniqueMonths,
            'qforecast' => $qforecast,
            'contacts' => $contacts,
            'log' => $log,
        ]);
    }
}
