<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forecast</title>
    <style>
        /* Table styles */
        table {
            border-collapse: collapse;
            width: 100%;
        }

        /* Table header styles */
        th {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
            background-color: #f2f2f2;
        }

        /* Table data styles */
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
    </style>

</head>

<body>
    @if (!empty($materials))
        <div class="row">
            <div class="col-12 table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <td style="vertical-align: middle; height:60px; font-size: 18px;">Nama Vendor</td>
                            <td colspan="{{ 5 + count($mon) }}" style="vertical-align: middle; height:60px; font-size: 18px;">
                                {{ $vendorName }}</td>
                        </tr>
                        <tr>
                            <td style="vertical-align: middle; height:60px; font-size: 18px;">Date</td>
                            <td colspan="{{ 5 + count($mon) }}" style="vertical-align: middle; height:60px; font-size: 18px;">
                                {{ now()->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}</td>
                        </tr>
                        <tr>
                            <td style="vertical-align: middle; height:60px; font-size: 18px;">ATT</td>
                            <td colspan="{{ 5 + count($mon) }}" style="vertical-align: middle; height:60px; font-size: 18px;">
                                {{ $contact->persontocontact ?? "-" }} </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: middle; height:60px; font-size: 18px;">FR </td>
                            <td colspan="{{ 5 + count($mon) }}" style="vertical-align: middle; height:60px; font-size: 18px;">
                                {{ $contact->p_member ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td colspan="{{ 6 + count($mon) }}" align="center" style="vertical-align: middle; font-size: 30px;">
                                Forecast
                                Report</td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <th align="center" style="vertical-align: middle; height:60px; font-size: 18px;">No</th>
                            <th align="center" style="vertical-align: middle; font-size: 18px;">Material Code</th>
                            <th align="center" style="vertical-align: middle; font-size: 18px;">Material Name</th>
                            <th align="center" style="vertical-align: middle; font-size: 18px;">Unit Measure</th>
                            @foreach ($mon as $month)
                                <th align="center" style="vertical-align: middle; font-size: 18px;">
                                    {{ \Carbon\Carbon::parse($month)->format('M-Y') }}</th>
                            @endforeach
                            <th align="center" style="vertical-align: middle; font-size: 18px;">Total</th>
                            <th align="center" style="vertical-align: middle; font-size: 18px;">Customer</th>
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
                                        <td class="table-bordered" align="center" style="vertical-align: middle;">{{ $rowNumber++ }}</td>
                                        <td class="table-bordered" align="left" style="vertical-align: middle;">{{ $currentMaterialCode }}</td>
                                        <td class="table-bordered" align="left" style="vertical-align: middle;">{{ $currentMaterialName }}</td>
                                        <td class="table-bordered" align="center" style="vertical-align: middle;">{{ $currentMaterialMeasure }}</td>
                                        @foreach ($monthlyTotals as $monthlyTotal)
                                            <td class="table-bordered" align="right" style="vertical-align: middle; mso-number-format:'\#\,\#\#0\.00';">{{ round($monthlyTotal, 2) }}</td>
                                        @endforeach
                                        <td class="table-bordered" align="right" style="vertical-align: middle; font-weight: bold; mso-number-format:'\#\,\#\#0\.00';">{{ round(array_sum($monthlyTotals), 2) }}</td>
                                        <td class="table-bordered" align="left" style="vertical-align: middle;">{{ implode(', ', array_unique(array_filter($currentCustomers))) }}</td>
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

                        <!-- Print the final row for the last material -->
                        @if ($currentMaterialCode !== null)
                            <tr>
                                <td class="table-bordered" align="center" style="vertical-align: middle;">{{ $rowNumber++ }}</td>
                                <td class="table-bordered" align="left" style="vertical-align: middle;">{{ $currentMaterialCode }}</td>
                                <td class="table-bordered" align="left" style="vertical-align: middle;">{{ $currentMaterialName }}</td>
                                <td class="table-bordered" align="center" style="vertical-align: middle;">{{ $currentMaterialMeasure }}</td>
                                @foreach ($monthlyTotals as $monthlyTotal)
                                    <td class="table-bordered" align="right" style="vertical-align: middle; mso-number-format:'\#\,\#\#0\.00';">{{ round($monthlyTotal, 2) }}</td>
                                @endforeach
                                <td class="table-bordered" align="right" style="vertical-align: middle; font-weight: bold; mso-number-format:'\#\,\#\#0\.00';">{{ round(array_sum($monthlyTotals), 2) }}</td>
                                <td class="table-bordered" align="left" style="vertical-align: middle;">{{ implode(', ', array_unique(array_filter($currentCustomers))) }}</td>
                            </tr>
                        @endif

                        <tr>
                            <td colspan="{{ 6 + count($mon) }}" style="text-align: left; font-style: italic;">
                                ** Daijo tidak bertanggung jawab atas perubahan Qty Forecast, sewaktu - waktu Qty bisa berubah sesuai permintaan customer kami
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</body>

</html>
