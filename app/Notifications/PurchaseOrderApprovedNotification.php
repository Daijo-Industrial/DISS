<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PurchaseOrder $purchaseOrder,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $poNumber = $this->purchaseOrder->po_number;
        $vendor = $this->purchaseOrder->vendor_name ?: 'N/A';
        $formattedTotal = ($this->purchaseOrder->currency ?: 'IDR') . ' ' . number_format($this->purchaseOrder->total, 2, '.', ',');
        $url = route('po.view', $this->purchaseOrder->id);

        return (new MailMessage)
            ->subject("Purchase Order Approved: #{$poNumber}")
            ->greeting("Hello, {$notifiable->name}")
            ->line("Purchase Order #{$poNumber} ({$vendor}) has been fully approved.")
            ->line("Total Valuation: {$formattedTotal}")
            ->action('View Purchase Order', $url)
            ->line('This notification was sent to you because you are an Accounting Administrator.');
    }

    public function toArray(object $notifiable): array
    {
        $poNumber = $this->purchaseOrder->po_number;
        $vendor = $this->purchaseOrder->vendor_name ?: 'N/A';
        $formattedTotal = ($this->purchaseOrder->currency ?: 'IDR') . ' ' . number_format($this->purchaseOrder->total, 2, '.', ',');

        return [
            'title' => 'Purchase Order Approved',
            'message' => "PO #{$poNumber} ({$vendor}) for {$formattedTotal} has been fully approved.",
            'action_url' => route('po.view', $this->purchaseOrder->id),
            'icon' => 'bx bx-check-circle',
            'category' => 'success',
            'approvable_id' => $this->purchaseOrder->id,
            'po_number' => $poNumber,
        ];
    }
}
