<?php

namespace Tests\Feature\PurchaseOrder;

use App\Livewire\PurchaseOrder\PurchaseOrderIndex;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderIndexYearFilterTest extends TestCase
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

    private function createPO(int $poNumber, \DateTimeInterface $createdAt, float $total = 1000000, string $currency = 'IDR'): PurchaseOrder
    {
        $po = PurchaseOrder::create([
            'po_number' => $poNumber,
            'creator_id' => $this->user->id,
            'purchase_order_category_id' => $this->category->id,
            'vendor_name' => "Vendor {$poNumber}",
            'filename' => "po_{$poNumber}.pdf",
            'currency' => $currency,
            'total' => $total,
        ]);

        $po->created_at = $createdAt;
        $po->updated_at = $createdAt;
        $po->saveQuietly();

        return $po;
    }

    public function test_it_defaults_year_filter_to_current_year_on_mount()
    {
        Livewire::test(PurchaseOrderIndex::class)
            ->assertSet('yearFilter', (string) now()->year);
    }

    public function test_it_filters_purchase_orders_by_year()
    {
        $currentYear = now()->year;
        $prevYear = $currentYear - 1;

        $this->createPO(1001, now());
        $this->createPO(1002, now()->subYear());

        // Default: only current year PO is in results
        $component = Livewire::test(PurchaseOrderIndex::class);
        $pos = $component->viewData('purchaseOrders');
        $this->assertTrue($pos->contains('po_number', 1001));
        $this->assertFalse($pos->contains('po_number', 1002));

        // Switch to previous year
        $component->set('yearFilter', (string) $prevYear);
        $pos = $component->viewData('purchaseOrders');
        $this->assertFalse($pos->contains('po_number', 1001));
        $this->assertTrue($pos->contains('po_number', 1002));

        // Switch to all years
        $component->set('yearFilter', 'all');
        $pos = $component->viewData('purchaseOrders');
        $this->assertTrue($pos->contains('po_number', 1001));
        $this->assertTrue($pos->contains('po_number', 1002));
    }

    public function test_it_scopes_filtered_totals_by_selected_year()
    {
        $currentYear = now()->year;
        $prevYear = $currentYear - 1;

        $this->createPO(2001, now(), 1000000);
        $this->createPO(2002, now()->subYear(), 500000);

        // Default (current year)
        $component = Livewire::test(PurchaseOrderIndex::class);
        $this->assertEquals(1000000, $component->get('filteredTotal')['IDR'] ?? 0);

        // Previous year
        $component->set('yearFilter', (string) $prevYear);
        $this->assertEquals(500000, $component->get('filteredTotal')['IDR'] ?? 0);

        // All years
        $component->set('yearFilter', 'all');
        $this->assertEquals(1500000, $component->get('filteredTotal')['IDR'] ?? 0);
    }

    public function test_clear_filters_resets_year_to_current_year()
    {
        Livewire::test(PurchaseOrderIndex::class)
            ->set('yearFilter', 'all')
            ->call('clearFilters')
            ->assertSet('yearFilter', (string) now()->year);
    }
}
