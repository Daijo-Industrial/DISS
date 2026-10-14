---
name: supplier-evaluation
description: Domain knowledge and runbooks for Purchasing Supplier Evaluation, SAP export data parsing, scoring criteria, and vendor table imports in DISS.
---

# Purchasing Supplier Evaluation & SAP Import

## 1. SAP File Format & Streaming Ingestion

SAP exports have a `.xls` extension but are **tab-delimited UTF-16LE text with CRLF line breaks**:
- **Encoding**: UTF-16LE with BOM `\xFF\xFE`. Read via PHP stream filter `convert.iconv.UTF-16LE/UTF-8` for zero-memory streaming.
- **Date Format**: Formatted as `dd.mm.yy` (e.g. `19.04.24` &rarr; `2024-04-19`). Handled by `SapEvaluationImportService::parseDate()`.
- **Numeric Fields**: Quantities contain commas and 5 decimal zeros (e.g. `1,000.00000`). Strip commas before casting.
- **Timestamps**: Master/evaluation tables lack timestamp columns. Models set `public $timestamps = false;` and `$guarded = ['id'];`.

## 2. SAP File to Model Mappings

| Target Model | Database Table | SAP Export File | Purpose / Criteria | Key Column Mappings |
| :--- | :--- | :--- | :--- | :--- |
| `PurchasingListPo` | `purchasing_list_po` | `(EVALUASI) LIST GRPO.xls` | PO Master (supplier selection & active months) | `Customer/Supplier Code` &rarr; `supplier_code`<br>`Customer/Supplier Name` &rarr; `supplier_name`<br>`Posting Date` &rarr; `posting_date` (`d.m.y`) |
| `PurchasingVendorClaim` | `purchasing_vendor_claim` | `(EVALUASI) VENDOR CLAIM.xls` | Kriteria 1: Kualitas Barang & Kemasan | `Vendor Code` &rarr; `vendor_code`<br>`Incoming Date` &rarr; `incoming_date`<br>`Stop Line` &rarr; `customer_stopline` |
| `PurchasingVendorAccuracyGood` | `purchasing_vendor_accuracy_good` | `(EVALUASI) VENDOR ACCURACY GOOD.xls` | Kriteria 2: Ketepatan Kuantitas | `Delivery Qty` &rarr; `delivery_quantity`<br>`Received Qty` &rarr; `received_quantity`<br>`Close Status` &rarr; `close_status` |
| `PurchasingVendorOntimeDelivery` | `purchasing_vendor_ontime_delivery` | `(EVALUASI) VENDOR ONTIME DELIVERY.xls` | Kriteria 3: Ketepatan Waktu Pengiriman | `Request Date` &rarr; `request_date`<br>`Actual Date` &rarr; `actual_date`<br>`Actual Incoming Qty` &rarr; `actual_incoming_quantity` |
| `PurchasingVendorUrgentRequest` | `purchasing_vendor_urgent_request` | `(EVALUASI) VENDOR URGENT REQUEST.xls` | Kriteria 4: Kerjasama Permintaan Mendadak | `PO No` &rarr; `po_no`<br>`Incoming Date` &rarr; `incoming_date`<br>`Special Price` &rarr; `special_price` |
| `PurchasingVendorClaimResponse` | `purchasing_vendor_claim_response` | `(EVALUASI) VENDOR CLAIM RESPON.xls` | Kriteria 5: Respon Klaim (CPAR) | `Vendor Claim Code` &rarr; `vendor_claim_code`<br>`CPAR Sent Date` &rarr; `cpar_sent_date`<br>`CPAR Respon Date` &rarr; `cpar_response_date` |
| `PurchasingVendorListCertificate` | `purchasing_vendor_list_certificate` | `(EVALUASI) LIST VENDOR.xls` | Kriteria 6: Sertifikasi ISO / IATF | `BP Code` &rarr; `vendor_code`<br>`ISO 9001:2015 NUM` &rarr; `iso_9001_doc`<br>`IATF 16949:2016 NO` &rarr; `iatf_16949_doc` |
| `PurchasingContact` | `purchasing_contacts` | `(EVALUASI) PURCHASING CONTACT.xls` | Vendor Contacts & Purchasing PIC (`p_member`) | `BP Code` &rarr; `vendor_code`<br>`FirstWord` &rarr; `p_member` (null if '-No')<br>`Contact Person Name` &rarr; `persontocontact` |

## 3. Purchasing Contact & PIC Ingestion

From `(EVALUASI) PURCHASING CONTACT.xls`:
- **`p_member` (Purchasing PIC)**: Extracted from `FirstWord`. Values prefixed with `'-'` (e.g. `'-No'`) or blank become `null`; valid names (e.g. `ANDRIANI`, `DIAN`) are stored trimmed.
- **`persontocontact` (Vendor Contact)**: Extracted from `Contact Person Name`.
- In views (`foremind_detail.blade.php`, `supplier_detail.blade.php`), display `$contact->persontocontact` in `ATT` and `$contact->p_member ?? '-'` in `FR` / `PIC`.

## 4. Ingestion Pipeline & Access Control

- **Strategy**: Truncate-and-replace (`DELETE FROM <table>` in `DB::transaction()` with 1,000-row batch inserts).
- **Log Touch**: `PurchasingUpdateLog::updateOrCreate(['id' => 1], ['updated_at' => now()])`.
- **Authorization**: Restricted to `super-admin` or `admin`.
- **Route**: `POST /purc/evaluationsupplier/import` &rarr; `PurchasingSupplierEvaluationController@import`.

## 5. Scoring Logic (`SupplierScoringService`)

- **Kriteria 1 (Kualitas Barang)**: Max 20 pts. Deduct 5 pts per monthly claim (min 0).
- **Customer Stopline**: Max 10 pts. Deduct 5 pts per claim with `customer_stopline == 'Yes'`.
- **Kriteria 2 (Kuantitas)**: Max 20 pts. Deduct 5 pts per discrepancy record.
- **Kriteria 3 (Waktu Pengiriman)**: Max 20 pts. Deduct 5 pts per late delivery record.
- **Kriteria 4 (Permintaan Mendadak)**: Max 10 pts (10 if same date with normal price, 5 if special price).
- **Kriteria 5 (Respon Klaim / CPAR)**: Max 10 pts (1-3 days: 10 pts, 4-5 days: 5 pts, >5 days: 0 pts).
- **Kriteria 6 (Sertifikasi)**: Max 10 pts (IATF 16949: 10 pts, ISO 9001/14001: 5 pts, none: 0 pts).
- **Grades**: $\ge 81 \rightarrow$ **A** (Diteruskan), $\ge 61 \rightarrow$ **B** (Dipertahankan), $< 61 \rightarrow$ **C** (Monitoring 3 bulan).
