<?php

namespace Tests\Feature;

use App\Livewire\PurchaseOrder\PurchaseOrderIndex;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderIndexInvoicingSumsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_it_calculates_filtered_invoicing_totals_for_partially_invoiced_pos()
    {
        $category = \App\Models\PurchaseOrderCategory::create(['name' => 'General']);

        // 1. Partially invoiced PO: Total 1,000,000 IDR, Invoiced 400,000 IDR (Remaining 600,000 IDR)
        $po1 = PurchaseOrder::create([
            'po_number' => 1001,
            'creator_id' => $this->user->id,
            'purchase_order_category_id' => $category->id,
            'vendor_name' => 'Vendor A',
            'filename' => 'test1.pdf',
            'currency' => 'IDR',
            'total' => 1000000,
        ]);
        Invoice::create([
            'purchase_order_id' => $po1->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now(),
            'total' => 400000,
            'total_currency' => 'IDR',
        ]);

        // 2. Fully invoiced PO: Total 500,000 IDR, Invoiced 500,000 IDR
        $po2 = PurchaseOrder::create([
            'po_number' => 1002,
            'creator_id' => $this->user->id,
            'purchase_order_category_id' => $category->id,
            'vendor_name' => 'Vendor B',
            'filename' => 'test2.pdf',
            'currency' => 'IDR',
            'total' => 500000,
        ]);
        Invoice::create([
            'purchase_order_id' => $po2->id,
            'invoice_number' => 'INV-002',
            'invoice_date' => now(),
            'total' => 500000,
            'total_currency' => 'IDR',
        ]);

        // 3. Not invoiced PO: Total 300,000 IDR, no invoices
        $po3 = PurchaseOrder::create([
            'po_number' => 1003,
            'creator_id' => $this->user->id,
            'purchase_order_category_id' => $category->id,
            'vendor_name' => 'Vendor C',
            'filename' => 'test3.pdf',
            'currency' => 'IDR',
            'total' => 300000,
        ]);

        $component = Livewire::test(PurchaseOrderIndex::class)
            ->set('invoicingFilter', 'partially_invoiced');

        $invoicingTotals = $component->get('filteredInvoicingTotals');

        // Assert only the partially invoiced PO is in the sums
        $this->assertArrayHasKey('IDR', $invoicingTotals);
        $this->assertEquals(1, $invoicingTotals['IDR']['po_count']);
        $this->assertEquals(1000000.0, $invoicingTotals['IDR']['total_valuation']);
        $this->assertEquals(400000.0, $invoicingTotals['IDR']['total_invoiced']);
        $this->assertEquals(600000.0, $invoicingTotals['IDR']['total_remaining']);
        $this->assertEquals(40.0, $invoicingTotals['IDR']['percent']);

        // Assert the UI displays the summary elements
        $component->assertSee('Partially Invoiced POs Summary');
        $component->assertSee('Partial Invoicing Sums');
        $component->assertSee(number_format(1000000, 0, ',', '.'));
        $component->assertSee(number_format(400000, 0, ',', '.'));
        $component->assertSee(number_format(600000, 0, ',', '.'));
    }

    public function test_it_filters_by_stat_partially_invoiced()
    {
        $component = Livewire::test(PurchaseOrderIndex::class)
            ->call('filterByStat', 'partially_invoiced');

        $component->assertSet('invoicingFilter', 'partially_invoiced');
    }

    public function test_it_handles_multi_currency_and_ignores_soft_deleted_invoices()
    {
        $category = \App\Models\PurchaseOrderCategory::create(['name' => 'General']);

        // USD PO: Total $2,000, Invoiced $500, soft-deleted invoice of $800
        $poUsd = PurchaseOrder::create([
            'po_number' => 2001,
            'creator_id' => $this->user->id,
            'purchase_order_category_id' => $category->id,
            'vendor_name' => 'US Vendor',
            'filename' => 'usd.pdf',
            'currency' => 'USD',
            'total' => 2000,
        ]);
        Invoice::create([
            'purchase_order_id' => $poUsd->id,
            'invoice_number' => 'INV-USD-1',
            'invoice_date' => now(),
            'total' => 500,
            'total_currency' => 'USD',
        ]);
        $deletedInv = Invoice::create([
            'purchase_order_id' => $poUsd->id,
            'invoice_number' => 'INV-USD-DEL',
            'invoice_date' => now(),
            'total' => 800,
            'total_currency' => 'USD',
        ]);
        $deletedInv->delete();

        $component = Livewire::test(PurchaseOrderIndex::class)
            ->set('invoicingFilter', 'partially_invoiced');

        $invoicingTotals = $component->get('filteredInvoicingTotals');

        $this->assertArrayHasKey('USD', $invoicingTotals);
        $this->assertEquals(1, $invoicingTotals['USD']['po_count']);
        $this->assertEquals(2000.0, $invoicingTotals['USD']['total_valuation']);
        $this->assertEquals(500.0, $invoicingTotals['USD']['total_invoiced']);
        $this->assertEquals(1500.0, $invoicingTotals['USD']['total_remaining']);
        $this->assertEquals(25.0, $invoicingTotals['USD']['percent']);
    }
}
