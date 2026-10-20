<?php

namespace App\Console\Commands;

use App\Models\InspectionForm\InspectionReport;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerifyMesBridgeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mes:verify-bridge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify live database bridging and connectivity between DISS and Daijo_MES';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connectionName = config('database.inspection_connection', 'mes_mysql');

        $this->info('====================================================');
        $this->info('  DISS <-> Daijo_MES Bridge Connectivity Verifier   ');
        $this->info('====================================================');
        $this->line("Target Connection : <comment>{$connectionName}</comment>");
        $this->line('Host              : <comment>' . config("database.connections.{$connectionName}.host") . '</comment>');
        $this->line('Port              : <comment>' . config("database.connections.{$connectionName}.port") . '</comment>');
        $this->line('Database          : <comment>' . config("database.connections.{$connectionName}.database") . '</comment>');
        $this->newLine();

        // 1. Raw PDO Connectivity Check
        $this->line('1. Checking raw PDO connection...');
        $startTime = microtime(true);
        try {
            DB::connection($connectionName)->getPdo();
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            $this->info("   ✔ Connected successfully! Latency: {$latency} ms");
        } catch (Exception $e) {
            $this->error('   ✘ Failed to connect: ' . $e->getMessage());

            return Command::FAILURE;
        }

        // 2. Schema / Table Existence Check
        $this->line('2. Verifying inspection tables in target database...');
        $tables = [
            'inspection_reports',
            'detail_inspection_reports',
            'first_inspections',
            'second_inspections',
            'inspection_dimensions',
            'inspection_defects',
            'inspection_problems',
            'inspection_packagings',
            'inspection_samplings',
            'inspection_quantities',
            'inspection_judgements',
        ];

        $missing = [];
        foreach ($tables as $table) {
            if (! Schema::connection($connectionName)->hasTable($table)) {
                $missing[] = $table;
            }
        }

        if (! empty($missing)) {
            $this->error('   ✘ Missing tables in Daijo_MES: ' . implode(', ', $missing));

            return Command::FAILURE;
        }
        $this->info('   ✔ All 11 inspection tables verified present in Daijo_MES.');

        // 3. Live Round-Trip Write & Read Verification
        $this->line('3. Testing end-to-end Eloquent write & read roundtrip...');
        $docNumber = 'VERIFY-BRIDGE-' . uniqid();

        try {
            // Write via DISS Model
            $report = InspectionReport::create([
                'document_number' => $docNumber,
                'customer' => 'PT Verification Initial',
                'inspection_date' => date('Y-m-d'),
                'part_number' => 'VERIFY-PART-001',
                'part_name' => 'Bridge Test Widget',
                'weight' => 10.0,
                'weight_uom' => 'g',
                'material' => 'ABS',
                'color' => 'Black',
                'tool_number_or_cav_number' => 'T1',
                'machine_number' => 'MC-01',
                'shift' => 1,
                'operator' => 'DISS Verifier',
            ]);
            $this->line("   ✔ Wrote record [{$docNumber}] with ID {$report->id}.");

            // Query directly from connection
            $row = DB::connection($connectionName)
                ->table('inspection_reports')
                ->where('document_number', $docNumber)
                ->first();

            if (! $row || $row->customer !== 'PT Verification Initial') {
                throw new Exception('Record could not be verified in target database.');
            }
            $this->line('   ✔ Verified record directly in Daijo_MES table.');

            // Update in target connection and read via DISS Eloquent
            DB::connection($connectionName)
                ->table('inspection_reports')
                ->where('document_number', $docNumber)
                ->update(['customer' => 'PT Verification Updated In MES']);

            $refreshed = InspectionReport::where('document_number', $docNumber)->first();
            if (! $refreshed || $refreshed->customer !== 'PT Verification Updated In MES') {
                throw new Exception('Data update in MES was not reflected in DISS model.');
            }
            $this->line('   ✔ Bi-directional read-after-update confirmed.');

            // Clean up
            $report->delete();
            $this->line('   ✔ Cleaned up test record.');
        } catch (Exception $e) {
            $this->error('   ✘ Roundtrip verification failed: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('====================================================');
        $this->info('  ALL BRIDGE CONNECTIVITY CHECKS PASSED!            ');
        $this->info('  DISS and Daijo_MES are fully connected.           ');
        $this->info('====================================================');

        return Command::SUCCESS;
    }
}
