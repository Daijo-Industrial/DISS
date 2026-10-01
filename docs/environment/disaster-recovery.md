# DISS Disaster Recovery & Backup Strategy (Windows 11 + IIS)

## 1. Executive Summary & Recovery Objectives

This document establishes the standard disaster recovery (DR) procedures and automated backup routines for the **Daijo Industrial Support System (DISS)** running on **Windows 11 with IIS 10, FastCGI PHP 8.3, and MySQL 8.x**.

### Recovery Objectives
- **RPO (Recovery Point Objective)**: **24 hours** (full daily backups) or **1 hour** (with MySQL binary logging).
- **RTO (Recovery Time Objective) — Minor Incident**: **< 15 minutes** (database corruption rollback, accidental soft-delete restoration, storage recovery).
- **RTO (Recovery Time Objective) — Major Incident**: **< 2 hours** (complete bare-metal server replacement, IIS rebuild, full data restore).

### The 3-2-1 Backup Strategy
- **3 Copies of Data**: Production system + Local backup disk (`D:\Backups\DISS\`) + Network NAS / Offsite.
- **2 Different Media Types**: High-speed local SSD/NVMe + Network Storage / External Drive Array.
- **1 Isolated/Air-Gapped Copy**: Network-isolated or immutable snapshot on NAS to survive ransomware.

---

## 2. Critical Application Assets Inventory

| Asset Category | Location | Criticality | Backup Mechanism |
| :--- | :--- | :--- | :--- |
| **MySQL Database** | Port 3306 (`daijo_diss`) | **Highest** | Automated `mysqldump` with `--single-transaction` and ZIP compression |
| **Document Storage** | `C:\inetpub\wwwroot\DISS\storage\app\public` | **Highest** | Contains **digitally signed PO PDFs**, invoices, scans. Compressed to ZIP |
| **Environment Secrets** | `C:\inetpub\wwwroot\DISS\.env` | **Critical** | Contains `APP_KEY` and DB credentials. Secured in offline password vault |
| **IIS Configuration** | `C:\inetpub\wwwroot\DISS\public\web.config` | **High** | Reverb WebSocket reverse proxy, rewrite rules, 100MB payload limits. Git tracked |

---

## 3. Automated Daily Backup Script (`backup-diss.ps1`)

Place this script at `C:\Scripts\DISS_Backup\backup-diss.ps1`:

```powershell
param (
    [string]$AppRoot       = "C:\inetpub\wwwroot\DISS",
    [string]$BackupRoot    = "D:\Backups\DISS",
    [string]$NasPath       = "\\192.168.1.50\Backups\DISS",
    [string]$MySqlDumpPath = "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe",
    [string]$DbName        = "daijo_diss",
    [string]$DbUser        = "root",
    [string]$DbPassword    = "YourProductionPasswordHere",
    [int]$RetentionDays    = 14
)

$ErrorActionPreference = "Stop"
$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$DailyBackupDir = Join-Path $BackupRoot $Timestamp

Write-Host "[$(Get-Date)] Starting DISS Backup Routine..."
if (-not (Test-Path $DailyBackupDir)) {
    New-Item -ItemType Directory -Path $DailyBackupDir -Force | Out-Null
}

try {
    # 1. Backup MySQL Database
    $DbBackupFile = Join-Path $DailyBackupDir "diss_db_$Timestamp.sql"
    Write-Host "--> Exporting MySQL database $DbName..."
    & $MySqlDumpPath --user=$DbUser --password=$DbPassword `
        --single-transaction --quick --routines --triggers `
        --result-file="$DbBackupFile" $DbName

    Compress-Archive -Path "$DbBackupFile" -DestinationPath "$DbBackupFile.zip"
    Remove-Item -Path "$DbBackupFile" -Force

    # 2. Backup Storage (Signed PDFs and User Files)
    Write-Host "--> Compressing storage/app/public..."
    $StorageSource = Join-Path $AppRoot "storage\app\public"
    $StorageZip = Join-Path $DailyBackupDir "diss_storage_$Timestamp.zip"
    if (Test-Path $StorageSource) {
        Compress-Archive -Path "$StorageSource\*" -DestinationPath $StorageZip -CompressionLevel Optimal
    }

    # 3. Backup .env Configuration
    Write-Host "--> Backing up .env file..."
    $EnvSource = Join-Path $AppRoot ".env"
    if (Test-Path $EnvSource) {
        Copy-Item -Path $EnvSource -Destination (Join-Path $DailyBackupDir ".env_$Timestamp.bak") -Force
    }

    # 4. Offsite / NAS Replication
    if (Test-Path $NasPath) {
        Write-Host "--> Replicating backup to NAS: $NasPath..."
        Copy-Item -Path $DailyBackupDir -Destination $NasPath -Recurse -Force
    } else {
        Write-Warning "NAS share unreachable. Kept on local backup drive."
    }

    # 5. Local Retention Pruning
    Write-Host "--> Pruning local backups older than $RetentionDays days..."
    $CutoffDate = (Get-Date).AddDays(-$RetentionDays)
    Get-ChildItem -Path $BackupRoot -Directory | Where-Object { $_.CreationTime -lt $CutoffDate } | ForEach-Object {
        Remove-Item $_.FullName -Recurse -Force
    }
    Write-Host "[$(Get-Date)] DISS Backup completed successfully."
} catch {
    Write-Error "Backup failure occurred: $_"
    exit 1
}
```

---

## 4. Scheduling via Windows Task Scheduler

Register the task to run daily at midnight under the `SYSTEM` account:

```powershell
$Action = New-ScheduledTaskAction `
    -Execute "powershell.exe" `
    -Argument "-NoProfile -ExecutionPolicy Bypass -File C:\Scripts\DISS_Backup\backup-diss.ps1"

$Trigger = New-ScheduledTaskTrigger -Daily -At "00:00"

$Principal = New-ScheduledTaskPrincipal `
    -UserId "NT AUTHORITY\SYSTEM" `
    -LogonType ServiceAccount `
    -RunLevel Highest

Register-ScheduledTask `
    -TaskName "DISS_Daily_Backup" `
    -Action $Action `
    -Trigger $Trigger `
    -Principal $Principal `
    -Description "Daily automated backup of DISS database and document storage."
```

---

## 5. Recovery Runbooks

### Runbook A: Database Restoration
1. Enable maintenance mode:
   ```powershell
   cd C:\inetpub\wwwroot\DISS
   php artisan down --secret="diss-recovery-mode"
   ```
2. Unpack backup:
   ```powershell
   Expand-Archive -Path "D:\Backups\DISS\YYYYMMDD_HHMMSS\diss_db_YYYYMMDD_HHMMSS.sql.zip" -DestinationPath "D:\Backups\DISS\temp"
   ```
3. Import SQL:
   ```powershell
   & "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -p daijo_diss < "D:\Backups\DISS\temp\diss_db_YYYYMMDD_HHMMSS.sql"
   ```
4. Clear cache and resume:
   ```powershell
   php artisan optimize:clear
   php artisan up
   Remove-Item "D:\Backups\DISS\temp" -Recurse -Force
   ```

### Runbook B: Storage Files Restoration
1. Extract into storage folder:
   ```powershell
   Expand-Archive -Path "D:\Backups\DISS\YYYYMMDD_HHMMSS\diss_storage_YYYYMMDD_HHMMSS.zip" -DestinationPath "C:\inetpub\wwwroot\DISS\storage\app\public" -Force
   ```
2. Grant IIS Worker permissions:
   ```powershell
   icacls "C:\inetpub\wwwroot\DISS\storage" /grant "IIS_IUSRS:(OI)(CI)F" /T
   icacls "C:\inetpub\wwwroot\DISS\bootstrap\cache" /grant "IIS_IUSRS:(OI)(CI)F" /T
   ```
3. Re-link public storage:
   ```powershell
   php artisan storage:link
   ```

### Runbook C: Bare-Metal Server Rebuild Checklist
1. **OS & IIS**: Install Windows 11, enable IIS (CGI, WebSocket Protocol, URL Rewrite 2.1, ARR).
2. **PHP & MySQL**: Install PHP 8.3 with FastCGI mapping in IIS. Install MySQL 8.x.
3. **Repository**: Clone DISS to `C:\inetpub\wwwroot\DISS`, restore `.env` from secure vault, run `composer install --no-dev --optimize-autoloader`.
4. **Data Import**: Restore MySQL dump and storage directory from NAS.
5. **Background Services**: Configure Task Scheduler for `php artisan schedule:run` and NSSM for queue worker and Reverb.
6. **Cache & Test**: Run `php artisan optimize:clear && php artisan config:cache`.
