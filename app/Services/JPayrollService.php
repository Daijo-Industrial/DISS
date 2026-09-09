<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ImportJob;
use App\Services\Payroll\Contracts\JPayrollClientContract;
use App\Services\Payroll\PayrollSyncOrchestrator;
use App\Services\Payroll\Sync\DateRangeResolver;
use App\Services\Payroll\Sync\EmployeeSync;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class JPayrollService
{
    public function __construct(
        private readonly JPayrollClientContract  $client,
        private readonly PayrollSyncOrchestrator $orchestrator,
        private readonly DateRangeResolver       $dateRangeResolver,
        private readonly EmployeeSync            $employeeSync,
        private readonly \App\Services\Payroll\Sync\AnnualLeaveSync $annualLeaveSync,
        private readonly \App\Services\Payroll\Sync\AttendanceSync $attendanceSync,
    ) {}

    public function syncEmployeesLeaveAndAttendanceFromApi(
        string $companyArea = '10000',
        ?int $year = null,
        CarbonImmutable|string|null $fromDate = null,
        CarbonImmutable|string|null $toDate = null,
        ?int $importJobId = null,
        string $source = 'scheduled',
    ): array {
        $tz    = config('payroll.timezone', 'Asia/Jakarta');
        $range = $this->dateRangeResolver->resolve($fromDate, $toDate, $tz);
        $year ??= $range['to']->year;

        // Resolve or create an ImportJob audit record
        $importJob = $importJobId ? ImportJob::find($importJobId) : null;
        if (! $importJob) {
            try {
                $importJob = ImportJob::create([
                    'type'             => 'jpayroll_sync',
                    'status'           => 'running',
                    'started_at'       => now(),
                    'results_snapshot' => [
                        'phases'     => ['employees', 'annual_leave', 'attendance'],
                        'source'     => $source,
                        'parameters' => [
                            'company_area' => $companyArea,
                            'year'         => $year,
                            'date_range'   => [
                                'requested_from' => $fromDate instanceof CarbonImmutable ? $fromDate->toDateString() : $fromDate,
                                'requested_to'   => $toDate instanceof CarbonImmutable ? $toDate->toDateString() : $toDate,
                                'resolved_from'  => $range['from']->format('Y-m-d'),
                                'resolved_to'    => $range['to']->format('Y-m-d'),
                            ],
                        ],
                    ],
                ]);
            } catch (Throwable $e) {
                Log::warning('Could not create ImportJob record for sync: ' . $e->getMessage());
            }
        }

        Log::info('JPayroll sync initiated', [
            'source'        => $source,
            'company_area'  => $companyArea,
            'year'          => $year,
            'resolved_from' => $range['from']->toDateString(),
            'resolved_to'   => $range['to']->toDateString(),
            'import_job_id' => $importJob?->id,
        ]);

        if ($range['from']->gt($range['to'])) {
            $msg = "Invalid range: {$range['from']->toDateString()} > {$range['to']->toDateString()}";
            Log::error('JPayroll sync validation failed: ' . $msg);

            $this->updateImportJob($importJob, false, $msg);

            return [
                'success' => false,
                'message' => $msg,
            ];
        }

        $result = $this->orchestrator->run(
            $companyArea,
            $year,
            $range['from'],
            $range['to'],
        );

        $this->updateImportJob($importJob, $result['success'], $result['success'] ? null : ($result['message'] ?? 'Sync failed'));

        if ($result['success']) {
            Log::info('JPayroll sync completed successfully', [
                'import_job_id' => $importJob?->id,
                'source'        => $source,
            ]);
        } else {
            Log::error('JPayroll sync failed: ' . ($result['message'] ?? 'Unknown error'), [
                'import_job_id' => $importJob?->id,
                'source'        => $source,
            ]);
        }

        return $result;
    }

    private function updateImportJob(?ImportJob $importJob, bool $success, ?string $error = null): void
    {
        if (! $importJob) {
            return;
        }

        try {
            $snapshot = $importJob->results_snapshot ?? [];
            $snapshot['completed_at'] = now('Asia/Jakarta')->toDateTimeString();

            $importJob->update([
                'status'           => $success ? 'completed' : 'failed',
                'error'            => $error,
                'finished_at'      => now(),
                'results_snapshot' => $snapshot,
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to update ImportJob audit record: ' . $e->getMessage());
        }
    }

    /**
     * Return a lightweight preview of what each selected phase will do.
     *
     * @param  string[]  $phases  Subset of: 'employees', 'annual_leave', 'attendance'
     * @param  string|null  $fromDate  ISO date (for attendance)
     * @param  string|null  $toDate    ISO date (for attendance)
     */
    public function previewSync(
        string $companyArea = '10000',
        ?int $year = null,
        array $phases = ['employees'],
        ?string $fromDate = null,
        ?string $toDate = null,
    ): array {
        $tz   = config('payroll.timezone', 'Asia/Jakarta');
        $year ??= now($tz)->year;

        try {
            $preview = ['phases' => $phases, 'parameters' => []];

            // --- Employee phase preview ---
            if (in_array('employees', $phases, true)) {
                $employees          = $this->client->getMasterEmployees($companyArea);
                $preview['employees'] = $this->employeeSync->preview($employees);
            }

            // --- Annual leave phase preview ---
            if (in_array('annual_leave', $phases, true)) {
                $leaves = $this->client->getAnnualLeave($companyArea, $year);
                $preview['annual_leave'] = $this->annualLeaveSync->preview($leaves);
            }

            // --- Attendance phase preview ---
            if (in_array('attendance', $phases, true)) {
                $range = $this->dateRangeResolver->resolve($fromDate, $toDate, $tz);
                $attendances = $this->client->getAttendance($companyArea, $range['from'], $range['to']);
                $preview['attendance'] = $this->attendanceSync->preview($attendances);
                
                $preview['parameters']['date_range'] = [
                    'requested_from' => $fromDate,
                    'requested_to'   => $toDate,
                    'resolved_from'  => $range['from']->format('Y-m-d'),
                    'resolved_to'    => $range['to']->format('Y-m-d'),
                ];
            }

            return array_merge(['success' => true], $preview);
        } catch (Throwable $e) {
            Log::error('Preview failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Preview failed: ' . $e->getMessage()];
        }
    }
}
