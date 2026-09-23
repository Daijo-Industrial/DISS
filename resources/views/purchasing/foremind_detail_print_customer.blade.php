@extends('new.layouts.app')

@section('content')
    <style>
        .print-container {
            background-color: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #dee2e6 !important;
            padding: 6px 8px !important;
            font-size: 12px;
            vertical-align: middle;
        }

        .table-bordered thead th {
            background-color: #f8fafc !important;
            color: #334155;
            font-weight: 600;
        }

        .vendor-meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        @media print {
            aside, header, nav, .no-print {
                display: none !important;
            }

            main {
                margin: 0 !important;
                padding: 0 !important;
            }

            body {
                background: #fff !important;
                font-size: 10px !important;
            }

            .print-container {
                box-shadow: none !important;
                padding: 0 !important;
                border-radius: 0 !important;
            }

            .vendor-meta-box {
                background-color: transparent !important;
                border: 1px solid #000 !important;
            }

            .table-bordered th,
            .table-bordered td {
                border: 1px solid #000 !important;
                padding: 3px 5px !important;
                font-size: 10px !important;
            }

            @page {
                size: landscape;
                margin: 8mm;
            }
        }
    </style>

    <div class="print-container">
        <!-- Top Action Bar (hidden on print) -->
        <div class="no-print flex flex-wrap items-center justify-between gap-3 mb-6 pb-4 border-b border-slate-200">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Forecast Material Prediction (Customer)</h1>
                <p class="text-sm text-slate-500">Preview and print the customer-oriented forecast sheet.</p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print Document
                </button>
                <a href="/foremind-detail/print/customer/excel/{{ $vendorCode }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export to Excel
                </a>
                <button onclick="window.close()" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-medium rounded-lg transition">
                    Close
                </button>
            </div>
        </div>

        <!-- Document Header -->
        <section class="invoice">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-300">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 tracking-tight">PT Daijo Industrial</h2>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Customer Material Forecast Report</p>
                </div>
                <div class="text-right text-xs text-slate-600">
                    <p><strong>Print Date:</strong> {{ now()->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <!-- Vendor Meta Box -->
            <div class="vendor-meta-box">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="text-slate-500 block">Vendor Name</span>
                        <strong class="text-slate-800 text-sm">{{ $vendorName }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Vendor Code</span>
                        <strong class="text-slate-800 text-sm">{{ $vendorCodeDisplay ?? $vendorCode }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block">ATT (Contact Person)</span>
                        <strong class="text-slate-800">{{ $contact->persontocontact ?? '-' }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block">FR (Purchasing PIC)</span>
                        <strong class="text-slate-800">{{ $contact->p_member ?? '-' }}</strong>
                    </div>
                </div>
            </div>

            <!-- Table Row -->
            @if (!empty($materials) && $materials->isNotEmpty())
                <div class="table-responsive overflow-x-auto">
                    <table class="table table-bordered w-full text-left">
                        <thead>
                            <tr>
                                <th class="table-bordered" style="width: 50px;">No</th>
                                <th class="table-bordered">Material Code</th>
                                <th class="table-bordered">Material Name</th>
                                <th class="table-bordered text-center">Unit Measure</th>
                                @foreach ($mon as $month)
                                    <th class="table-bordered text-center">{{ \Carbon\Carbon::parse($month)->format('M-Y') }}</th>
                                @endforeach
                                <th class="table-bordered text-right">Total</th>
                                <th class="table-bordered">Customer</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $colCount = count($qforecast[0] ?? []);
                                $currentMaterialCode = null;
                                $currentMaterialName = null;
                                $currentMaterialMeasure = null;
                                $currentCustomers = [];
                                $monthlyTotals = array_fill(0, $colCount, 0);
                                $rowNumber = 1;
                            @endphp

                            @foreach ($materials as $key => $material)
                                @if ($material->material_code != $currentMaterialCode)
                                    @if ($currentMaterialCode !== null)
                                        <tr>
                                            <td class="table-bordered text-center">{{ $rowNumber++ }}</td>
                                            <td class="table-bordered font-medium">{{ $currentMaterialCode }}</td>
                                            <td class="table-bordered">{{ $currentMaterialName }}</td>
                                            <td class="table-bordered text-center">{{ $currentMaterialMeasure }}</td>
                                            @foreach ($monthlyTotals as $monthlyTotal)
                                                <td class="table-bordered text-right font-medium">
                                                    {{ number_format($monthlyTotal, 2) }}
                                                </td>
                                            @endforeach
                                            <td class="table-bordered text-right font-bold">
                                                {{ number_format(array_sum($monthlyTotals), 2) }}
                                            </td>
                                            <td class="table-bordered font-medium">{{ implode(', ', array_unique(array_filter($currentCustomers))) }}</td>
                                        </tr>
                                    @endif

                                    @php
                                        $currentMaterialCode = $material->material_code;
                                        $currentMaterialName = $material->material_name;
                                        $currentMaterialMeasure = $material->unit_of_measure;
                                        $currentCustomers = [$material->customer];
                                        $monthlyTotals = array_fill(0, $colCount, 0);
                                    @endphp
                                @else
                                    @php
                                        if (!empty($material->customer)) {
                                            $currentCustomers[] = $material->customer;
                                        }
                                    @endphp
                                @endif

                                @foreach ($qforecast[$key] ?? [] as $index => $value)
                                    @php
                                        $calculation = $value * $material->quantity_material;
                                        $monthlyTotals[$index] = ($monthlyTotals[$index] ?? 0) + $calculation;
                                    @endphp
                                @endforeach
                            @endforeach

                            <!-- Print final row for the last material code -->
                            @if ($currentMaterialCode !== null)
                                <tr>
                                    <td class="table-bordered text-center">{{ $rowNumber++ }}</td>
                                    <td class="table-bordered font-medium">{{ $currentMaterialCode }}</td>
                                    <td class="table-bordered">{{ $currentMaterialName }}</td>
                                    <td class="table-bordered text-center">{{ $currentMaterialMeasure }}</td>
                                    @foreach ($monthlyTotals as $monthlyTotal)
                                        <td class="table-bordered text-right font-medium">
                                            {{ number_format($monthlyTotal, 2) }}
                                        </td>
                                    @endforeach
                                    <td class="table-bordered text-right font-bold">
                                        {{ number_format(array_sum($monthlyTotals), 2) }}
                                    </td>
                                    <td class="table-bordered font-medium">{{ implode(', ', array_unique(array_filter($currentCustomers))) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12 text-slate-500">
                    No forecast materials found for this vendor.
                </div>
            @endif
        </section>
    </div>
@endsection
