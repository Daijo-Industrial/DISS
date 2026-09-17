<?php

namespace Tests\Feature\Invoice;

use App\Infrastructure\Persistence\Eloquent\Models\ApprovalRequest;
use App\Livewire\Invoice\InvoiceIndex;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private PurchaseOrderCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->category = PurchaseOrderCategory::create(['name' => 'General']);
    }

    private function createPO(int $poNumber, string $vendor = 'Vendor Standard', string $currency = 'IDR', float $total = 1000000): PurchaseOrder
    {
        return PurchaseOrder::create([
            'po_number' => $poNumber,
            'creator_id' => $this->user->id,
            'purchase_order_category_id' => $this->category->id,
            'vendor_name' => $vendor,
            'filename' => "po_{$poNumber}.pdf",
            'currency' => $currency,
            'total' => $total,
        ]);
    }

    public function test_it_renders_invoice_index_component()
    {
        $po = $this->createPO(1000);

        Invoice::create([
            'purchase_order_id' => $po->id,
            'invoice_number' => 'INV-TEST-001',
            'invoice_date' => now(),
            'total' => 1000000,
            'total_currency' => 'IDR',
        ]);

        Livewire::test(InvoiceIndex::class)
            ->assertStatus(200)
            ->assertSee('INV-TEST-001')
            ->assertSee('All Invoices');
    }

    public function test_it_filters_invoices_by_po_pending_approval_state()
    {
        // PO 1: Pending Approval (IN_REVIEW)
        $poPending = $this->createPO(1001, 'Vendor Pending', 'IDR', 2500000);
        ApprovalRequest::create([
            'approvable_type' => PurchaseOrder::class,
            'approvable_id' => $poPending->id,
            'status' => 'IN_REVIEW',
            'submitted_at' => now()->subDays(2),
        ]);
        Invoice::create([
            'purchase_order_id' => $poPending->id,
            'invoice_number' => 'INV-REVIEW-101',
            'invoice_date' => now(),
            'total' => 2500000,
            'total_currency' => 'IDR',
        ]);

        // PO 2: Approved
        $poApproved = $this->createPO(1002, 'Vendor Approved', 'IDR', 1500000);
        ApprovalRequest::create([
            'approvable_type' => PurchaseOrder::class,
            'approvable_id' => $poApproved->id,
            'status' => 'APPROVED',
            'submitted_at' => now()->subDays(5),
        ]);
        Invoice::create([
            'purchase_order_id' => $poApproved->id,
            'invoice_number' => 'INV-APPROVED-202',
            'invoice_date' => now(),
            'total' => 1500000,
            'total_currency' => 'IDR',
        ]);

        // Filter by IN_REVIEW (Pending Approval)
        Livewire::test(InvoiceIndex::class)
            ->set('poStatusFilter', 'IN_REVIEW')
            ->assertSee('INV-REVIEW-101')
            ->assertSee('1001')
            ->assertDontSee('INV-APPROVED-202')
            ->assertDontSee('1002');

        // Filter by APPROVED
        Livewire::test(InvoiceIndex::class)
            ->set('poStatusFilter', 'APPROVED')
            ->assertSee('INV-APPROVED-202')
            ->assertSee('1002')
            ->assertDontSee('INV-REVIEW-101')
            ->assertDontSee('1001');
    }

    public function test_it_filters_by_stat_card_presets()
    {
        // PO In Review
        $poPending = $this->createPO(2001, 'Vendor Stat', 'IDR', 500000);
        ApprovalRequest::create([
            'approvable_type' => PurchaseOrder::class,
            'approvable_id' => $poPending->id,
            'status' => 'IN_REVIEW',
            'submitted_at' => now(),
        ]);
        Invoice::create([
            'purchase_order_id' => $poPending->id,
            'invoice_number' => 'INV-STAT-PENDING',
            'invoice_date' => now(),
            'total' => 500000,
            'total_currency' => 'IDR',
        ]);

        // Unpaid Invoice
        $poUnpaid = $this->createPO(2002);
        Invoice::create([
            'purchase_order_id' => $poUnpaid->id,
            'invoice_number' => 'INV-UNPAID-ONLY',
            'invoice_date' => now(),
            'payment_date' => null,
            'total' => 300000,
            'total_currency' => 'IDR',
        ]);

        // Paid Invoice
        $poPaid = $this->createPO(2003);
        Invoice::create([
            'purchase_order_id' => $poPaid->id,
            'invoice_number' => 'INV-PAID-ONLY',
            'invoice_date' => now(),
            'payment_date' => now(),
            'total' => 400000,
            'total_currency' => 'IDR',
        ]);

        // Test filterByStat('pending_approval')
        Livewire::test(InvoiceIndex::class)
            ->call('filterByStat', 'pending_approval')
            ->assertSet('poStatusFilter', 'IN_REVIEW')
            ->assertSee('INV-STAT-PENDING')
            ->assertDontSee('INV-PAID-ONLY');

        // Test filterByStat('unpaid')
        Livewire::test(InvoiceIndex::class)
            ->call('filterByStat', 'unpaid')
            ->assertSet('paymentStatusFilter', 'unpaid')
            ->assertSee('INV-UNPAID-ONLY')
            ->assertDontSee('INV-PAID-ONLY');
    }

    public function test_it_filters_by_payment_status()
    {
        $po1 = $this->createPO(3001);
        Invoice::create([
            'purchase_order_id' => $po1->id,
            'invoice_number' => 'INV-PAID',
            'invoice_date' => now(),
            'payment_date' => now(),
            'total' => 100000,
            'total_currency' => 'IDR',
        ]);

        $po2 = $this->createPO(3002);
        Invoice::create([
            'purchase_order_id' => $po2->id,
            'invoice_number' => 'INV-UNPAID',
            'invoice_date' => now(),
            'payment_date' => null,
            'total' => 200000,
            'total_currency' => 'IDR',
        ]);

        Livewire::test(InvoiceIndex::class)
            ->set('paymentStatusFilter', 'paid')
            ->assertSee('INV-PAID')
            ->assertDontSee('INV-UNPAID');

        Livewire::test(InvoiceIndex::class)
            ->set('paymentStatusFilter', 'unpaid')
            ->assertSee('INV-UNPAID')
            ->assertDontSee('INV-PAID');
    }

    public function test_it_filters_by_vendor_and_currency()
    {
        $poA = $this->createPO(4001, 'PT Vendor Alpha', 'IDR', 100000);
        Invoice::create([
            'purchase_order_id' => $poA->id,
            'invoice_number' => 'INV-ALPHA',
            'invoice_date' => now(),
            'total' => 100000,
            'total_currency' => 'IDR',
        ]);

        $poB = $this->createPO(4002, 'PT Vendor Beta', 'USD', 500);
        Invoice::create([
            'purchase_order_id' => $poB->id,
            'invoice_number' => 'INV-BETA',
            'invoice_date' => now(),
            'total' => 500,
            'total_currency' => 'USD',
        ]);

        Livewire::test(InvoiceIndex::class)
            ->set('vendorFilter', 'PT Vendor Alpha')
            ->assertSee('INV-ALPHA')
            ->assertDontSee('INV-BETA');

        Livewire::test(InvoiceIndex::class)
            ->set('currencyFilter', 'USD')
            ->assertSee('INV-BETA')
            ->assertDontSee('INV-ALPHA');
    }

    public function test_it_clears_filters_individually_and_in_bulk()
    {
        Livewire::test(InvoiceIndex::class)
            ->set('search', 'TestSearch')
            ->set('poStatusFilter', 'IN_REVIEW')
            ->set('paymentStatusFilter', 'unpaid')
            ->call('clearFilter', 'poStatusFilter')
            ->assertSet('poStatusFilter', '')
            ->assertSet('paymentStatusFilter', 'unpaid')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('paymentStatusFilter', '')
            ->assertSet('poStatusFilter', '');
    }
}
