<?php

use App\Domains\Ticketing\Entities\TicketCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $categories = [
            [
                'name' => 'Software / Application Bug',
                'description' => 'Errors, crashes, or broken UI across DISS modules.',
                'sla_hours' => 24,
                'is_active' => true,
            ],
            [
                'name' => 'Fleet Management & P2H',
                'description' => 'Vehicle inspection (P2H), QR stickers, KIR & STNK compliance issues.',
                'sla_hours' => 24,
                'is_active' => true,
            ],
            [
                'name' => 'Procurement & SAP Sync',
                'description' => 'Purchase requests, purchase orders, SAP synchronization, and forecast errors.',
                'sla_hours' => 24,
                'is_active' => true,
            ],
            [
                'name' => 'Access & Permissions',
                'description' => 'Password reset, account unlock, missing user roles or permissions.',
                'sla_hours' => 12,
                'is_active' => true,
            ],
            [
                'name' => 'Hardware & Peripheral',
                'description' => 'Issues with PC, laptop, monitor, barcode scanner, or printer.',
                'sla_hours' => 24,
                'is_active' => true,
            ],
            [
                'name' => 'Network & Connectivity',
                'description' => 'Wi-Fi disconnections, LAN cable faults, VPN, or internet slowness.',
                'sla_hours' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'IT Project & KPI',
                'description' => 'Project tracking, task approvals, or KPI dashboard inquiries.',
                'sla_hours' => 48,
                'is_active' => true,
            ],
            [
                'name' => 'Feature Request',
                'description' => 'Suggestions for new features, workflows, or report exports.',
                'sla_hours' => 120,
                'is_active' => true,
            ],
            [
                'name' => 'General Inquiry / Other',
                'description' => 'Questions, user guidance, or general technical assistance.',
                'sla_hours' => 48,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $data) {
            TicketCategory::updateOrCreate(['name' => $data['name']], $data);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Preserved to maintain foreign key integrity with existing tickets
    }
};
