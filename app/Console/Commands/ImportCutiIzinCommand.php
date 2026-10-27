<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Attendance\Services\ImportCutiIzinService;
use Illuminate\Console\Command;
use Throwable;

class ImportCutiIzinCommand extends Command
{
    protected $signature = 'attendance:import-cuti-izin 
                            {file? : Path to the Cuti Izin Excel file (defaults to Cuti Izin September 2026.xls)}
                            {--month= : Override month (1-12)}
                            {--year= : Override year}';

    protected $description = 'Import Cuti Izin from JPayroll summary Excel file and update employee attendance and evaluation data';

    public function handle(ImportCutiIzinService $service): int
    {
        $file = $this->argument('file') ?? base_path('Cuti Izin September 2026.xls');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $month = $this->option('month') ? (int) $this->option('month') : null;
        $year = $this->option('year') ? (int) $this->option('year') : null;

        $this->info("Importing Cuti Izin from: {$file}...");

        try {
            $result = $service->import($file, $month, $year);

            $this->info("✓ Import successful for period: {$result['period']}");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Period', $result['period']],
                    ['Total Rows in Excel', $result['total_processed']],
                    ['Employees with Izin Updated', $result['updated_employees']],
                    ['Evaluation Records Recalculated', $result['recalculated_evaluations']],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Failed to import Cuti Izin: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
