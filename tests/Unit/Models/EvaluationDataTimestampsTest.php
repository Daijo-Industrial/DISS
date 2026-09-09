<?php

namespace Tests\Unit\Models;

use App\Models\EvaluationData;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EvaluationDataTimestampsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_evaluation_data_model_has_timestamps_enabled(): void
    {
        $evaluation = new EvaluationData();
        $this->assertTrue($evaluation->usesTimestamps());
    }

    public function test_creating_evaluation_data_automatically_sets_created_at_and_updated_at(): void
    {
        $record = EvaluationData::create([
            'NIK' => '12345',
            'Month' => '2026-09-01',
            'dept' => '310',
            'Alpha' => 0,
            'Telat' => 0,
            'Izin' => 0,
            'Sakit' => 0,
        ]);

        $this->assertNotNull($record->created_at);
        $this->assertNotNull($record->updated_at);
        $this->assertSame($record->created_at->toDateTimeString(), $record->updated_at->toDateTimeString());

        // Update and check updated_at advances
        $this->travel(5)->seconds();
        $record->update(['Alpha' => 2]);
        $record->refresh();

        $this->assertTrue($record->updated_at->gt($record->created_at));
    }
}
