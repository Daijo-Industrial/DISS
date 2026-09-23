<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 100000);

use App\Exports\ForExport;
use App\Exports\ForExportCustomer;
use App\Models\ForecastCustomerMaster;
use App\Models\foremindFinal;
use App\Models\PurchasingContact;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class PurchasingDetailController extends Controller
{
    /**
     * Resolve vendor details, consolidated vendor codes, and contact info.
     *
     * @param string $vendorCode
     * @return array{vendorName: string, allVendorCodes: array<string>, vendorCodeDisplay: string, contact: object|null}
     */
    private function resolveConsolidatedVendor(string $vendorCode): array
    {
        $vendorInfo = DB::table('forecast_material_predictions')
            ->where('vendor_code', $vendorCode)
            ->first();

        $vendorName = $vendorInfo?->vendor_name
            ?? DB::table('purchasing_contacts')->where('vendor_code', $vendorCode)->value('vendor_name')
            ?? $vendorCode;

        $allVendorCodes = DB::table('forecast_material_predictions')
            ->where('vendor_name', $vendorName)
            ->distinct()
            ->pluck('vendor_code')
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        if (empty($allVendorCodes)) {
            $allVendorCodes = [$vendorCode];
        }

        $vendorCodeDisplay = implode(', ', $allVendorCodes);

        $contact = DB::table('purchasing_contacts')
            ->whereIn('vendor_code', $allVendorCodes)
            ->where(function ($query) {
                $query->whereNotNull('persontocontact')
                      ->orWhereNotNull('p_member');
            })
            ->first()
            ?? DB::table('purchasing_contacts')
                ->whereIn('vendor_code', $allVendorCodes)
                ->first();

        return [
            'vendorName'        => $vendorName,
            'allVendorCodes'    => $allVendorCodes,
            'vendorCodeDisplay' => $vendorCodeDisplay,
            'contact'           => $contact,
        ];
    }

    public function index(Request $request)
    {
        $vendorCode = $request->input('vendor_code');
        if (! $vendorCode) {
            return redirect()->back()->with('error', 'Vendor code is required.');
        }

        $vendorData = $this->resolveConsolidatedVendor($vendorCode);
        $vendorName = $vendorData['vendorName'];
        $allVendorCodes = $vendorData['allVendorCodes'];
        $vendorCodeDisplay = $vendorData['vendorCodeDisplay'];
        $contact = $vendorData['contact'];

        $materials = DB::table('forecast_material_predictions')
            ->whereIn('vendor_code', $allVendorCodes)
            ->orderBy('material_code')
            ->orderBy('item_no')
            ->get();

        if ($materials->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', "No forecast data found for vendor: {$vendorName} ({$vendorCodeDisplay}) (Internal)");
        }

        // Get unique months efficiently from foremind_final
        $uniqueMonths = DB::table('foremind_final')
            ->whereNotNull('day_forecast')
            ->distinct()
            ->pluck('day_forecast')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $monthm = [];
        $values = [];
        $qforecast = [];

        foreach ($materials as $material) {
            $stringMonths = json_decode($material->months ?? '{}', true) ?? [];
            $stringForecast = json_decode($material->quantity_forecast ?? '{}', true) ?? [];

            $monthm[] = array_keys($stringMonths);
            $values[] = array_values($stringMonths);
            $qforecast[] = array_values($stringForecast);
        }

        return view('purchasing.foremind_detail_print', [
            'monthm'            => $monthm,
            'materials'         => $materials,
            'values'            => $values,
            'mon'               => $uniqueMonths,
            'vendorCode'        => $vendorCode,
            'vendorCodeDisplay' => $vendorCodeDisplay,
            'vendorName'        => $vendorName,
            'contact'           => $contact,
            'qforecast'         => $qforecast,
        ]);
    }

    public function indexCustomer(Request $request)
    {
        $vendorCode = $request->input('vendor_code');
        if (! $vendorCode) {
            return redirect()->back()->with('error', 'Vendor code is required.');
        }

        $vendorData = $this->resolveConsolidatedVendor($vendorCode);
        $vendorName = $vendorData['vendorName'];
        $allVendorCodes = $vendorData['allVendorCodes'];
        $vendorCodeDisplay = $vendorData['vendorCodeDisplay'];
        $contact = $vendorData['contact'];

        $materials = DB::table('forecast_material_predictions')
            ->whereIn('vendor_code', $allVendorCodes)
            ->orderBy('material_code')
            ->orderBy('item_no')
            ->get();

        if ($materials->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', "No forecast data found for vendor: {$vendorName} ({$vendorCodeDisplay}) (Customer)");
        }

        // Get unique months efficiently from foremind_final
        $uniqueMonths = DB::table('foremind_final')
            ->whereNotNull('day_forecast')
            ->distinct()
            ->pluck('day_forecast')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $monthm = [];
        $values = [];
        $qforecast = [];

        foreach ($materials as $material) {
            $stringMonths = json_decode($material->months ?? '{}', true) ?? [];
            $stringForecast = json_decode($material->quantity_forecast ?? '{}', true) ?? [];

            $monthm[] = array_keys($stringMonths);
            $values[] = array_values($stringMonths);
            $qforecast[] = array_values($stringForecast);
        }

        return view('purchasing.foremind_detail_print_customer', [
            'monthm'            => $monthm,
            'materials'         => $materials,
            'values'            => $values,
            'mon'               => $uniqueMonths,
            'vendorCode'        => $vendorCode,
            'vendorCodeDisplay' => $vendorCodeDisplay,
            'vendorName'        => $vendorName,
            'contact'           => $contact,
            'qforecast'         => $qforecast,
        ]);
    }

    public function exportExcel($vendorCode)
    {
        $vendorData = $this->resolveConsolidatedVendor($vendorCode);
        $vendorName = $vendorData['vendorName'];
        $allVendorCodes = $vendorData['allVendorCodes'];
        $vendorCodeDisplay = $vendorData['vendorCodeDisplay'];

        $materials = DB::table('forecast_material_predictions as fmp')
            ->leftJoin('sap_fct_inventory_fgs as inv', 'fmp.item_no', '=', 'inv.item_code')
            ->whereIn('fmp.vendor_code', $allVendorCodes)
            ->select('fmp.*', 'inv.item_name as item_desc')
            ->orderBy('fmp.material_code')
            ->orderBy('fmp.item_no')
            ->get();

        if ($materials->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', "No forecast data found to export for vendor: {$vendorCode}");
        }

        $uniqueMonths = DB::table('foremind_final')
            ->whereNotNull('day_forecast')
            ->distinct()
            ->pluck('day_forecast')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $monthm = [];
        $values = [];
        $qforecast = [];

        foreach ($materials as $material) {
            $stringMonths = json_decode($material->months ?? '{}', true) ?? [];
            $stringForecast = json_decode($material->quantity_forecast ?? '{}', true) ?? [];

            $monthm[] = array_keys($stringMonths);
            $values[] = array_values($stringMonths);
            $qforecast[] = array_values($stringForecast);
        }

        $export = new ForExport(
            $monthm,
            $materials,
            $values,
            $uniqueMonths,
            $vendorCodeDisplay,
            $qforecast,
            $vendorName,
        );

        $fileName = $vendorName ? $vendorName . '_exported_data_INTERNAL.xlsx' : 'filename.xlsx';

        return Excel::download($export, $fileName);
    }

    public function exportExcelcustomer($vendorCode)
    {
        $vendorData = $this->resolveConsolidatedVendor($vendorCode);
        $vendorName = $vendorData['vendorName'];
        $allVendorCodes = $vendorData['allVendorCodes'];
        $vendorCodeDisplay = $vendorData['vendorCodeDisplay'];
        $contact = $vendorData['contact'];

        $materials = DB::table('forecast_material_predictions')
            ->whereIn('vendor_code', $allVendorCodes)
            ->orderBy('material_code')
            ->orderBy('item_no')
            ->get();

        if ($materials->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', "No forecast data found to export for vendor: {$vendorCode}");
        }

        $uniqueMonths = DB::table('foremind_final')
            ->whereNotNull('day_forecast')
            ->distinct()
            ->pluck('day_forecast')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $monthm = [];
        $values = [];
        $qforecast = [];

        foreach ($materials as $material) {
            $stringMonths = json_decode($material->months ?? '{}', true) ?? [];
            $stringForecast = json_decode($material->quantity_forecast ?? '{}', true) ?? [];

            $monthm[] = array_keys($stringMonths);
            $values[] = array_values($stringMonths);
            $qforecast[] = array_values($stringForecast);
        }

        $customers = ForecastCustomerMaster::get();
        foreach ($materials as $material) {
            foreach ($customers as $customer) {
                if ($material->customer === '5H45') {
                    $material->customer = 'ITSP/IKUYO';
                    break;
                } elseif ($material->customer === $customer->forecast_name) {
                    $material->customer = $customer->customer;
                    break;
                }
            }
        }

        $export = new ForExportCustomer(
            $monthm,
            $materials,
            $values,
            $uniqueMonths,
            $vendorCodeDisplay,
            $qforecast,
            $vendorName,
            $contact,
        );

        $fileName = $vendorName ? $vendorName . '_exported_data_Customer.xlsx' : 'filename.xlsx';

        return Excel::download($export, $fileName);
    }
}
