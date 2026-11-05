<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Purchase Requisition — {{ $purchaseRequest->doc_num }}</title>
    <style>
        @page {
            margin: 18mm 14mm 16mm 14mm;
            size: a4 landscape;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 9.5pt;
            color: #1e293b;
            line-height: 1.35;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        /* ── Header ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0f172a;
        }

        .header-table td {
            vertical-align: middle;
            padding: 0;
        }

        .company-logo {
            max-height: 48px;
            max-width: 160px;
        }

        .company-name {
            font-size: 13pt;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.3px;
            text-transform: uppercase;
        }

        .company-subtext {
            font-size: 7.5pt;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-title {
            font-size: 16pt;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            text-align: right;
            margin: 0;
        }

        .doc-subtitle {
            font-size: 8pt;
            color: #475569;
            text-align: right;
            margin-top: 2px;
            font-weight: 600;
        }

        /* ── Status Badge ── */
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 7.5pt;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .status-draft { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .status-in_review { background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .status-approved { background-color: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .status-returned { background-color: #e0e7ff; color: #3730a3; border: 1px solid #a5b4fc; }
        .status-canceled { background-color: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0; text-decoration: line-through; }

        /* ── Metadata Grid ── */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }

        .meta-table td {
            padding: 4px 8px;
            vertical-align: top;
            font-size: 8.5pt;
        }

        .meta-label {
            width: 12%;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            font-size: 7pt;
            letter-spacing: 0.4px;
        }

        .meta-value {
            width: 38%;
            font-weight: 600;
            color: #0f172a;
        }

        /* ── Items Table ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 700;
            text-align: left;
            padding: 6px 8px;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #0f172a;
        }

        .items-table td {
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
            font-size: 8.5pt;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .items-table tr.rejected td {
            background-color: #fef2f2 !important;
            color: #991b1b;
        }

        .items-table tr.rejected td strong {
            text-decoration: line-through;
            color: #991b1b;
        }

        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .font-mono { font-family: monospace, Courier, sans-serif; }

        /* ── Summary & Totals ── */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .summary-table td {
            padding: 0;
            vertical-align: top;
        }

        .remarks-box {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            padding: 8px 10px;
            font-size: 8pt;
            color: #475569;
            margin-right: 12px;
        }

        .totals-card {
            width: 280px;
            border-collapse: collapse;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
        }

        .totals-card td {
            padding: 6px 10px;
            font-size: 9pt;
        }

        .totals-card .grand-total-row {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 800;
            font-size: 10pt;
        }

        /* ── Signatures Section ── */
        .signatures-section {
            width: 100%;
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .signatures-header {
            font-size: 8pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            margin-bottom: 4px;
            padding-bottom: 2px;
            border-bottom: 1px solid #e2e8f0;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .signature-cell {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: center;
            vertical-align: top;
            background-color: #ffffff;
        }

        .sig-role {
            font-size: 7.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #475569;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 4px;
            min-height: 14px;
        }

        .sig-image-container {
            height: 52px;
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            width: 100%;
        }

        .sig-image {
            max-height: 48px;
            max-width: 130px;
            display: inline-block;
        }

        .sig-status-placeholder {
            font-size: 8pt;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 14px 0;
        }

        .sig-name {
            font-size: 8pt;
            font-weight: 700;
            color: #0f172a;
            margin-top: 4px;
            padding-top: 2px;
            border-top: 1px solid #f1f5f9;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sig-date {
            font-size: 6.5pt;
            color: #64748b;
            margin-top: 1px;
        }

        /* ── Footer ── */
        .doc-footer {
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px solid #e2e8f0;
            font-size: 7pt;
            color: #94a3b8;
            width: 100%;
        }
    </style>
</head>

<body>
    {{-- COMPANY & DOCUMENT HEADER --}}
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <table style="border-collapse: collapse;">
                    <tr>
                        @if ($logoBase64)
                            <td style="padding-right: 12px; vertical-align: middle;">
                                <img src="{{ $logoBase64 }}" class="company-logo" alt="Logo">
                            </td>
                        @endif
                        <td style="vertical-align: middle;">
                            <div class="company-name">PT. Daijo Industrial</div>
                            <div class="company-subtext">Integrated Enterprise Procurement System</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 45%; text-align: right;">
                <h1 class="doc-title">Purchase Requisition</h1>
                <div class="doc-subtitle">
                    <strong>Doc No:</strong> {{ $purchaseRequest->doc_num }}
                    &nbsp;|&nbsp;
                    @php
                        $status = strtoupper($purchaseRequest->workflow_status);
                        $badgeClass = match ($status) {
                            'APPROVED' => 'status-approved',
                            'IN_REVIEW' => 'status-in_review',
                            'REJECTED' => 'status-rejected',
                            'RETURNED' => 'status-returned',
                            'CANCELED' => 'status-canceled',
                            default => 'status-draft',
                        };
                    @endphp
                    <span class="status-badge {{ $badgeClass }}">{{ str_replace('_', ' ', $status) }}</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- METADATA GRID --}}
    <table class="meta-table">
        <tr>
            <td class="meta-label">PR Number</td>
            <td class="meta-value font-mono">{{ $purchaseRequest->pr_no ?: '—' }}</td>
            <td class="meta-label">Date PR</td>
            <td class="meta-value">
                {{ $purchaseRequest->date_pr ? \Carbon\Carbon::parse($purchaseRequest->date_pr)->timezone('Asia/Jakarta')->format('d-m-Y') : '—' }}
            </td>
        </tr>
        <tr>
            <td class="meta-label">Requested By</td>
            <td class="meta-value">
                {{ $userCreatedBy->name ?? 'System' }}
                <span style="color: #64748b; font-weight: normal; font-size: 7.5pt;">({{ $purchaseRequest->from_department }})</span>
            </td>
            <td class="meta-label">Date Required</td>
            <td class="meta-value">
                {{ $purchaseRequest->date_required ? \Carbon\Carbon::parse($purchaseRequest->date_required)->timezone('Asia/Jakarta')->format('d-m-Y') : '—' }}
            </td>
        </tr>
        <tr>
            <td class="meta-label">Target Dept</td>
            <td class="meta-value">
                {{ $purchaseRequest->to_department->value ?? $purchaseRequest->to_department }}
                @if ($purchaseRequest->is_import)
                    <span style="font-size: 7pt; font-weight: 800; background: #e0e7ff; color: #3730a3; padding: 1px 4px; border-radius: 3px; margin-left: 4px;">IMPORT</span>
                @endif
            </td>
            <td class="meta-label">Branch</td>
            <td class="meta-value">
                {{ $purchaseRequest->branch->value ?? ($purchaseRequest->branch ?? 'HQ') }}
            </td>
        </tr>
        <tr>
            <td class="meta-label">Supplier</td>
            <td class="meta-value">{{ $purchaseRequest->supplier ?: 'Not Specified' }}</td>
            <td class="meta-label">PO Number</td>
            <td class="meta-value font-mono">{{ $purchaseRequest->po_number ?: '—' }}</td>
        </tr>
        @if ($purchaseRequest->pic)
            <tr>
                <td class="meta-label">PIC</td>
                <td class="meta-value" colspan="3">{{ $purchaseRequest->pic }}</td>
            </tr>
        @endif
    </table>

    {{-- ITEMS TABLE --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">No</th>
                <th style="width: 34%;">Item Description & Specification</th>
                <th style="width: 8%;" class="text-center">Qty</th>
                <th style="width: 6%;" class="text-center">UoM</th>
                <th style="width: 15%;" class="text-right">Unit Price</th>
                <th style="width: 18%;" class="text-right">Subtotal</th>
                <th style="width: 15%;" class="text-center">Item Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($filteredItemDetail as $detail)
                @php
                    $isRejected =
                        $detail->is_approve_by_head === 0 ||
                        $detail->is_approve_by_verificator === 0 ||
                        $detail->is_approve_by_gm === 0 ||
                        $detail->is_approve === 0;

                    $isApproved = $purchaseRequest->workflow_status === 'APPROVED' && $detail->is_approve === 1;
                    $rowClass = $isRejected ? 'rejected' : '';
                    $subtotal = (float) $detail->quantity * (float) $detail->price;
                    $currency = $detail->currency ?: 'IDR';
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $detail->item_name }}</strong>
                        @if ($detail->purpose)
                            <div style="font-size: 7.5pt; color: #64748b; margin-top: 1px;">
                                Purpose / Specs: {{ $detail->purpose }}
                            </div>
                        @endif
                    </td>
                    <td class="text-center font-mono">
                        {{ rtrim(rtrim(number_format((float) $detail->quantity, 2, '.', ','), '0'), '.') }}
                    </td>
                    <td class="text-center uppercase" style="font-size: 8pt;">{{ $detail->uom ?: 'PCS' }}</td>
                    <td class="text-right font-mono">
                        {{ $currency }} {{ number_format((float) $detail->price, 2, '.', ',') }}
                    </td>
                    <td class="text-right font-mono" style="font-weight: 700;">
                        {{ $currency }} {{ number_format($subtotal, 2, '.', ',') }}
                    </td>
                    <td class="text-center">
                        @if ($isRejected)
                            <span style="font-size: 7pt; font-weight: 800; color: #dc2626; text-transform: uppercase;">✕ REJECTED</span>
                        @elseif($isApproved)
                            <span style="font-size: 7pt; font-weight: 800; color: #16a34a; text-transform: uppercase;">✓ APPROVED</span>
                        @else
                            <span style="font-size: 7pt; font-weight: 700; color: #d97706; text-transform: uppercase;">⏳ PENDING</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 16px; color: #94a3b8;">
                        No requisition items recorded.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- REMARKS & TOTALS SUMMARY --}}
    <table class="summary-table">
        <tr>
            <td style="width: 60%;">
                @if ($purchaseRequest->remark)
                    <div class="remarks-box">
                        <strong style="font-size: 7.5pt; text-transform: uppercase; color: #0f172a; display: block; margin-bottom: 2px;">
                            Remarks / Notes:
                        </strong>
                        "{{ $purchaseRequest->remark }}"
                    </div>
                @endif
            </td>
            <td style="width: 40%; text-align: right;">
                @if (isset($totals['total']) && $totals['total'] > 0)
                    <table class="totals-card" style="margin-left: auto;">
                        <tr class="grand-total-row">
                            <td style="text-align: left;">Approved Total:</td>
                            <td class="text-right font-mono">
                                {{ $totals['currency'] ?? 'IDR' }}
                                {{ number_format((float) $totals['total'], 2, '.', ',') }}
                            </td>
                        </tr>
                        @if (!empty($totals['hasCurrencyDiff']))
                            <tr>
                                <td colspan="2" style="font-size: 7pt; color: #d97706; text-align: right; padding: 2px 8px;">
                                    * Requisition contains multi-currency line items
                                </td>
                            </tr>
                        @endif
                    </table>
                @endif
            </td>
        </tr>
    </table>

    {{-- DIGITAL CERTIFICATION & SIGNATURES BLOCK --}}
    <div class="signatures-section">
        <div class="signatures-header">
            Approval Workflow & Digital Signatures
        </div>
        <table class="signatures-table">
            <tr>
                @php
                    $sigCount = count($signatures);
                    $cellWidth = $sigCount > 0 ? (100 / $sigCount) : 20;
                @endphp
                @forelse($signatures as $sig)
                    <td class="signature-cell" style="width: {{ $cellWidth }}%;">
                        <div class="sig-role">{{ $sig['role'] }}</div>
                        <div style="height: 54px; text-align: center;">
                            @if ($sig['status'] === 'signed' && $sig['image_base64'])
                                <img src="{{ $sig['image_base64'] }}" class="sig-image" alt="Signature">
                            @elseif($sig['status'] === 'signed')
                                <div class="sig-status-placeholder" style="color: #16a34a;">
                                    ✓ CERTIFIED
                                </div>
                            @elseif($sig['status'] === 'rejected')
                                <div class="sig-status-placeholder" style="color: #dc2626;">
                                    ✕ REJECTED
                                </div>
                            @else
                                <div class="sig-status-placeholder">
                                    [ PENDING ]
                                </div>
                            @endif
                        </div>
                        <div class="sig-name" title="{{ $sig['name'] }}">
                            {{ $sig['name'] }}
                        </div>
                        <div class="sig-date">
                            @if ($sig['status'] === 'signed' && $sig['at'])
                                {{ \Carbon\Carbon::parse($sig['at'])->timezone('Asia/Jakarta')->format('d M Y H:i') }}
                            @elseif($sig['status'] === 'signed')
                                Signed
                            @else
                                Awaiting Action
                            @endif
                        </div>
                    </td>
                @empty
                    <td class="signature-cell" style="width: 100%;">
                        <div class="sig-status-placeholder">No signatures required or recorded.</div>
                    </td>
                @endforelse
            </tr>
        </table>
    </div>

    {{-- DOCUMENT FOOTER --}}
    <table class="doc-footer">
        <tr>
            <td style="text-align: left;">
                Confidential — Internal Company Document. Generated on {{ $generatedAt->format('d-m-Y H:i:s') }} WIB by {{ $generatedBy }}.
            </td>
            <td style="text-align: right;">
                PR Ref: #{{ $purchaseRequest->id }} ({{ $purchaseRequest->doc_num }})
            </td>
        </tr>
    </table>
</body>

</html>
