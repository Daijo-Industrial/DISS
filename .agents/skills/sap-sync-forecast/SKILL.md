---
name: sap-sync-forecast
description: Domain knowledge, architecture, pipeline execution, vendor code preservation rules, and troubleshooting runbooks for SAP Data Synchronization (sap:sync) and Purchasing Forecast Post-Processing in DISS.
---

# SAP Data Synchronization & Forecast Post-Processing

## 1. Architecture Overview

Ingests production, inventory, BOM, and demand data from external SAP API endpoints into MySQL staging tables, then runs BOM explosion and material demand prediction.

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

## 2. The 5 Synchronization Groups

| Group | Method in `SapSyncService` | Staging Table(s) | Description |
| :--- | :--- | :--- | :--- |
| **BOM WIP Group** | `syncBomWipGroup($date)` | `sap_fct_bom_wip_first`<br>`sap_fct_bom_wip_second`<br>`sap_fct_bom_wip_third`<br>`sap_fct_bom_wip`<br>`sap_fct_bom_wip_fgcode` | Ingests 3 BOM WIP levels, populates level tables, executes `processBomWipUnion()` to combine and extract unique FG codes. |
| **Inventory MTR Union** | `syncInventoryMtrUnion($date)` | `sap_fct_inventory_mtr` | Unions direct raw materials, Level 1 semi, Level 2 WIP, Level 3 WIP. Preserves SAP vendor codes. |
| **Inventory FG Union** | `syncInventoryFgUnion($date)` | `sap_fct_inventory_fg` | Unions finished goods inventory across production stages. Upserts on `item_code`. |
| **Line Production** | `syncLineProductionUnion($date)` | `sap_fct_lineproductions` | Unions assembly and production line assignments across WIP stages. |
| **Forecast Demands** | `syncForecastGroup($date)` | `sap_forecast` | Ingests monthly sales demand forecasts by FG item and customer. |

## 3. Vendor Code Preservation Rules (CRITICAL)

SAP uses distinct `vendor_code` prefixes for purchasing and tax categorization:
- **`VML...`**: Vendor Material Local (direct raw materials).
- **`KML...`**: Konsinyasi Material Local (consignment / Kawasan Berikat).
- **`VNL...`**: Vendor Non-material Local (indirect supplies / general expenses).
- **`KNL...`**: Konsinyasi Non-material.
- **`VMI...`**: Vendor Material Import.
- **`D...`**: Internal subsidiaries / sister plants.

### Ingestion Rules
1. **SAP System of Record**: When the SAP API returns a non-empty `VendorCode`, **NEVER overwrite it** with a vendor-name lookup. The distinction between `VML...` and `KML...` is critical for purchasing and accounting.
2. **Unambiguous Fallback Only**: If `VendorCode` is empty/null from SAP, fall back to `purchasing_contacts` ONLY if that vendor name maps to **exactly 1 unique `vendor_code`**. If ambiguous (e.g. `ASIA HODA` having both `VML...` and `VNL...`), preserve as-is without assigning an arbitrary code.
3. **Composite Identity**: Services and `/foremind-detail` treat `(vendor_code, vendor_name)` as a composite identity.

## 4. Post-Processing Pipeline (`ForecastPostProcessingJob`)

### A. Forecast Data Explosion (`ForecastDataExplosionService`)
Reads `sap_forecast` and resolves 5 BOM cases into `foremind_final` within a `DB::transaction()`:
1. Finished goods without WIP (direct raw materials joined to `sap_fct_inventory_mtr`).
2. Level 0 WIP with raw materials.
3. Level 1 BOM WIP (`sap_fct_bom_wip_first`).
4. Level 2 BOM WIP (`sap_fct_bom_wip_second`).
5. Level 3 BOM WIP (`sap_fct_bom_wip_third`).

### B. Material Prediction Calculation (`ForecastMaterialPredictionService`)
Streams `foremind_final` via Eloquent `cursor()`, aggregates monthly quantities grouped by `[material_code][material_name][customer][item_no][UOM][quantity_material][vendor_code][vendor_name]`, and atomically populates `forecast_material_predictions`.

## 5. Execution Modes & Scheduled Crons

```bash
# Synchronous run (all 5 groups, then post-processing)
php artisan sap:sync

# Asynchronous batch queue (dispatches Bus::batch to queue worker)
php artisan sap:sync --queue

# Sync a single group
php artisan sap:sync --endpoint=sap_fct_bom_wip
php artisan sap:sync --endpoint=sap_fct_inventory_mtr

# Automated Crons (app/Console/Kernel.php)
# 12:00 WIB & 18:00 WIB: php artisan sap:sync --endpoint=all >> storage/logs/sap-sync.log

# Verification tests
docker exec diss-laravel.test-1 php artisan test --filter=SapSyncServiceTest
docker exec diss-laravel.test-1 php artisan test --filter=ForecastPipelineTest
```

## 6. Troubleshooting

### Vendor Code Missing from `/foremind-detail`
1. Check staging table:
   ```sql
   SELECT vendor_code, vendor_name, count(*) FROM sap_fct_inventory_mtr WHERE vendor_code = 'VML0000223' GROUP BY vendor_code, vendor_name;
   ```
2. If present in staging but missing in predictions, run manual Tinker post-processing:
   ```php
   app(\App\Services\Forecast\ForecastDataExplosionService::class)->explodeForecastData();
   app(\App\Services\Forecast\ForecastMaterialPredictionService::class)->generatePredictions();
   ```
3. Verify target table:
   ```sql
   SELECT vendor_code, vendor_name, count(*) FROM forecast_material_predictions WHERE vendor_code = 'VML0000223' GROUP BY vendor_code, vendor_name;
   ```
