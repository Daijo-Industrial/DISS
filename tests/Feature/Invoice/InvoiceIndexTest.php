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
use Spatie\Permission\Models\Role;
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

    public function test_it_calculates_and_displays_category_sums_in_kpi_cards()
    {
        // PO In Review: 2,500,000 IDR
        $poPending = $this->createPO(5001, 'Vendor A', 'IDR', 2500000);
        ApprovalRequest::create([
            'approvable_type' => PurchaseOrder::class,
            'approvable_id' => $poPending->id,
            'status' => 'IN_REVIEW',
            'submitted_at' => now(),
        ]);
        Invoice::create([
            'purchase_order_id' => $poPending->id,
            'invoice_number' => 'INV-SUM-REVIEW',
            'invoice_date' => now(),
            'payment_date' => now(), // paid, but PO in review
            'total' => 2500000,
            'total_currency' => 'IDR',
        ]);

        // PO Approved: 1,500,000 IDR
        $poApproved = $this->createPO(5002, 'Vendor B', 'IDR', 1500000);
        ApprovalRequest::create([
            'approvable_type' => PurchaseOrder::class,
            'approvable_id' => $poApproved->id,
            'status' => 'APPROVED',
            'submitted_at' => now(),
        ]);
        Invoice::create([
            'purchase_order_id' => $poApproved->id,
            'invoice_number' => 'INV-SUM-APPROVED',
            'invoice_date' => now(),
            'payment_date' => now(), // paid
            'total' => 1500000,
            'total_currency' => 'IDR',
        ]);

        // Past Due Unpaid Invoice: 750,000 IDR
        $poUnpaid = $this->createPO(5003, 'Vendor C', 'IDR', 750000);
        Invoice::create([
            'purchase_order_id' => $poUnpaid->id,
            'invoice_number' => 'INV-SUM-UNPAID',
            'invoice_date' => now()->subDays(10),
            'payment_date' => now()->subDays(3), // Past target date
            'paid_at' => null, // UNPAID -> Past due!
            'total' => 750000,
            'total_currency' => 'IDR',
        ]);

        // Settled Historical Invoice: 500,000 IDR (target was in past, but paid)
        $poSettled = $this->createPO(5004, 'Vendor D', 'IDR', 500000);
        Invoice::create([
            'purchase_order_id' => $poSettled->id,
            'invoice_number' => 'INV-SUM-SETTLED',
            'invoice_date' => now()->subDays(20),
            'payment_date' => now()->subDays(15),
            'paid_at' => now()->subDays(14), // Paid! Not past due
            'total' => 500000,
            'total_currency' => 'IDR',
        ]);

        $component = Livewire::test(InvoiceIndex::class);

        // Verify stats data property
        $stats = $component->get('stats');
        $this->assertEquals(2500000, (float) $stats['pending_approval_sum']);
        $this->assertEquals(1500000, (float) $stats['po_approved_sum']);
        $this->assertEquals(750000, (float) $stats['past_due_sum']); // Only the unpaid one!

        // Verify formatted output rendered in KPI cards
        $component->assertSee('2,500,000')
            ->assertSee('1,500,000')
            ->assertSee('750,000')
            ->assertDontSee('Financial Category Breakdown');
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

        // Past Due Unpaid Invoice
        $poPastDue = $this->createPO(2002);
        Invoice::create([
            'purchase_order_id' => $poPastDue->id,
            'invoice_number' => 'INV-PASTDUE-ONLY',
            'invoice_date' => now()->subDays(10),
            'payment_date' => now()->subDays(2),
            'paid_at' => null,
            'total' => 300000,
            'total_currency' => 'IDR',
        ]);

        // Settled Paid Invoice
        $poPaid = $this->createPO(2003);
        Invoice::create([
            'purchase_order_id' => $poUpcoming->id,
            'invoice_number' => 'INV-UPCOMING-ONLY',
            'invoice_date' => now(),
            'payment_date' => now(),
            'paid_at' => now(),
            'total' => 400000,
            'total_currency' => 'IDR',
        ]);

        // Test filterByStat('pending_approval')
        Livewire::test(InvoiceIndex::class)
            ->call('filterByStat', 'pending_approval')
            ->assertSet('poStatusFilter', 'IN_REVIEW')
            ->assertSee('INV-STAT-PENDING')
            ->assertDontSee('INV-UPCOMING-ONLY');

        // Test filterByStat('past_due')
        Livewire::test(InvoiceIndex::class)
            ->call('filterByStat', 'past_due')
            ->assertSet('paymentStatusFilter', 'past_due')
            ->assertSet('settlementFilter', 'unpaid')
            ->assertSee('INV-PASTDUE-ONLY')
            ->assertDontSee('INV-PAID-ONLY');
    }

    public function test_it_filters_by_payment_status_and_settlement()
    {
        $po1 = $this->createPO(3001);
        Invoice::create([
            'purchase_order_id' => $po1->id,
            'invoice_number' => 'INV-PAID',
            'invoice_date' => now(),
            'payment_date' => now(),
            'paid_at' => now(),
            'total' => 100000,
            'total_currency' => 'IDR',
        ]);

        $po2 = $this->createPO(3002);
        Invoice::create([
            'purchase_order_id' => $po2->id,
            'invoice_number' => 'INV-UNPAID',
            'invoice_date' => now(),
            'payment_date' => null,
            'paid_at' => null,
            'total' => 200000,
            'total_currency' => 'IDR',
        ]);

        $po3 = $this->createPO(3003);
        Invoice::create([
            'purchase_order_id' => $po3->id,
            'invoice_number' => 'INV-PAST-DUE',
            'invoice_date' => now()->subDays(10),
            'payment_date' => now()->subDays(2),
            'paid_at' => null,
            'total' => 300000,
            'total_currency' => 'IDR',
        ]);

        // Test settlementFilter = paid
        Livewire::test(InvoiceIndex::class)
            ->set('settlementFilter', 'paid')
            ->assertSee('INV-PAID')
            ->assertDontSee('INV-UNPAID');

        // Test settlementFilter = unpaid
        Livewire::test(InvoiceIndex::class)
            ->set('settlementFilter', 'unpaid')
            ->assertSee('INV-UNPAID')
            ->assertSee('INV-PAST-DUE')
            ->assertDontSee('INV-PAID');

        // Test paymentStatusFilter = past_due
        Livewire::test(InvoiceIndex::class)
            ->set('paymentStatusFilter', 'past_due')
            ->assertSee('INV-PAST-DUE')
            ->assertDontSee('INV-PAID')
            ->assertDontSee('INV-UNPAID');
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
            ->set('paymentStatusFilter', 'past_due')
            ->set('settlementFilter', 'unpaid')
            ->call('clearFilter', 'poStatusFilter')
            ->assertSet('poStatusFilter', '')
            ->assertSet('settlementFilter', 'unpaid')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('paymentStatusFilter', '')
            ->assertSet('settlementFilter', '')
            ->assertSet('poStatusFilter', '');
    }

    public function test_it_marks_invoices_as_paid_and_unpaid()
    {
        Role::firstOrCreate(['name' => 'accounting-admin', 'guard_name' => 'web']);
        $this->user->assignRole('accounting-admin');

        $po = $this->createPO(6001);
        $invoice = Invoice::create([
            'purchase_order_id' => $po->id,
            'invoice_number' => 'INV-TOGGLE-PAID',
            'invoice_date' => now(),
            'payment_date' => now()->addDays(5),
            'paid_at' => null,
            'total' => 1250000,
            'total_currency' => 'IDR',
        ]);

        $this->assertFalse($invoice->fresh()->is_paid);
        $this->assertNull($invoice->fresh()->paid_at);

        // Mark as paid
        Livewire::actingAs($this->user)
            ->test(InvoiceIndex::class)
            ->call('markAsPaid', $invoice->id);

        $invoice->refresh();
        $this->assertTrue($invoice->is_paid);
        $this->assertNotNull($invoice->paid_at);
        $this->assertEquals(today()->toDateString(), $invoice->paid_at->toDateString());

        // Mark as unpaid
        Livewire::actingAs($this->user)
            ->test(InvoiceIndex::class)
            ->call('markAsUnpaid', $invoice->id);

        $invoice->refresh();
        $this->assertFalse($invoice->is_paid);
        $this->assertNull($invoice->paid_at);
    }

    public function test_unauthorized_user_cannot_mark_invoice_as_paid_or_unpaid()
    {
        $po = $this->createPO(6002);
        $invoice = Invoice::create([
            'purchase_order_id' => $po->id,
            'invoice_number' => 'INV-UNAUTH-PAID',
            'invoice_date' => now(),
            'total' => 1000000,
            'total_currency' => 'IDR',
        ]);

        // Regular user without accounting-admin role
        $regularUser = User::factory()->create();

        Livewire::actingAs($regularUser)
            ->test(InvoiceIndex::class)
            ->call('markAsPaid', $invoice->id)
            ->assertForbidden();

        $invoice->update(['paid_at' => today()]);

        Livewire::actingAs($regularUser)
            ->test(InvoiceIndex::class)
            ->call('markAsUnpaid', $invoice->id)
            ->assertForbidden();
    }

    public function test_super_admin_can_mark_invoice_as_paid_and_unpaid()
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $adminUser = User::factory()->create();
        $adminUser->assignRole('super-admin');

        $po = $this->createPO(6003);
        $invoice = Invoice::create([
            'purchase_order_id' => $po->id,
            'invoice_number' => 'INV-SUPERADMIN-PAID',
            'invoice_date' => now(),
            'total' => 1000000,
            'total_currency' => 'IDR',
        ]);

        Livewire::actingAs($adminUser)
            ->test(InvoiceIndex::class)
            ->call('markAsPaid', $invoice->id);

        $this->assertTrue($invoice->fresh()->is_paid);

        Livewire::actingAs($adminUser)
            ->test(InvoiceIndex::class)
            ->call('markAsUnpaid', $invoice->id);

        $this->assertFalse($invoice->fresh()->is_paid);
    }

    public function test_it_defaults_year_filter_to_current_year_on_mount()
    {
        Livewire::test(InvoiceIndex::class)
            ->assertSet('yearFilter', (string) now()->year);
    }

    public function test_it_filters_invoices_by_year()
    {
        $currentYear = now()->year;
        $prevYear = $currentYear - 1;

        $poCurrent = $this->createPO(7001);
        $poPrev = $this->createPO(7002);

        $invCurrent = Invoice::create([
            'purchase_order_id' => $poCurrent->id,
            'invoice_number' => 'INV-YEAR-CURR',
            'invoice_date' => now(),
            'total' => 1000000,
            'total_currency' => 'IDR',
        ]);

        $invPrev = Invoice::create([
            'purchase_order_id' => $poPrev->id,
            'invoice_number' => 'INV-YEAR-PREV',
            'invoice_date' => now()->subYear(),
            'total' => 2000000,
            'total_currency' => 'IDR',
        ]);

        // Default: current year only
        Livewire::test(InvoiceIndex::class)
            ->assertSee('INV-YEAR-CURR')
            ->assertDontSee('INV-YEAR-PREV')
            // Set to previous year
            ->set('yearFilter', (string) $prevYear)
            ->assertSee('INV-YEAR-PREV')
            ->assertDontSee('INV-YEAR-CURR')
            // Set to all years
            ->set('yearFilter', 'all')
            ->assertSee('INV-YEAR-CURR')
            ->assertSee('INV-YEAR-PREV');
    }

    public function test_it_scopes_stats_by_selected_year()
    {
        $currentYear = now()->year;
        $prevYear = $currentYear - 1;

        $poCurrent = $this->createPO(8001);
        $poPrev = $this->createPO(8002);

        Invoice::create([
            'purchase_order_id' => $poCurrent->id,
            'invoice_number' => 'INV-STAT-CURR',
            'invoice_date' => now(),
            'total' => 1000000,
            'total_currency' => 'IDR',
        ]);

        Invoice::create([
            'purchase_order_id' => $poPrev->id,
            'invoice_number' => 'INV-STAT-PREV',
            'invoice_date' => now()->subYear(),
            'total' => 500000,
            'total_currency' => 'IDR',
        ]);

        // Default: current year only in stats
        $component = Livewire::test(InvoiceIndex::class);
        $this->assertEquals(1, $component->get('stats')['total_count']);
        $this->assertEquals(1000000, $component->get('stats')['total_amount_idr']);

        // Previous year in stats
        $component->set('yearFilter', (string) $prevYear);
        $this->assertEquals(1, $component->get('stats')['total_count']);
        $this->assertEquals(500000, $component->get('stats')['total_amount_idr']);

        // All years in stats
        $component->set('yearFilter', 'all');
        $this->assertEquals(2, $component->get('stats')['total_count']);
        $this->assertEquals(1500000, $component->get('stats')['total_amount_idr']);
    }

    public function test_clear_filter_and_clear_filters_for_year_in_invoice_index()
    {
        Livewire::test(InvoiceIndex::class)
            // clearFilter('yearFilter') sets it to 'all'
            ->call('clearFilter', 'yearFilter')
            ->assertSet('yearFilter', 'all')
            // clearFilters() resets it to current year
            ->call('clearFilters')
            ->assertSet('yearFilter', (string) now()->year);
    }

    public function test_it_opens_payment_modal_with_default_date_now()
    {
        Role::firstOrCreate(['name' => 'accounting-admin', 'guard_name' => 'web']);
        $this->user->assignRole('accounting-admin');

        $po = $this->createPO(9001);
        $invoice = Invoice::create([
            'purchase_order_id' => $po->id,
            'invoice_number' => 'INV-MODAL-001',
            'invoice_date' => now(),
            'total' => 1000000,
            'total_currency' => 'IDR',
        ]);

        Livewire::actingAs($this->user)
            ->test(InvoiceIndex::class)
            ->call('openPaymentModal', $invoice->id)
            ->assertSet('showPaymentModal', true)
            ->assertSet('settlingInvoiceId', $invoice->id)
            ->assertSet('settlementDate', now()->format('Y-m-d'))
            ->call('closePaymentModal')
            ->assertSet('showPaymentModal', false)
            ->assertSet('settlingInvoiceId', null);
    }

    public function test_it_confirms_payment_with_selected_custom_date()
    {
        Role::firstOrCreate(['name' => 'accounting-admin', 'guard_name' => 'web']);
        $this->user->assignRole('accounting-admin');

        $po = $this->createPO(9002);
        $invoice = Invoice::create([
            'purchase_order_id' => $po->id,
            'invoice_number' => 'INV-MODAL-002',
            'invoice_date' => now(),
            'total' => 2000000,
            'total_currency' => 'IDR',
        ]);

        Livewire::actingAs($this->user)
            ->test(InvoiceIndex::class)
            ->call('openPaymentModal', $invoice->id)
            ->set('settlementDate', '2026-09-12')
            ->call('confirmPayment')
            ->assertSet('showPaymentModal', false)
            ->assertDispatched('banner-message');

        $invoice->refresh();
        $this->assertTrue($invoice->is_paid);
        $this->assertEquals('2026-09-12', $invoice->paid_at->format('Y-m-d'));
    }

    public function test_unauthorized_user_cannot_open_payment_modal()
    {
        $po = $this->createPO(9003);
        $invoice = Invoice::create([
            'purchase_order_id' => $po->id,
            'invoice_number' => 'INV-MODAL-UNAUTH',
            'invoice_date' => now(),
            'total' => 1000000,
            'total_currency' => 'IDR',
        ]);

        $regularUser = User::factory()->create();

        Livewire::actingAs($regularUser)
            ->test(InvoiceIndex::class)
            ->call('openPaymentModal', $invoice->id)
            ->assertForbidden();
    }
}
