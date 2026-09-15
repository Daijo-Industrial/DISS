---
name: supplier-evaluation
description: Domain knowledge and runbooks for Purchasing Supplier Evaluation, SAP export data parsing, scoring criteria, and vendor table imports in DISS. Use whenever working on purchasing evaluations, SAP xls imports, vendor criteria, or scoring reports.
---

# Purchasing Supplier Evaluation & SAP Data Import

This skill provides domain knowledge, file formats, and architecture guidelines for the Supplier Evaluation module in Daijo Industrial System (DISS).

---

## 1. SAP File Format & Streaming Ingestion

Exported files from SAP have a `.xls` extension but are actually **tab-delimited text with UTF-16LE encoding and CRLF line breaks**.
- **Encoding**: UTF-16LE with BOM `\xFF\xFE`. In PHP, must be read with stream filter `convert.iconv.UTF-16LE/UTF-8` to stream-decode in milliseconds with zero memory overhead.
- **Date Format**: Dates from SAP are formatted as `dd.mm.yy` (e.g. `19.04.24` &rarr; `2024-04-19`). Handled via `SapEvaluationImportService::parseDate()`.
- **Numeric Fields**: Quantities contain commas as thousands separators and 5 decimal zeros (e.g. `1,000.00000`). Commas must be stripped before casting to `int` or `float`.
- **Timestamps**: The evaluation and master tables do not contain `created_at` or `updated_at` columns. Models must have `public $timestamps = false;` and `$guarded = ['id'];`.

---

## 2. 8 SAP Files to Database Models Mapping

| Target Model | Database Table | SAP Export File | Criteria / Purpose | Key Column Mappings |
| :--- | :--- | :--- | :--- | :--- |
| `PurchasingListPo` | `purchasing_list_po` | `(EVALUASI) LIST GRPO.xls` | PO Master (37k+ rows, used for supplier selection & active months) | `Customer/Supplier Code` &rarr; `supplier_code`<br>`Customer/Supplier Name` &rarr; `supplier_name`<br>`Document Status` &rarr; `doc_status`<br>`Posting Date` &rarr; `posting_date` (`d.m.y`) |
| `PurchasingVendorClaim` | `purchasing_vendor_claim` | `(EVALUASI) VENDOR CLAIM.xls` | Kriteria 1: Kualitas Barang & Kemasan | `Vendor Code` &rarr; `vendor_code`<br>`Vendor Name` &rarr; `vendor_name`<br>`Incoming Date` &rarr; `incoming_date`<br>`Quantity` &rarr; `quantity`<br>`Claim Start Date` &rarr; `claim_start_date`<br>`Claim Finished Date` &rarr; `claim_finish_date`<br>`Stop Line` &rarr; `customer_stopline` |
| `PurchasingVendorAccuracyGood` | `purchasing_vendor_accuracy_good` | `(EVALUASI) VENDOR ACCURACY GOOD.xls` | Kriteria 2: Ketepatan Kuantitas | `Delivery Qty` &rarr; `delivery_quantity`<br>`Received Qty` &rarr; `received_quantity`<br>`Shortage Qty` &rarr; `shortage_quantity`<br>`Over Qty` &rarr; `over_quantity`<br>`Close Status` &rarr; `close_status` |
| `PurchasingVendorOntimeDelivery` | `purchasing_vendor_ontime_delivery` | `(EVALUASI) VENDOR ONTIME DELIVERY.xls` | Kriteria 3: Ketepatan Waktu Pengiriman | `Request Date` &rarr; `request_date`<br>`Request Qty` &rarr; `request_quantity`<br>`Actual Date` &rarr; `actual_date`<br>`Actual Incoming Qty` &rarr; `actual_incoming_quantity` |
| `PurchasingVendorUrgentRequest` | `purchasing_vendor_urgent_request` | `(EVALUASI) VENDOR URGENT REQUEST.xls` | Kriteria 4: Kerjasama Permintaan Mendadak | `PO No` &rarr; `po_no`<br>`PO Date` &rarr; `po_date`<br>`Request Date` &rarr; `request_date`<br>`Request Qty` &rarr; `request_quantity`<br>`Incoming Date` &rarr; `incoming_date`<br>`Incoming Qty` &rarr; `incoming_quantity`<br>`Special Price` &rarr; `special_price` |
| `PurchasingVendorClaimResponse` | `purchasing_vendor_claim_response` | `(EVALUASI) VENDOR CLAIM RESPON.xls` | Kriteria 5: Respon Klaim (CPAR) | `Vendor Claim Code` &rarr; `vendor_claim_code`<br>`CPAR_NO` &rarr; `cpar_no`<br>`CPAR Sent Date` &rarr; `cpar_sent_date`<br>`CPAR Respon Date` &rarr; `cpar_response_date`<br>`Close Status` &rarr; `close_status` |
| `PurchasingVendorListCertificate` | `purchasing_vendor_list_certificate` | `(EVALUASI) LIST VENDOR.xls` | Kriteria 6: Sertifikasi ISO / IATF | `BP Code` &rarr; `vendor_code`<br>`BP Name` &rarr; `vendor_name`<br>`ISO 9001:2015 NUM` &rarr; `iso_9001_doc`<br>`ISO 9001:2015 START/END DATE`<br>`ISO 14001:2015 NO / DATES`<br>`IATF 16949:2016 NO / DATES` |
| `PurchasingContact` | `purchasing_contacts` | `(EVALUASI) VENDOR LIST PURCHASING DEPT.xls` | Vendor Contacts & Purchasing PIC (`p_member`) | `BP Code` &rarr; `vendor_code`<br>`BP Name` &rarr; `vendor_name`<br>`Sales Employee Name` &rarr; `p_member` (cleaned name) |

