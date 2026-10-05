<?php

namespace Tests\Feature\Forecast;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ForemindPrintViewsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::first() ?? User::factory()->create();

        // Seed test data in testing database
        DB::table('forecast_material_predictions')->where('vendor_code', 'VML0000223')->delete();
        DB::table('forecast_material_predictions')->insert([
            [
                'material_code' => '429-400271',
                'material_name' => 'FOAM STICKER',
                'customer' => 'FR-NAD',
                'item_no' => 'D0B46200',
                'unit_of_measure' => 'PCS',
                'quantity_material' => 1,
                'vendor_code' => 'VML0000223',
                'vendor_name' => 'ASIA HODA INDONESIA PT.',
                'quantity_forecast' => json_encode(['2026-10' => 756]),
                'months' => json_encode(['2026-10' => 756]),
            ],
            [
                'material_code' => '429-400271',
                'material_name' => 'FOAM STICKER',
                'customer' => 'FR-SHAD',
                'item_no' => 'D0B46200.',
                'unit_of_measure' => 'PCS',
                'quantity_material' => 1,
                'vendor_code' => 'VML0000223',
                'vendor_name' => 'ASIA HODA INDONESIA PT.',
                'quantity_forecast' => json_encode(['2026-10' => 150]),
                'months' => json_encode(['2026-10' => 150]),
            ],
        ]);

        DB::table('purchasing_contacts')->where('vendor_code', 'VML0000223')->delete();
        DB::table('purchasing_contacts')->insert([
            'vendor_code' => 'VML0000223',
            'vendor_name' => 'ASIA HODA INDONESIA PT.',
            'p_member' => 'ANNA',
            'persontocontact' => 'Mr. Ferry',
        ]);
    }

    public function test_print_internal_renders_successfully_with_vendor_info(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/foremind-detail/print?vendor_code=VML0000223');

        $response->assertStatus(200);
        $response->assertSee('Forecast Material Prediction (Internal)');
        $response->assertSee('VML0000223');
        $response->assertSee('ASIA HODA INDONESIA PT.');
        $response->assertSee('Print Document');
        $response->assertSee('Export to Excel');
    }

    public function test_print_customer_renders_successfully_with_customers_and_formatting(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/foremind-detail/printCustomer?vendor_code=VML0000223');

        $response->assertStatus(200);
        $response->assertSee('Forecast Material Prediction (Customer)');
        $response->assertSee('VML0000223');
        $response->assertSee('ASIA HODA INDONESIA PT.');
        $response->assertSee('Print Document');
        $response->assertSee('Export to Excel');
        $response->assertSee('FR-NAD, FR-SHAD');
    }

    public function test_print_redirects_back_when_vendor_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/foremind-detail/print?vendor_code=NONEXISTENT_VENDOR');

        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    public function test_export_excel_internal_rounds_decimals_to_two(): void
    {
        // Add a forecast item with repeating decimal calculation
        DB::table('forecast_material_predictions')->insert([
            'material_code' => 'TEST-DECIMAL-1',
            'material_name' => 'DECIMAL MATERIAL',
            'customer' => 'TEST-CUST',
            'item_no' => 'ITEM001',
            'unit_of_measure' => 'PCS',
            'quantity_material' => 0.33333,
            'vendor_code' => 'VML0000223',
            'vendor_name' => 'ASIA HODA INDONESIA PT.',
            'quantity_forecast' => json_encode(['2026-10' => 100]),
            'months' => json_encode(['2026-10' => 33.333]),
        ]);

        $response = $this->actingAs($this->user)
            ->get('/foremind-detail/print/excel/VML0000223');

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');

        // Test the blade view directly for rounded decimal output
        $materials = DB::table('forecast_material_predictions as fmp')
            ->leftJoin('sap_fct_inventory_fgs as inv', 'fmp.item_no', '=', 'inv.item_code')
            ->where('fmp.vendor_code', 'VML0000223')
            ->select('fmp.*', 'inv.item_name as item_desc')
            ->orderBy('fmp.material_code')
            ->orderBy('fmp.item_no')
            ->get();

        $view = view('purchasing.foremind_detail_print_excel', [
            'monthm' => [['2026-10']],
            'materials' => $materials,
            'values' => [[33.333]],
            'mon' => ['2026-10'],
            'vendorCode' => 'VML0000223',
            'qforecast' => array_map(fn ($m) => array_values(json_decode($m->quantity_forecast, true) ?? []), $materials->all()),
            'vendorName' => 'ASIA HODA INDONESIA PT.',
        ])->render();

        // 100 * 0.33333 = 33.333 -> formatted to 33.33
        $this->assertStringContainsString('<b>33.33</b>', $view);
        $this->assertStringNotContainsString('33.333', $view);
    }

    public function test_export_excel_customer_rounds_decimals_to_two(): void
    {
        DB::table('forecast_material_predictions')->insert([
            'material_code' => 'TEST-DECIMAL-2',
            'material_name' => 'DECIMAL MATERIAL 2',
            'customer' => 'TEST-CUST',
            'item_no' => 'ITEM002',
            'unit_of_measure' => 'PCS',
            'quantity_material' => 0.33333,
            'vendor_code' => 'VML0000223',
            'vendor_name' => 'ASIA HODA INDONESIA PT.',
            'quantity_forecast' => json_encode(['2026-10' => 100]),
            'months' => json_encode(['2026-10' => 33.333]),
        ]);

        $response = $this->actingAs($this->user)
            ->get('/foremind-detail/print/customer/excel/VML0000223');

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');

        $materials = DB::table('forecast_material_predictions')
            ->where('vendor_code', 'VML0000223')
            ->orderBy('material_code')
            ->orderBy('item_no')
            ->get();

        $view = view('purchasing.foremind_detail_print_customer_excel', [
            'monthm' => [['2026-10']],
            'materials' => $materials,
            'values' => [[33.333]],
            'mon' => ['2026-10'],
            'vendorCode' => 'VML0000223',
            'qforecast' => array_map(fn ($m) => array_values(json_decode($m->quantity_forecast, true) ?? []), $materials->all()),
            'vendorName' => 'ASIA HODA INDONESIA PT.',
            'contact' => (object)['persontocontact' => 'Mr. Ferry', 'p_member' => 'ANNA'],
        ])->render();

        // Monthly total should be formatted to 33.33
        $this->assertStringContainsString('<strong>33.33</strong>', $view);
        $this->assertStringNotContainsString('33.333', $view);
    }
}
