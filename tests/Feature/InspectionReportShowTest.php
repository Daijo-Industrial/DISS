<?php

namespace Tests\Feature;

use App\Livewire\InspectionShow;
use App\Models\InspectionForm\DetailInspectionReport;
use App\Models\InspectionForm\InspectionReport;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class InspectionReportShowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_inspection_show_livewire_renders_open_all_accordions_buttons()
    {
        $docNum = 'DOC-TEST-SHOW-' . uniqid();
        $report = InspectionReport::create([
            'document_number' => $docNum,
            'customer' => 'Customer A',
            'inspection_date' => '2026-10-19',
            'part_number' => 'PN-123',
            'part_name' => 'Part Widget',
            'weight' => 12.5,
            'weight_uom' => 'g',
            'material' => 'ABS',
            'color' => 'Black',
            'tool_number_or_cav_number' => 'T1/C2',
            'machine_number' => 'MC-01',
            'shift' => 1,
        ]);

        DetailInspectionReport::create([
            'inspection_report_document_number' => $docNum,
            'document_number' => $docNum . '-P1',
            'period' => 1,
            'start_datetime' => '2026-10-19 08:00:00',
            'end_datetime' => '2026-10-19 10:00:00',
        ]);

        // Test Livewire component
        Livewire::test(InspectionShow::class, ['inspection_report' => $report])
            ->assertSee('Open All Accordions')
            ->assertSee('Collapse All')
            ->assertSee('toggleAccordionGroup')
            ->assertSee('toggleAllAccordions')
            ->assertSee('First Inspection')
            ->assertSee('Dimension Data')
            ->assertSee('Second Inspection')
            ->assertSee('Judgement');

        // Test legacy blade view rendering
        $report->load([
            'detailInspectionReports.firstInspections',
            'detailInspectionReports.secondInspections',
            'detailInspectionReports.judgementData',
            'dimensionData',
            'problemData',
            'quantityData',
        ]);
        $blade = view('inspection.show', ['inspectionReport' => $report, 'r' => $report])->render();
        $this->assertStringContainsString('Open All Accordions', $blade);
        $this->assertStringContainsString('Collapse All', $blade);
        $this->assertStringContainsString('toggleAccordionGroup', $blade);
        $this->assertStringContainsString('toggleAllAccordions', $blade);
        $this->assertStringContainsString('First Inspection', $blade);
    }
}
