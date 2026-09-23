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
        // Retrieve distinct vendor codes and names from forecast_material_predictions
        $predictionVendors = DB::table('forecast_material_predictions')
            ->select('vendor_code', 'vendor_name')
            ->distinct()
            ->whereNotNull('vendor_code')
            ->where('vendor_code', '!=', '')
            ->get();

        if ($predictionVendors->isNotEmpty()) {
            $availableVendorCodes = $predictionVendors->pluck('vendor_code')->unique()->toArray();
            $contactsRaw = PurchasingContact::whereIn('vendor_code', $availableVendorCodes)->get();

            // Group by trimmed vendor_name
            $groupedByVendor = $predictionVendors->groupBy(function ($item) {
                return trim($item->vendor_name ?: $item->vendor_code);
            });

            $contacts = $groupedByVendor->map(function ($items, $vendorName) use ($contactsRaw) {
                $codes = $items->pluck('vendor_code')->unique()->sort()->values();
                $primaryCode = $codes->first();

                // Find best matching contact record for these codes
                $contactRecord = $contactsRaw->whereIn('vendor_code', $codes)->first(function ($c) {
                    return !empty($c->persontocontact) || !empty($c->p_member);
                }) ?? $contactsRaw->whereIn('vendor_code', $codes)->first();

                return (object) [
                    'vendor_code'     => $primaryCode,
                    'vendor_name'     => $vendorName,
                    'all_codes'       => $codes->toArray(),
                    'codes_display'   => $codes->implode(', '),
                    'p_member'        => $contactRecord?->p_member,
                    'persontocontact' => $contactRecord?->persontocontact,
                ];
            })->sortBy('vendor_name', SORT_NATURAL | SORT_FLAG_CASE)->values();
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