---

## 3. `p_member` Contact Name Extraction Rules

In `(EVALUASI) VENDOR LIST PURCHASING DEPT.xls`, column `Sales Employee Name` contains concatenated data like `"AYU KARIMA H. Ext 186 ayu@daijo.co.id"`.
- Rule in `SapEvaluationImportService::extractContactName()`:
  - Strips everything starting from `Ext`, `email:`, or `@`.
  - Extracts the full name (even if multi-word: `AYU KARIMA H.`, `BAYU SETIADJI`, `DIAN`).
  - Missing or placeholder values like `"-No Sales Employee-"` are converted to `null`.
- In views (`foremind_detail.blade.php`, `foremind_detail_print_customer_excel.blade.php`, `supplier_detail.blade.php`), it is displayed with fallback `'-'`.

---

## 4. Update Strategy & Access Control

- **Strategy**: Full replacement (`DELETE FROM <table>` inside `DB::transaction()` followed by batch inserts in 1,000-row chunks).
- **Log Touch**: Updates `PurchasingUpdateLog::updateOrCreate(['id' => 1], ['updated_at' => now()])`.
- **Authorization**: Strictly gated to users with roles `super-admin` or `admin`.
- **UI Trigger**:
  - Modal: `resources/views/purchasing/evaluationsupplier/partials/import_modal.blade.php`.
  - Main button in `supplier_selection.blade.php` ("Update / Import SAP Data").
  - Quick action buttons on each Kriteria view (`kriteria1.blade.php` &rarr; `kriteria6.blade.php`) calling `openImportModal('kriteriaX')`.
- **Route**: `POST /purc/evaluationsupplier/import` &rarr; `PurchasingSupplierEvaluationController@import`.

---

## 5. Scoring Logic & Calculations

Managed by `SupplierScoringService`:
- **Kriteria 1 (Kualitas Barang)**: Max 20 points. Deducts 5 points per monthly claim down to min 0.
- **Customer Stopline**: Max 10 points. Deducts 5 points per claim with `customer_stopline == 'Yes'`.
- **Kriteria 2 (Kuantitas)**: Max 20 points. Deducts 5 points per discrepancy record.
- **Kriteria 3 (Waktu Pengiriman)**: Max 20 points. Deducts 5 points per late delivery record.
- **Kriteria 4 (Permintaan Mendadak)**: Max 10 points. 10 if on same date with normal price, 5 if special price.
- **Kriteria 5 (Respon Klaim / CPAR)**: Max 10 points. Closed within 1-3 days: 10 pts, 4-5 days: 5 pts, >5 days: 0 pts.
- **Kriteria 6 (Sertifikasi)**: Max 10 points. IATF 16949: 10 pts, ISO 9001 or 14001: 5 pts, none: 0 pts.
- **Grade**: Average score &ge; 81 &rarr; **A** (Diteruskan), &ge; 61 &rarr; **B** (Dipertahankan), &lt; 61 &rarr; **C** (Monitoring 3 bulan).
