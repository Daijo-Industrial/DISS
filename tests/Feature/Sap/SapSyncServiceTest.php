<?php

namespace Tests\Feature\Sap;

use App\Services\Sap\SapSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SapSyncServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected SapSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SapSyncService::class);
    }

    public function test_sync_inventory_mtr_union_preserves_multiple_vendor_codes_for_same_vendor_name(): void
    {
        // Setup purchasing_contacts with multiple codes for the same vendor name
        DB::table('purchasing_contacts')->where('vendor_name', 'TEST MULTI VENDOR')->delete();
        DB::table('purchasing_contacts')->insert([
            ['vendor_code' => 'VML0000991', 'vendor_name' => 'TEST MULTI VENDOR', 'p_member' => 'TEST1'],
            ['vendor_code' => 'KML0000992', 'vendor_name' => 'TEST MULTI VENDOR', 'p_member' => 'TEST2'],
        ]);

        // Mock SAP API responses
        Http::fake([
            '*auth/token*' => Http::response(['token' => 'fake-jwt-token'], 200),
            '*/sap_fct_inventory_mtr/list*' => Http::response([
                'data' => [
                    [
                        'FGCode' => 'FG-TEST-001',
                        'MaterialCode' => 'MTR-001',
                        'MaterialName' => 'Material Direct',
                        'QuantityBOM' => 2.5,
                        'InStock' => 100,
                        'ItemGroup' => 104,
                        'VendorCode' => 'VML0000991',
                        'VendorName' => 'TEST MULTI VENDOR',
                        'UOM' => 'PCS',
                    ],
                    [
                        'FGCode' => 'FG-TEST-002',
                        'MaterialCode' => 'MTR-002',
                        'MaterialName' => 'Material Consignment',
                        'QuantityBOM' => 1.0,
                        'InStock' => 50,
                        'ItemGroup' => 104,
                        'VendorCode' => 'KML0000992',
                        'VendorName' => 'TEST MULTI VENDOR',
                        'UOM' => 'PCS',
                    ],
                ]
            ], 200),
            '*/sap_fct_inventory_mtr_semi/list*' => Http::response(['data' => []], 200),
            '*/sap_fct_inventory_mtr_semi_wip/list*' => Http::response(['data' => []], 200),
            '*/sap_fct_inventory_mtr_semi_semi_wip/list*' => Http::response(['data' => []], 200),
        ]);

        $res = $this->service->syncInventoryMtrUnion('2026-09-01');

        $this->assertTrue($res['success']);

        // Assert both vendor codes exist in sap_fct_inventory_mtr
        $vmlRecord = DB::table('sap_fct_inventory_mtr')
            ->where('material_code', 'MTR-001')
            ->first();
        $this->assertNotNull($vmlRecord);
        $this->assertEquals('VML0000991', $vmlRecord->vendor_code);
        $this->assertEquals('TEST MULTI VENDOR', $vmlRecord->vendor_name);

        $kmlRecord = DB::table('sap_fct_inventory_mtr')
            ->where('material_code', 'MTR-002')
            ->first();
        $this->assertNotNull($kmlRecord);
        $this->assertEquals('KML0000992', $kmlRecord->vendor_code);
        $this->assertEquals('TEST MULTI VENDOR', $kmlRecord->vendor_name);
    }

    public function test_sync_inventory_mtr_union_falls_back_only_when_unambiguous(): void
    {
        // Setup unambiguous single contact
        DB::table('purchasing_contacts')->where('vendor_name', 'SINGLE CODE VENDOR')->delete();
        DB::table('purchasing_contacts')->insert([
            ['vendor_code' => 'VML0000888', 'vendor_name' => 'SINGLE CODE VENDOR', 'p_member' => 'SOLO'],
        ]);

        Http::fake([
            '*auth/token*' => Http::response(['token' => 'fake-jwt-token'], 200),
            '*/sap_fct_inventory_mtr/list*' => Http::response([
                'data' => [
                    [
                        'FGCode' => 'FG-SOLO',
                        'MaterialCode' => 'MTR-SOLO',
                        'MaterialName' => 'Solo Material',
                        'QuantityBOM' => 1,
                        'InStock' => 10,
                        'ItemGroup' => 104,
                        'VendorCode' => '', // Empty from SAP
                        'VendorName' => 'SINGLE CODE VENDOR',
                        'UOM' => 'PCS',
                    ]
                ]
            ], 200),
            '*/sap_fct_inventory_mtr_semi/list*' => Http::response(['data' => []], 200),
            '*/sap_fct_inventory_mtr_semi_wip/list*' => Http::response(['data' => []], 200),
            '*/sap_fct_inventory_mtr_semi_semi_wip/list*' => Http::response(['data' => []], 200),
        ]);

        $res = $this->service->syncInventoryMtrUnion('2026-09-01');

        $this->assertTrue($res['success']);

        $record = DB::table('sap_fct_inventory_mtr')
            ->where('material_code', 'MTR-SOLO')
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals('VML0000888', $record->vendor_code);
    }

    public function test_process_bom_wip_union_executes_successfully(): void
    {
        // Seed first level
        DB::table('sap_fct_bom_wip_first')->delete();
        DB::table('sap_fct_bom_wip_first')->insert([
            'fg_code' => 'FG-UNION-001',
            'semi_first' => 'SEMI-001',
            'semi_second' => null,
            'semi_third' => null,
            'level' => 1,
            'bom_quantity' => 1.5,
            'item_group' => 103,
        ]);

        $res = $this->service->processBomWipUnion();

        $this->assertTrue($res['success']);
        $this->assertGreaterThanOrEqual(1, DB::table('sap_fct_bom_wip')->count());
        $this->assertGreaterThanOrEqual(1, DB::table('sap_fct_bom_wip_fgcode')->where('FinishG_Code', 'FG-UNION-001')->count());
    }
}
