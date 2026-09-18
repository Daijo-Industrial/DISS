<?php

namespace App\Application\PurchaseOrder\Listeners;

use App\Events\ApprovalCompleted;
use App\Infrastructure\Approval\Services\ApprovalScopingManager;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Models\PurchaseOrder;
use App\Notifications\PurchaseOrderApprovedNotification;
use Illuminate\Support\Facades\Notification;

final class HandlePurchaseOrderApprovalNotifications
{
    public function __construct(private ApprovalScopingManager $scopingManager) {}

    public function handle(ApprovalCompleted $event): void
    {
        if (! $event->approvable instanceof PurchaseOrder) {
            return;
        }

        $po = $event->approvable;

        // Fetch all accounting-admin users and check their notification preferences
        $accountingAdmins = User::role('accounting-admin')->get()
            ->filter(function ($user) use ($po) {
                return $this->scopingManager->wantsNotification($user, PurchaseOrder::class, 'immediate');
            });

        if ($accountingAdmins->isNotEmpty()) {
            Notification::send($accountingAdmins, new PurchaseOrderApprovedNotification($po));
        }
    }
}
