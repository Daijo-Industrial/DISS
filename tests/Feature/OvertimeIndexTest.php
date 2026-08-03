<?php

namespace Tests\Feature;

use App\Domain\Overtime\Models\OvertimeForm;
use App\Domain\Overtime\Models\OvertimeFormDetail;
use App\Livewire\Overtime\Index;
use Livewire\Livewire;
use Tests\TestCase;

class OvertimeIndexTest extends TestCase
{
    public function test_it_identifies_and_excludes_high_intensity_sessions_exceeding_12_hours()
    {
        // Form 1: 13 hours session (>12h warning)
        $form1 = \Database\Factories\OvertimeFormFactory::new()->create();
        \Database\Factories\OvertimeFormDetailFactory::new()->create([
            'header_id' => $form1->id,
            'NIK' => '10001',
            'name' => 'John Doe',
            'overtime_date' => '2026-08-01',
            'start_date' => '2026-08-01',
            'start_time' => '08:00:00',
            'end_date' => '2026-08-01',
            'end_time' => '21:30:00', // 13.5 hours, 0 break = >12h
            'break' => 0,
        ]);

        // Form 2: 4 hours session (normal)
        $form2 = \Database\Factories\OvertimeFormFactory::new()->create();
        \Database\Factories\OvertimeFormDetailFactory::new()->create([
            'header_id' => $form2->id,
            'NIK' => '10002',
            'name' => 'Jane Smith',
            'overtime_date' => '2026-08-01',
            'start_date' => '2026-08-01',
            'start_time' => '08:00:00',
            'end_date' => '2026-08-01',
            'end_time' => '12:00:00', // 4 hours
            'break' => 0,
        ]);

        $component = Livewire::test(Index::class)
            ->set('selectedIds', [(string) $form1->id, (string) $form2->id])
            ->call('loadSnapshot');

        // Assert warning populated for form 1
        $component->assertSet('highIntensityFormIds', [$form1->id]);
        $component->assertSee('1 sessions exceed 12 hours of duration.');
        $component->assertSee('Exclude 1 High-Duration Form');

        // Call excludeHighIntensityForms
        $component->call('excludeHighIntensityForms');

        // Assert form 1 was removed from selection, only form 2 remains
        $component->assertSet('selectedIds', [(string) $form2->id]);
        $component->assertSet('highIntensityFormIds', []);
        $component->assertDontSee('sessions exceed 12 hours of duration.');
    }
}
