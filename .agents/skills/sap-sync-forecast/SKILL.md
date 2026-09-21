---
name: sap-sync-forecast
description: Domain knowledge, architecture, pipeline execution, vendor code preservation rules, and troubleshooting runbooks for SAP Data Synchronization (sap:sync) and Purchasing Forecast Post-Processing in DISS.
---

# SAP Data Synchronization & Forecast Post-Processing

This skill provides comprehensive domain knowledge, architectural reference, data flow details, and operational runbooks for the SAP Data Synchronization (`sap:sync`) and Purchasing Forecast Post-Processing pipelines in the Daijo Industrial System (DISS).

---

## 1. Architecture Overview

The SAP synchronization pipeline ingests production, inventory, BOM, and demand data from external SAP API endpoints, normalizes and stages the records in MySQL staging tables, and then executes BOM explosion and material demand prediction.

```
                      ┌─────────────────────────────────────────┐
                      │          php artisan sap:sync           │
                      └────┬───────────────────────────────┬────┘
                           │ (default synchronous)         │ (--queue flag)
                           ▼                               ▼
             ┌───────────────────────────┐    ┌───────────────────────────┐
             │       SapSyncService      │    │        Bus::batch         │
             │   Atomic DB::transaction  │    │  [5 Concurrent Sap Jobs]  │
             └─────────────┬─────────────┘    └─────────────┬─────────────┘
                           │                                │
                           │                                │ ->then()
                           ▼                                ▼
                      ┌─────────────────────────────────────────┐
                      │        ForecastPostProcessingJob        │
                      └────┬───────────────────────────────┬────┘
                           │                               │
                           ▼                               ▼
       ┌──────────────────────────────────────┐  ┌──────────────────────────────────────┐
       │     ForecastDataExplosionService     │  │  ForecastMaterialPredictionService   │
       │    Explodes forecast -> foremind_    │  │    Calculates monthly predictions    │
       │       final in DB::transaction      │  │      into DB::transaction             │
       └──────────────────────────────────────┘  └──────────────────────────────────────┘
```

---

## 2. The 5 Synchronization Groups

The SAP sync pipeline consolidates 17+ raw SAP API endpoints into 5 primary groups:

| Group | Method in `SapSyncService` | Staging Table(s) | Description |
| :--- | :--- | :--- | :--- |
| **BOM WIP Group** | `syncBomWipGroup($date)` | `sap_fct_bom_wip_first`<br>`sap_fct_bom_wip_second`<br>`sap_fct_bom_wip_third`<br>`sap_fct_bom_wip`<br>`sap_fct_bom_wip_fgcode` | Fetches 3 levels of BOM WIP, populates level tables, then executes `processBomWipUnion()` to combine them and extract unique FG codes. |
| **Inventory MTR Union** | `syncInventoryMtrUnion($date)` | `sap_fct_inventory_mtr` | Unions 4 streams: direct raw materials, Level 1 semi, Level 2 WIP, Level 3 WIP. Preserves SAP vendor codes. |
| **Inventory FG Union** | `syncInventoryFgUnion($date)` | `sap_fct_inventory_fg` | Unions finished goods inventory across production stages. Upserts on `item_code`. |
| **Line Production** | `syncLineProductionUnion($date)` | `sap_fct_lineproductions` | Unions assembly and production line assignments across WIP stages. |
| **Forecast Demands** | `syncForecastGroup($date)` | `sap_forecast` | Ingests monthly sales demand forecasts by FG item and customer. |

---

## 3. Vendor Code Preservation Rules (CRITICAL)

In SAP, vendors have multiple distinct `vendor_code` prefixes for different purchasing and taxation workflows:
- **`VML...`**: Vendor Material Local (direct purchasing of raw materials).
- **`KML...`**: Konsinyasi Material Local (consignment purchasing / Kawasan Berikat).
- **`VNL...`**: Vendor Non-material Local (indirect supplies / general expenses).
- **`KNL...`**: Konsinyasi Non-material.
- **`VMI...`**: Vendor Material Import.
- **`D...`**: Daijo internal subsidiaries / sister plants.

