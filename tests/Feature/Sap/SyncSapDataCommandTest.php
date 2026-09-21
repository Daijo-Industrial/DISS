<?php

namespace Tests\Feature\Sap;

use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SyncSapDataCommandTest extends TestCase
{
    public function test_command_queue_flag_dispatches_bus_batch(): void
    {
        Bus::fake();

        $this->artisan('sap:sync', ['--queue' => true, '--date' => '2026-09-01'])
            ->assertExitCode(0);

        Bus::assertBatched(function ($batch) {
            return $batch->name === 'SAP Sync Batch (2026-09-01)' &&
                   $batch->jobs->count() === 5;
        });
    }
}
