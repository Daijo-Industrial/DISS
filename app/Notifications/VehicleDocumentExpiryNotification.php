<?php

namespace App\Notifications;

use App\Models\VehicleDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleDocumentExpiryNotification extends Notification
{
    use Queueable;

    public VehicleDocument $document;

    /**
     * Create a new notification instance.
     */
    public function __construct(VehicleDocument $document)
    {
        $this->document = $document;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $vehicle = $this->document->vehicle;
        $plate = $vehicle?->plate_number ?? 'Kendaraan';
        $typeLabel = $this->document->type_label;
        $statusLabel = $this->document->status_label;
        $expiredDate = $this->document->expired_date ? $this->document->expired_date->isoFormat('DD MMMM YYYY') : '—';
        $url = route('vehicles.show', ['vehicle' => $this->document->vehicle_id, 'tab' => 'documents']);

        return (new MailMessage)
            ->subject(sprintf('[Reminder Legalitas] %s %s - %s', $plate, $typeLabel, $statusLabel))
            ->greeting('Halo, ' . $notifiable->name)
            ->line(sprintf('Dokumen **%s** untuk armada **%s (%s)** berstatus **%s**.', $typeLabel, $plate, $vehicle?->display_name, $statusLabel))
            ->line('Tanggal Jatuh Tempo: **' . $expiredDate . '**')
            ->line('Nomor Dokumen: ' . ($this->document->document_number ?: '—'))
            ->action('Lihat Detail Armada & Dokumen', $url)
            ->line('Mohon segera memproses perpanjangan sebelum batas waktu agar operasional tidak terganggu.');
    }

    /**
     * Get the array representation of the notification (for Database and Bell widget).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $vehicle = $this->document->vehicle;
        $plate = $vehicle?->plate_number ?? 'Kendaraan';
        $typeLabel = $this->document->type_label;
        $statusLabel = $this->document->status_label;

        return [
            'title' => sprintf('Peringatan Dokumen Armada: %s (%s)', $plate, $typeLabel),
            'message' => sprintf('Dokumen %s untuk %s berstatus %s (Jatuh tempo: %s).', $typeLabel, $plate, $statusLabel, $this->document->expired_date?->format('d/m/Y')),
            'action_url' => route('vehicles.show', ['vehicle' => $this->document->vehicle_id, 'tab' => 'documents']),
            'vehicle_id' => $this->document->vehicle_id,
            'document_id' => $this->document->id,
            'document_type' => $this->document->document_type,
            'status' => $this->document->status,
        ];
    }
}
