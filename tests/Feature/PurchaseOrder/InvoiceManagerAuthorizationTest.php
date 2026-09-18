<?php

namespace Tests\Feature\PurchaseOrder;

use App\Livewire\PurchaseOrder\InvoiceManager;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceManagerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;
    private User $accountingAdmin;
    private User $superAdmin;
    private PurchaseOrder $purchaseOrder;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'accounting-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'purchaser', 'guard_name' => 'web']);

        $category = PurchaseOrderCategory::create(['name' => 'General Equipment']);

        $this->creator = User::factory()->create();
        $this->creator->assignRole('purchaser');

        $this->accountingAdmin = User::factory()->create();
        $this->accountingAdmin->assignRole('accounting-admin');

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');

        $this->purchaseOrder = PurchaseOrder::create([
            'po_number' => 99901,
            'creator_id' => $this->creator->id,
            'purchase_order_category_id' => $category->id,
            'vendor_name' => 'Test Industrial Vendor',
            'filename' => 'po_99901.pdf',
            'currency' => 'IDR',
            'total' => 5000000,
        ]);
    }

    public function test_unauthorized_user_cannot_toggle_paid_status()
    {
        $invoice = Invoice::create([
            'purchase_order_id' => $this->purchaseOrder->id,
            'invoice_number' => 'INV-AUTH-001',
            'invoice_date' => now(),
            'total' => 5000000,
            'total_currency' => 'IDR',
            'paid_at' => null,
        ]);

        // Even though $creator has purchaser role and owns the PO, they lack accounting-admin
        Livewire::actingAs($this->creator)
            ->test(InvoiceManager::class, ['purchaseOrderId' => $this->purchaseOrder->id])
            ->call('togglePaid', $invoice->id)
            ->assertForbidden();

        $this->assertNull($invoice->fresh()->paid_at);
    }

    public function test_accounting_admin_can_toggle_paid_status()
    {
        $invoice = Invoice::create([
            'purchase_order_id' => $this->purchaseOrder->id,
            'invoice_number' => 'INV-AUTH-002',
            'invoice_date' => now(),
            'total' => 2000000,
            'total_currency' => 'IDR',
            'paid_at' => null,
        ]);

        // Mark as paid
        Livewire::actingAs($this->accountingAdmin)
            ->test(InvoiceManager::class, ['purchaseOrderId' => $this->purchaseOrder->id])
            ->call('togglePaid', $invoice->id)
            ->assertDispatched('flash')
            ->assertDispatched('po-updated');

        $this->assertNotNull($invoice->fresh()->paid_at);
        $this->assertTrue($invoice->fresh()->is_paid);

        // Mark as unpaid
        Livewire::actingAs($this->accountingAdmin)
            ->test(InvoiceManager::class, ['purchaseOrderId' => $this->purchaseOrder->id])
            ->call('togglePaid', $invoice->id)
            ->assertDispatched('flash');

        $this->assertNull($invoice->fresh()->paid_at);
        $this->assertFalse($invoice->fresh()->is_paid);
    }

    public function test_super_admin_can_toggle_paid_status()
    {
        $invoice = Invoice::create([
            'purchase_order_id' => $this->purchaseOrder->id,
            'invoice_number' => 'INV-AUTH-003',
            'invoice_date' => now(),
            'total' => 1500000,
            'total_currency' => 'IDR',
            'paid_at' => null,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(InvoiceManager::class, ['purchaseOrderId' => $this->purchaseOrder->id])
            ->call('togglePaid', $invoice->id)
            ->assertDispatched('flash');

        $this->assertTrue($invoice->fresh()->is_paid);
    }

    public function test_unauthorized_user_save_ignores_paid_at_on_create()
    {
        // Purchaser creates an invoice and attempts to inject a paid_at date
        Livewire::actingAs($this->creator)
            ->test(InvoiceManager::class, ['purchaseOrderId' => $this->purchaseOrder->id])
            ->set('invoice_number', 'INV-TAMPER-001')
            ->set('invoice_date', '2026-09-17')
            ->set('paid_at', '2026-09-17')
            ->set('total', '1000000')
            ->set('total_currency', 'IDR')
            ->call('save')
            ->assertDispatched('flash');

        $createdInvoice = Invoice::where('invoice_number', 'INV-TAMPER-001')->first();
        $this->assertNotNull($createdInvoice);
        // paid_at MUST be null because user is not accounting-admin
        $this->assertNull($createdInvoice->paid_at);
        $this->assertFalse($createdInvoice->is_paid);
    }

    public function test_unauthorized_user_save_preserves_paid_at_on_edit()
    {
        $existingPaidAt = now()->subDays(3);
        $invoice = Invoice::create([
            'purchase_order_id' => $this->purchaseOrder->id,
            'invoice_number' => 'INV-PRESERVE-001',
            'invoice_date' => now()->subDays(5),
            'total' => 1000000,
            'total_currency' => 'IDR',
            'paid_at' => $existingPaidAt,
        ]);

        // Purchaser edits invoice total and tries to clear or modify paid_at
        Livewire::actingAs($this->creator)
            ->test(InvoiceManager::class, ['purchaseOrderId' => $this->purchaseOrder->id])
            ->call('edit', $invoice->id)
            ->set('total', '1200000')
            ->set('paid_at', '') // tries to clear paid_at
            ->call('save')
            ->assertDispatched('flash');

        $invoice->refresh();
        $this->assertEquals(1200000, $invoice->total);
        // paid_at MUST remain untouched
        $this->assertEquals($existingPaidAt->toDateString(), $invoice->paid_at->toDateString());
    }

    public function test_accounting_admin_can_set_paid_at_on_save()
    {
        Livewire::actingAs($this->accountingAdmin)
            ->test(InvoiceManager::class, ['purchaseOrderId' => $this->purchaseOrder->id])
            ->set('invoice_number', 'INV-ACCT-001')
            ->set('invoice_date', '2026-09-17')
            ->set('paid_at', '2026-09-17')
            ->set('total', '2500000')
            ->set('total_currency', 'IDR')
            ->call('save')
            ->assertDispatched('flash');

        $createdInvoice = Invoice::where('invoice_number', 'INV-ACCT-001')->first();
        $this->assertNotNull($createdInvoice);
        $this->assertNotNull($createdInvoice->paid_at);
        $this->assertEquals('2026-09-17', $createdInvoice->paid_at->format('Y-m-d'));
        $this->assertTrue($createdInvoice->is_paid);
    }

    public function test_accounting_admin_can_open_payment_modal_and_settle_with_custom_date()
    {
        $invoice = Invoice::create([
            'purchase_order_id' => $this->purchaseOrder->id,
            'invoice_number' => 'INV-MODAL-MGR-001',
            'invoice_date' => now(),
            'total' => 3000000,
            'total_currency' => 'IDR',
            'paid_at' => null,
        ]);

        Livewire::actingAs($this->accountingAdmin)
            ->test(InvoiceManager::class, ['purchaseOrderId' => $this->purchaseOrder->id])
            ->call('openPaymentModal', $invoice->id)
            ->assertSet('showPaymentModal', true)
            ->assertSet('settlingInvoiceId', $invoice->id)
            ->assertSet('settlementDate', now()->format('Y-m-d'))
            ->set('settlementDate', '2026-09-05')
            ->call('confirmPayment')
            ->assertSet('showPaymentModal', false)
            ->assertDispatched('flash');

        $invoice->refresh();
        $this->assertTrue($invoice->is_paid);
        $this->assertEquals('2026-09-05', $invoice->paid_at->format('Y-m-d'));
    }

    public function test_unauthorized_user_cannot_open_payment_modal_in_invoice_manager()
    {
        $invoice = Invoice::create([
            'purchase_order_id' => $this->purchaseOrder->id,
            'invoice_number' => 'INV-MODAL-MGR-UNAUTH',
            'invoice_date' => now(),
            'total' => 1000000,
            'total_currency' => 'IDR',
            'paid_at' => null,
        ]);

        Livewire::actingAs($this->creator)
            ->test(InvoiceManager::class, ['purchaseOrderId' => $this->purchaseOrder->id])
            ->call('openPaymentModal', $invoice->id)
            ->assertForbidden();
    }
}
