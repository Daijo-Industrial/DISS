<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\SupplierEvaluation\Services;

use App\Models\PurchasingUpdateLog;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * High-performance streaming SAP export parser and table updater.
 * ponytail: Native PHP stream decoding for UTF-16LE/UTF-8 with zero external dependencies.
 */
final class SapEvaluationImportService
{
    /**
     * Map model keys to table metadata.
     */
    public const MODEL_CONFIG = [
        'po_list' => [
            'label' => 'Master PO (GRPO List)',
            'table' => 'purchasing_list_po',
            'sample_file' => '(EVALUASI) LIST GRPO.xls',
        ],
        'kriteria1' => [
            'label' => 'Kriteria 1 - Vendor Claim',
            'table' => 'purchasing_vendor_claim',
            'sample_file' => '(EVALUASI) VENDOR CLAIM.xls',
        ],
        'kriteria2' => [
            'label' => 'Kriteria 2 - Accuracy Good',
            'table' => 'purchasing_vendor_accuracy_good',
            'sample_file' => '(EVALUASI) VENDOR ACCURACY GOOD.xls',
        ],
        'kriteria3' => [
            'label' => 'Kriteria 3 - Ontime Delivery',
            'table' => 'purchasing_vendor_ontime_delivery',
            'sample_file' => '(EVALUASI) VENDOR ONTIME DELIVERY.xls',
        ],
        'kriteria4' => [
            'label' => 'Kriteria 4 - Urgent Request',
            'table' => 'purchasing_vendor_urgent_request',
            'sample_file' => '(EVALUASI) VENDOR URGENT REQUEST.xls',
        ],
        'kriteria5' => [
            'label' => 'Kriteria 5 - Claim Response (CPAR)',
            'table' => 'purchasing_vendor_claim_response',
            'sample_file' => '(EVALUASI) VENDOR CLAIM RESPON.xls',
        ],
        'kriteria6' => [
            'label' => 'Kriteria 6 - Vendor Certificate',
            'table' => 'purchasing_vendor_list_certificate',
            'sample_file' => '(EVALUASI) LIST VENDOR.xls',
        ],
        'purchasing_contact' => [
            'label' => 'Purchasing Contacts & Vendor Dept',
            'table' => 'purchasing_contacts',
            'sample_file' => '(EVALUASI) PURCHASING CONTACT.xls',
        ],
    ];

    /**
     * Import an uploaded SAP file into the chosen model table.
     */
    public function import(string $modelKey, UploadedFile|string $file): array
    {
        if (! isset(self::MODEL_CONFIG[$modelKey])) {
            throw new InvalidArgumentException("Unknown evaluation model key: [{$modelKey}]");
        }

        $config = self::MODEL_CONFIG[$modelKey];
        $tableName = $config['table'];
        $filePath = is_string($file) ? $file : $file->getRealPath();

        $startTime = microtime(true);

        $handle = fopen($filePath, 'rb');
        if (! $handle) {
            throw new InvalidArgumentException('Failed to open uploaded file for reading.');
        }

        // Detect encoding / BOM (SAP exports are UTF-16LE tab-delimited text)
        $bom = fread($handle, 2);
        if ($bom === "\xFF\xFE") {
            stream_filter_append($handle, 'convert.iconv.UTF-16LE/UTF-8');
        } elseif ($bom === "\xEF\xBB") {
            fread($handle, 1); // Consume 3rd byte of UTF-8 BOM
        } else {
            rewind($handle);
        }

        // Read header line
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            throw new InvalidArgumentException('Uploaded file is empty.');
        }

        $delimiter = str_contains($firstLine, "\t") ? "\t" : (str_contains($firstLine, ';') ? ';' : ',');
        $rawHeaders = explode($delimiter, trim($firstLine, "\r\n"));

        // Build header index map: normalize column names to lowercase trimmed strings
        $headerMap = [];
        foreach ($rawHeaders as $idx => $name) {
            $cleaned = strtolower(trim($name));
            if ($cleaned !== '') {
                $headerMap[$cleaned] = $idx;
            }
        }

