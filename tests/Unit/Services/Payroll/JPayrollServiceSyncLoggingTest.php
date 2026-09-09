<?php

namespace Tests\Unit\Services\Payroll;

use App\Models\ImportJob;
use App\Services\JPayrollService;
use App\Services\Payroll\PayrollSyncOrchestrator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class JPayrollServiceSyncLoggingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sync_automatically_creates_and_completes_import_job(): void
    {
        $this->app->bind(PayrollSyncOrchestrator::class, fn () => new PayrollSyncOrchestrator([]));

        $service = app(JPayrollService::class);

        $result = $service->syncEmployeesLeaveAndAttendanceFromApi(
            companyArea: '10000',
            year: 2026,
            fromDate: '2026-08-01',
            toDate: '2026-08-10',
            source: 'scheduled',
        );

        $this->assertTrue($result['success']);

        $job = ImportJob::where('type', 'jpayroll_sync')->latest('id')->first();
        $this->assertNotNull($job);
        $this->assertSame('completed', $job->status);
        $this->assertNull($job->error);
        $this->assertNotNull($job->finished_at);
        $this->assertSame('scheduled', $job->results_snapshot['source']);
        $this->assertSame('2026-08-01', $job->results_snapshot['parameters']['date_range']['resolved_from']);
        $this->assertSame('2026-08-10', $job->results_snapshot['parameters']['date_range']['resolved_to']);
    }

    public function test_sync_records_failure_in_import_job_on_invalid_date_range(): void
    {
        $this->app->bind(PayrollSyncOrchestrator::class, fn () => new PayrollSyncOrchestrator([]));

        $service = app(JPayrollService::class);

        // Intentionally inverted dates
        $result = $service->syncEmployeesLeaveAndAttendanceFromApi(
            companyArea: '10000',
            year: 2026,
            fromDate: '2026-08-20',
            toDate: '2026-08-10',
            source: 'scheduled',
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid range', $result['message']);

        $job = ImportJob::where('type', 'jpayroll_sync')->latest('id')->first();
        $this->assertNotNull($job);
        $this->assertSame('failed', $job->status);
        $this->assertStringContainsString('Invalid range', $job->error);
        $this->assertNotNull($job->finished_at);
    }
}
