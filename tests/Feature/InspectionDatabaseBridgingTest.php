<?php

namespace Tests\Feature;

use App\Livewire\InspectionForm\StepHeader;
use App\Models\InspectionForm\DetailInspectionReport;
use App\Models\InspectionForm\FirstInspection;
use App\Models\InspectionForm\InspectionDefect;
use App\Models\InspectionForm\InspectionDimension;
use App\Models\InspectionForm\InspectionPackaging;
use App\Models\InspectionForm\InspectionProblem;
use App\Models\InspectionForm\InspectionReport;
use App\Models\InspectionForm\InspectionSampling;
use App\Models\InspectionForm\SecondInspection;
use App\Models\InspectionForm\UsesInspectionConnection;
use App\Models\InspectionJudgement;
use App\Models\InspectionQuantity;
use Tests\TestCase;

class InspectionDatabaseBridgingTest extends TestCase
{
    /**
     * Test that all 11 inspection models use the UsesInspectionConnection trait.
     */
    public function test_all_inspection_models_use_uses_inspection_connection_trait(): void
    {
        $models = [
            InspectionReport::class,
            DetailInspectionReport::class,
            FirstInspection::class,
            SecondInspection::class,
            InspectionDimension::class,
            InspectionDefect::class,
            InspectionProblem::class,
            InspectionPackaging::class,
            InspectionSampling::class,
            InspectionQuantity::class,
            InspectionJudgement::class,
        ];

        foreach ($models as $modelClass) {
            $traits = class_uses_recursive($modelClass);
            $this->assertArrayHasKey(
                UsesInspectionConnection::class,
                $traits,
                "Model {$modelClass} must use UsesInspectionConnection trait."
            );
        }
    }

    /**
     * Test that getConnectionName dynamically resolves from config.
     */
    public function test_models_resolve_connection_from_config_dynamically(): void
    {
        $report = new InspectionReport;

        config(['database.inspection_connection' => 'mes_mysql']);
        $this->assertSame('mes_mysql', $report->getConnectionName());

        config(['database.inspection_connection' => 'mysql']);
        $this->assertSame('mysql', $report->getConnectionName());
    }

    /**
     * Test that mes_mysql connection configuration is properly defined.
     */
    public function test_mes_mysql_connection_config_exists(): void
    {
        $config = config('database.connections.mes_mysql');

        $this->assertIsArray($config);
        $this->assertSame('mysql', $config['driver']);
        $this->assertSame('DAIJO_MES', $config['database']);
    }

    /**
     * Test that StepHeader unique validation rule targets the InspectionReport table and connection.
     */
    public function test_step_header_unique_rule_targets_inspection_report_table(): void
    {
        $stepHeader = new StepHeader;
        $rules = $stepHeader->rules();

        $this->assertArrayHasKey('document_number', $rules);
        $documentNumberRules = array_map('strval', $rules['document_number']);

        $expectedTarget = (new InspectionReport)->getConnectionName() . '.' . (new InspectionReport)->getTable();
        $this->assertContains("unique:{$expectedTarget},document_number,NULL,id", $documentNumberRules);
    }
}