        $records = [];
        $insertedCount = 0;
        $batchSize = 1000;

        DB::beginTransaction();

        try {
            // Full replacement strategy: delete existing data before loading fresh SAP export
            DB::table($tableName)->delete();

            while (($line = fgets($handle)) !== false) {
                $trimmed = trim($line, "\r\n");
                if ($trimmed === '') {
                    continue;
                }

                $cols = explode($delimiter, $trimmed);

                $getCol = function (string ...$candidates) use ($cols, $headerMap): ?string {
                    foreach ($candidates as $c) {
                        $key = strtolower(trim($c));
                        if (isset($headerMap[$key])) {
                            $idx = $headerMap[$key];
                            if (isset($cols[$idx])) {
                                return trim($cols[$idx]);
                            }
                        }
                    }

                    return null;
                };

                $record = $this->transformRow($modelKey, $getCol);

                if (! empty($record)) {
                    $records[] = $record;
                    if (count($records) >= $batchSize) {
                        DB::table($tableName)->insert($records);
                        $insertedCount += count($records);
                        $records = [];
                    }
                }
            }

            if (! empty($records)) {
                DB::table($tableName)->insert($records);
                $insertedCount += count($records);
            }

            // Update log timestamp
            PurchasingUpdateLog::updateOrCreate(['id' => 1], ['updated_at' => now()]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        $elapsed = round(microtime(true) - $startTime, 2);

        return [
            'success' => true,
            'model' => $modelKey,
            'label' => $config['label'],
            'table' => $tableName,
            'count' => $insertedCount,
            'elapsed_seconds' => $elapsed,
            'message' => "Successfully updated {$config['label']} ({$insertedCount} records imported in {$elapsed}s).",
        ];
    }

    /**
     * Map row data to DB column dictionary.
     */
    private function transformRow(string $modelKey, \Closure $getCol): ?array
    {
        return match ($modelKey) {
            'po_list' => $this->transformPoList($getCol),
            'kriteria1' => $this->transformVendorClaim($getCol),
            'kriteria2' => $this->transformAccuracyGood($getCol),
            'kriteria3' => $this->transformOntimeDelivery($getCol),
            'kriteria4' => $this->transformUrgentRequest($getCol),
            'kriteria5' => $this->transformClaimResponse($getCol),
            'kriteria6' => $this->transformVendorListCertificate($getCol),
            'purchasing_contact' => $this->transformPurchasingContact($getCol),
            default => null,
        };
    }

    private function transformPoList(\Closure $getCol): ?array
    {
        $supplierCode = $getCol('Customer/Supplier Code', 'BP Code', 'Supplier Code');
        if (! $supplierCode) {
            return null;
        }

        return [
            'supplier_code' => $supplierCode,
            'supplier_name' => $getCol('Customer/Supplier Name', 'BP Name', 'Supplier Name') ?? '',
            'doc_status' => $getCol('Document Status', 'Doc Status') ?? 'C',
            'posting_date' => $this->parseDate($getCol('Posting Date', 'Doc Date')) ?? now()->toDateString(),
        ];
    }

    private function transformVendorClaim(\Closure $getCol): ?array
    {
        $vendorCode = $getCol('Vendor Code', 'BP Code');
        if (! $vendorCode) {
            return null;
        }

        return [
            'vendor_code' => $vendorCode,
            'vendor_name' => $getCol('Vendor Name', 'BP Name') ?? '',
            'item_code' => $getCol('Item Code') ?? '',
            'description' => $getCol('Description', 'Item Description') ?? '',
            'delivery_no' => $getCol('Delivery No', 'Delivery Note') ?? '',
            'incoming_date' => $this->parseDate($getCol('Incoming Date')) ?? now()->toDateString(),
            'quantity' => $this->parseInt($getCol('Quantity', 'Qty')),
            'claim_start_date' => $this->parseDate($getCol('Claim Start Date')) ?? now()->toDateString(),
            'claim_finish_date' => $this->parseDate($getCol('Claim Finished Date', 'Claim Finish Date')) ?? now()->toDateString(),
            'can_use' => $getCol('Can Use') ?? 'No',
            'remarks' => $this->cleanString($getCol('Remarks')),
            'reason' => $this->cleanString($getCol('Reason')),
            'risk' => $this->cleanString($getCol('Risk')),
            'customer_stopline' => $this->cleanString($getCol('Stop Line', 'Customer Stopline')),
        ];
    }

    private function transformAccuracyGood(\Closure $getCol): ?array
    {
        $vendorCode = $getCol('Vendor Code', 'BP Code');
        if (! $vendorCode) {
            return null;
        }

        return [
            'vendor_code' => $vendorCode,
            'vendor_name' => $getCol('Vendor Name', 'BP Name') ?? '',
            'item_code' => $getCol('Item Code') ?? '',
            'description' => $getCol('Description') ?? '',
            'delivery_no' => $getCol('Delivery No') ?? '',
            'incoming_date' => $this->parseDate($getCol('Incoming Date')) ?? now()->toDateString(),
            'delivery_quantity' => $this->parseInt($getCol('Delivery Qty', 'Delivery Quantity')),
            'received_quantity' => $this->parseInt($getCol('Received Qty', 'Received Quantity')),
            'shortage_quantity' => $this->parseInt($getCol('Shortage Qty', 'Shortage Quantity')),
            'over_quantity' => $this->parseInt($getCol('Over Qty', 'Over Quantity')),
            'close_status' => $getCol('Close Status') ?? 'Yes',
        ];
    }

    private function transformOntimeDelivery(\Closure $getCol): ?array
    {
        $vendorCode = $getCol('Vendor Code', 'BP Code');
        if (! $vendorCode) {
            return null;
        }

        return [
            'vendor_code' => $vendorCode,
            'vendor_name' => $getCol('Vendor Name', 'BP Name') ?? '',
            'item_code' => $getCol('Item Code') ?? '',
            'description' => $getCol('Description') ?? '',
            'request_date' => $this->parseDate($getCol('Request Date')) ?? now()->toDateString(),
            'request_quantity' => $this->parseInt($getCol('Request Qty', 'Request Quantity')),
            'actual_date' => $this->parseDate($getCol('Actual Date')) ?? now()->toDateString(),
            'actual_incoming_quantity' => $this->parseInt($getCol('Actual Incoming Qty', 'Actual Incoming Quantity')),
        ];
    }

    private function transformUrgentRequest(\Closure $getCol): ?array
    {
        $poNo = $getCol('PO No', 'PO Number');
        if (! $poNo) {
            return null;
        }

        return [
            'po_no' => $poNo,
            'po_date' => $this->parseDate($getCol('PO Date')) ?? now()->toDateString(),
            'item_code' => $getCol('Item Code') ?? '',
            'description' => $getCol('Description') ?? '',
            'request_date' => $this->parseDate($getCol('Request Date')) ?? now()->toDateString(),
            'request_quantity' => $this->parseInt($getCol('Request Qty', 'Request Quantity')),
            'incoming_date' => $this->parseDate($getCol('Incoming Date')) ?? now()->toDateString(),
            'incoming_quantity' => $this->parseInt($getCol('Incoming Qty', 'Incoming Quantity')),
            'vendor_code' => $getCol('Vendor Code', 'BP Code') ?? '',
            'vendor_name' => $getCol('Vendor Name', 'BP Name') ?? '',
            'special_price' => $getCol('Special Price') ?? 'No',
        ];
    }

    private function transformClaimResponse(\Closure $getCol): ?array
    {
        $vendorCode = $getCol('Vendor Code', 'BP Code');
        if (! $vendorCode) {
            return null;
        }

        return [
            'vendor_claim_code' => $this->cleanString($getCol('Vendor Claim Code')),
            'vendor_code' => $vendorCode,
            'vendor_name' => $getCol('Vendor Name', 'BP Name') ?? '',
            'item_code' => $getCol('Item Code') ?? '',
            'description' => $getCol('Description') ?? '',
            'cpar_no' => $getCol('CPAR_NO', 'CPAR No') ?? '',
            'cpar_sent_date' => $this->parseDate($getCol('CPAR Sent Date')) ?? now()->toDateString(),
            'cpar_response_date' => $this->parseDate($getCol('CPAR Respon Date', 'CPAR Response Date')) ?? now()->toDateString(),
            'close_status' => $getCol('Close Status') ?? 'Yes',
        ];
    }

    private function transformVendorListCertificate(\Closure $getCol): ?array
    {
        $vendorCode = $getCol('BP Code', 'Vendor Code');
        if (! $vendorCode) {
            return null;
        }

        return [
            'vendor_code' => $vendorCode,
            'vendor_name' => $getCol('BP Name', 'Vendor Name') ?? '',
            'iso_9001_doc' => $this->cleanString($getCol('ISO 9001:2015 NUM', 'ISO 9001 NUM')),
            'iso_9001_start_date' => $this->parseDate($getCol('ISO 9001:2015 START DATE', 'ISO 9001 START DATE')),
            'iso_9001_end_date' => $this->parseDate($getCol('ISO 9001:2015 END DATE', 'ISO 9001 END DATE')),
            'iso_14001_doc' => $this->cleanString($getCol('ISO 14001:2015 NO', 'ISO 14001 NO')),
            'iso_14001_start_date' => $this->parseDate($getCol('ISO 14001:2015 START DATE', 'ISO 14001 START DATE')),
            'iso_14001_end_date' => $this->parseDate($getCol('ISO 14001:2015 END DATE', 'ISO 14001 END DATE')),
            'iatf_16949_doc' => $this->cleanString($getCol('IATF 16949:2016 NO', 'IATF 16949 NO')),
            'iatf_16949_start_date' => $this->parseDate($getCol('IATF 16949:2016 START DATE', 'IATF 16949 START DATE')),
            'iatf_16949_end_date' => $this->parseDate($getCol('IATF 16949:2016 END DATE', 'IATF 16949 END DATE')),
        ];
    }

    // ponytail: (EVALUASI) PURCHASING CONTACT.xls fully replaces legacy vendor list
    private function transformPurchasingContact(\Closure $getCol): ?array
    {
        $vendorCode = $getCol('BP Code', 'Vendor Code');
        if (! $vendorCode) {
            return null;
        }

        $firstWord = $this->cleanString($getCol('FirstWord'));
        $pMember = ($firstWord !== null && ! str_starts_with($firstWord, '-'))
            ? $firstWord
            : null;

        return [
            'vendor_code' => $vendorCode,
            'vendor_name' => $getCol('BP Name', 'Vendor Name') ?? '',
            'p_member' => $pMember,
            'persontocontact' => $this->cleanString($getCol('Contact Person Name', 'persontocontact')),
        ];
    }

    /**
     * Parse date string robustly (supports dd.mm.yy, dd.mm.yyyy, yyyy-mm-dd).
     */
    public function parseDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $val = trim($value);
        if ($val === '' || $val === '0000-00-00' || $val === '-' || $val === '0') {
            return null;
        }

        // Check dd.mm.yy
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2})$/', $val, $m)) {
            $year = (int) $m[3] < 50 ? 2000 + (int) $m[3] : 1900 + (int) $m[3];

            return sprintf('%04d-%02d-%02d', $year, (int) $m[2], (int) $m[1]);
        }

        // Check dd.mm.yyyy or dd/mm/yyyy or dd-mm-yyyy
        if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})$/', $val, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        // Check yyyy-mm-dd
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
            return $val;
        }

        try {
            return Carbon::parse($val)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Parse number string removing commas and rounding decimals.
     */
    public function parseInt(?string $value): int
    {
        if ($value === null) {
            return 0;
        }

        $cleaned = str_replace([',', ' '], '', trim($value));
        if ($cleaned === '' || ! is_numeric($cleaned)) {
            return 0;
        }

        return (int) round((float) $cleaned);
    }

    private function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $val = trim($value);

        return $val === '' ? null : $val;
    }
}
