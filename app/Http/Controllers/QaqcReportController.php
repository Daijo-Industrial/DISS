<?php

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\Models\VerificationReport;
use Illuminate\Http\Request;
use App\Exports\MonthlyVerificationExport;
use Maatwebsite\Excel\Facades\Excel;

class QaqcReportController extends Controller
{
    public function monthlyreport(Request $request)
    {
        $reports = VerificationReport::with('items')->get();

        $result = [];

        foreach ($reports as $report) {
            if (!$report->rec_date) continue;
            
            $month = $report->rec_date->format('Y-m');
            $customer = $report->customer ?? 'Unknown';

            if (!isset($result[$month])) {
                $result[$month] = [];
            }
            if (!isset($result[$month][$customer])) {
                $result[$month][$customer] = [
                    'cant_use' => 0,
                    'total_rec_quantity' => 0,
                    'total_price' => 0,
                ];
            }

            foreach ($report->items as $item) {
                $result[$month][$customer]['cant_use'] += (float)$item->cant_use;
                $result[$month][$customer]['total_rec_quantity'] += (float)$item->rec_quantity;
                $result[$month][$customer]['total_price'] += ((float)$item->rec_quantity * (float)$item->price);
            }
        }

        krsort($result);
        return view('qaqc.monthlyreport', compact('result'));
    }

    public function showDetails(Request $request)
    {
        $monthData = $request->input('monthData');
        
        $query = VerificationReport::with('items.defects');
        if ($monthData && $monthData !== 'all') {
            $parts = explode('-', $monthData);
            if (count($parts) === 2) {
                $query->whereYear('rec_date', $parts[0])
                      ->whereMonth('rec_date', $parts[1]);
            }
        }
        
        $reports = $query->get();
        return view('qaqc.monthlyreportdetail', compact('reports'));
    }

    public function export(Request $request)
    {
        $monthData = $request->input('monthData');
        
        if ($monthData && $monthData !== 'all') {
            $parts = explode('-', $monthData);
            if (count($parts) === 2) {
                return Excel::download(new MonthlyVerificationExport((int)$parts[1], (int)$parts[0]), "monthly-verification-{$parts[0]}-{$parts[1]}.xlsx");
            }
        }
        
        // fallback to current month if 'all' or empty
        return Excel::download(new MonthlyVerificationExport(now()->month, now()->year), "monthly-verification-".now()->year."-".now()->month.".xlsx");
    }
}
