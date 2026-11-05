<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\PurchaseRequest\Services;

use App\Application\PurchaseRequest\Queries\GetPurchaseRequestDetail;
use App\Application\PurchaseRequest\ViewModels\PurchaseRequestDetailVM;
use App\Domain\PurchaseRequest\Services\PurchaseRequestPdfService;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Mockery;
use PHPUnit\Framework\TestCase;

class PurchaseRequestPdfServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_filename_sanitizes_document_and_pr_numbers(): void
    {
        $ref = new \ReflectionClass(PurchaseRequestPdfService::class);
        /** @var PurchaseRequestPdfService $service */
        $service = $ref->newInstanceWithoutConstructor();

        $pr = new PurchaseRequest([
            'doc_num' => 'PR/JKT/2026-001',
            'pr_no' => 'PR#123/ABC',
        ]);

        $filename = $service->generateFilename($pr);

        $this->assertEquals('PR_PR_JKT_2026-001_PR_123_ABC.pdf', $filename);
    }
}
