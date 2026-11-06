<?php

namespace Tests\Unit\Exports;

use App\Exports\ForExport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ForExportTest extends TestCase
{
    public function test_it_preserves_item_no_with_trailing_dot_when_exported_to_excel(): void
    {
        $material = (object) [
            'material_code' => 'MAT001',
            'material_name' => 'Sample Material',
            'item_no' => '31099125084.',
            'item_desc' => 'Sample Description',
            'vendor_code' => 'VML0000028',
            'unit_of_measure' => 'PCS',
            'quantity_material' => 10,
            'months' => json_encode(['2026-11' => 10]),
            'quantity_forecast' => json_encode([10]),
        ];

        $export = new ForExport(
            [['2026-11']],
            collect([$material]),
            [[10]],
            ['2026-11'],
            'VML0000028',
            [[10]],
            'VENDOR TEST'
        );

        $tempFileName = 'test_forecast_export_' . uniqid() . '.xlsx';
        Excel::store($export, $tempFileName, 'local');
        $filePath = storage_path('app/' . $tempFileName);

        $this->assertFileExists($filePath);

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Coordinate B5 is Material Code, D5 is Item No, F5 is Vendor Code
        $matCodeCell = $sheet->getCell('B5');
        $itemNoCell = $sheet->getCell('D5');
        $vendorCodeCell = $sheet->getCell('F5');

        $this->assertSame('s', $matCodeCell->getDataType());
        $this->assertSame('MAT001', $matCodeCell->getValue());

        $this->assertSame('s', $itemNoCell->getDataType());
        $this->assertSame('31099125084.', $itemNoCell->getValue());
        $this->assertSame('31099125084.', $itemNoCell->getFormattedValue());

        $this->assertSame('s', $vendorCodeCell->getDataType());
        $this->assertSame('VML0000028', $vendorCodeCell->getValue());

        @unlink($filePath);
    }
}
