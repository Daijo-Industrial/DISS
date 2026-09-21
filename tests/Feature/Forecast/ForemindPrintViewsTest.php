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
}