### Strict Rules for Processing `vendor_code`:
1. **SAP is the System of Record**: When the SAP API returns a non-empty `VendorCode`, **NEVER overwrite it** with a vendor-name lookup. The distinction between `VML...` and `KML...` is critical for purchasing PICs and accounting.
2. **Unambiguous Fallback Only**: If `VendorCode` is empty/null from SAP, only fall back to `purchasing_contacts` if that vendor name maps to **exactly 1 unique `vendor_code`**. If a vendor name maps to multiple codes in `purchasing_contacts` (e.g. `ASIA HODA` having both `VML0000223` and `VNL0000957`), do NOT assign an arbitrary code; keep it as-is.
3. **Multi-Code Partitioning**: Downstream services (`ForecastMaterialPredictionService`) and views (`/foremind-detail`) treat `(vendor_code, vendor_name)` as a composite identity. Users must be able to select and print forecasts for `VML...` and `KML...` independently.

---

## 4. Post-Processing Pipeline

Post-processing is orchestrated by `ForecastPostProcessingJob` and executed by two dedicated domain services:

### A. Forecast Data Explosion (`ForecastDataExplosionService`)
- Reads sales demand from `sap_forecast`.
- Evaluates 5 BOM cases:
  1. Finished goods without WIP (direct raw materials joined to `sap_fct_inventory_mtr`).
  2. Level 0 WIP with raw materials.
  3. Level 1 BOM WIP (`sap_fct_bom_wip_first`).
  4. Level 2 BOM WIP (`sap_fct_bom_wip_second`).
  5. Level 3 BOM WIP (`sap_fct_bom_wip_third`).
- Atomically populates `foremind_final` inside a `DB::transaction()`.

### B. Material Prediction Calculation (`ForecastMaterialPredictionService`)
- Streams `foremind_final` records via Eloquent `cursor()`.
- Groups quantities by `[material_code][material_name][customer][item_no][UOM][quantity_material][vendor_code][vendor_name]`.
- Aggregates forecast requirements by month (e.g., `2026-09`, `2026-10`, `2026-11`, etc.).
- Atomically replaces `forecast_material_predictions` within a `DB::transaction()`.

---

## 5. Execution Modes & Scheduled Crons

### CLI Commands
```bash
# Synchronous run (runs all 5 groups, then dispatches post-processing)
php artisan sap:sync

# Asynchronous batch queue (dispatches Bus::batch to queue worker)
php artisan sap:sync --queue

# Sync a single endpoint/group
php artisan sap:sync --endpoint=sap_fct_bom_wip
php artisan sap:sync --endpoint=sap_fct_inventory_mtr
```

### Automated Crons (`app/Console/Kernel.php`)
- **12:00 WIB**: `php artisan sap:sync --endpoint=all`
- **18:00 WIB**: `php artisan sap:sync --endpoint=all`
Output logged to `storage/logs/sap-sync.log`.

---

## 6. Common Troubleshooting & Runbooks

### Vendor Code Missing from `/foremind-detail` Dropdown
1. Check `sap_fct_inventory_mtr`:
   ```sql
   SELECT vendor_code, vendor_name, count(*) FROM sap_fct_inventory_mtr WHERE vendor_code = 'VML0000223' GROUP BY vendor_code, vendor_name;
   ```
2. If present in `sap_fct_inventory_mtr` but missing in `forecast_material_predictions`:
   Run post-processing manually via Tinker:
   ```php
   app(\App\Services\Forecast\ForecastDataExplosionService::class)->explodeForecastData();
   app(\App\Services\Forecast\ForecastMaterialPredictionService::class)->generatePredictions();
   ```
3. Check `forecast_material_predictions`:
   ```sql
   SELECT vendor_code, vendor_name, count(*) FROM forecast_material_predictions WHERE vendor_code = 'VML0000223' GROUP BY vendor_code, vendor_name;
   ```
