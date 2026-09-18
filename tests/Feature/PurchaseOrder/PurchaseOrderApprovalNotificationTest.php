<?php

namespace Tests\Feature\PurchaseOrder;

use App\Events\ApprovalCompleted;
use App\Infrastructure\Persistence\Eloquent\Models\ApprovalRequest;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderCategory;
use App\Notifications\PurchaseOrderApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseOrderApprovalNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $accountingAdmin;
    private User $regularUser;
    private PurchaseOrder $purchaseOrder;
    private ApprovalRequest $approvalRequest;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'accounting-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'purchaser', 'guard_name' => 'web']);

        $category = PurchaseOrderCategory::create(['name' => 'Manufacturing Supplies']);

        $admin = User::factory()->create(['name' => 'Accounting Officer']);
        $this->accountingAdmin = User::find($admin->id);
        $this->accountingAdmin->assignRole('accounting-admin');

        $regular = User::factory()->create(['name' => 'Regular Purchaser']);
        $this->regularUser = User::find($regular->id);
        $this->regularUser->assignRole('purchaser');

        $this->purchaseOrder = PurchaseOrder::create([
            'po_number' => 88801,
            'creator_id' => $this->regularUser->id,
            'purchase_order_category_id' => $category->id,
            'vendor_name' => 'PT Test Supplier Indonesia',
            'filename' => 'po_88801.pdf',
            'currency' => 'IDR',
            'total' => 7500000,
        ]);

        $this->approvalRequest = ApprovalRequest::create([
            'approvable_type' => PurchaseOrder::class,
            'approvable_id' => $this->purchaseOrder->id,
            'status' => 'APPROVED',
            'current_step' => 1,
        ]);
    }

    public function test_accounting_admin_receives_notification_when_po_is_approved(): void
    {
        Notification::fake();

        ApprovalCompleted::dispatch($this->purchaseOrder, $this->approvalRequest);

        Notification::assertSentTo(
            $this->accountingAdmin,
            PurchaseOrderApprovedNotification::class,
            function (PurchaseOrderApprovedNotification $notification) {
                return $notification->purchaseOrder->id === $this->purchaseOrder->id
                    && $notification->purchaseOrder->po_number === 88801;
            }
        );

        Notification::assertNotSentTo(
            $this->regularUser,
            PurchaseOrderApprovedNotification::class
        );
    }

    public function test_database_notification_payload_is_properly_stored(): void
    {
        ApprovalCompleted::dispatch($this->purchaseOrder, $this->approvalRequest);

        $dbNotification = $this->accountingAdmin->notifications()->first();
        $this->assertNotNull($dbNotification);

        $data = $dbNotification->data;
        $this->assertEquals('Purchase Order Approved', $data['title']);
        $this->assertStringContainsString('88801', $data['message']);
        $this->assertStringContainsString('PT Test Supplier Indonesia', $data['message']);
        $this->assertEquals(route('po.view', $this->purchaseOrder->id), $data['action_url']);
        $this->assertEquals(88801, $data['po_number']);
        $this->assertEquals('success', $data['category']);
    }

    public function test_mail_message_content_is_properly_formatted(): void
    {
        $notification = new PurchaseOrderApprovedNotification($this->purchaseOrder);
        $mail = $notification->toMail($this->accountingAdmin);

        $this->assertEquals('Purchase Order Approved: #88801', $mail->subject);
        $this->assertEquals('Hello, Accounting Officer', $mail->greeting);
        $this->assertEquals(route('po.view', $this->purchaseOrder->id), $mail->actionUrl);
    }

    public function test_accounting_admin_with_opt_out_preference_does_not_receive_notification(): void
    {
        Notification::fake();

        $createdOptOut = User::factory()->create([
            'name' => 'Opt Out Admin',
            'email_notification_mode' => 'none',
        ]);
        $optOutAdmin = User::find($createdOptOut->id);
        $optOutAdmin->assignRole('accounting-admin');

        ApprovalCompleted::dispatch($this->purchaseOrder, $this->approvalRequest);

        Notification::assertSentTo($this->accountingAdmin, PurchaseOrderApprovedNotification::class);
        Notification::assertNotSentTo($optOutAdmin, PurchaseOrderApprovedNotification::class);
    }
}
