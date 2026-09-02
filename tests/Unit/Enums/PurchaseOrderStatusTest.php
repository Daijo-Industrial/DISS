<?php

namespace Tests\Unit\Enums;

use App\Enums\PurchaseOrderStatus;
use PHPUnit\Framework\TestCase;

class PurchaseOrderStatusTest extends TestCase
{
    public function test_enum_values()
    {
        $this->assertEquals(1, PurchaseOrderStatus::PENDING_APPROVAL->value);
        $this->assertEquals(2, PurchaseOrderStatus::APPROVED->value);
        $this->assertEquals(3, PurchaseOrderStatus::REJECTED->value);
        $this->assertEquals(4, PurchaseOrderStatus::CANCELLED->value);
        $this->assertEquals(5, PurchaseOrderStatus::DRAFT->value);
    }

    public function test_labels()
    {
        $this->assertEquals('Pending Approval', PurchaseOrderStatus::PENDING_APPROVAL->label());
        $this->assertEquals('Approved', PurchaseOrderStatus::APPROVED->label());
        $this->assertEquals('Rejected', PurchaseOrderStatus::REJECTED->label());
        $this->assertEquals('Cancelled', PurchaseOrderStatus::CANCELLED->label());
        $this->assertEquals('Draft', PurchaseOrderStatus::DRAFT->label());
    }

    public function test_can_edit()
    {
        $this->assertFalse(PurchaseOrderStatus::PENDING_APPROVAL->canEdit());
        $this->assertFalse(PurchaseOrderStatus::APPROVED->canEdit());
        $this->assertTrue(PurchaseOrderStatus::REJECTED->canEdit());
        $this->assertTrue(PurchaseOrderStatus::CANCELLED->canEdit());
        $this->assertTrue(PurchaseOrderStatus::DRAFT->canEdit());
    }

    public function test_can_approve()
    {
        $this->assertTrue(PurchaseOrderStatus::PENDING_APPROVAL->canApprove());
        $this->assertFalse(PurchaseOrderStatus::APPROVED->canApprove());
        $this->assertFalse(PurchaseOrderStatus::REJECTED->canApprove());
        $this->assertFalse(PurchaseOrderStatus::CANCELLED->canApprove());
        $this->assertFalse(PurchaseOrderStatus::DRAFT->canApprove());
    }

    public function test_is_terminal()
    {
        $this->assertFalse(PurchaseOrderStatus::PENDING_APPROVAL->isTerminal());
        $this->assertTrue(PurchaseOrderStatus::APPROVED->isTerminal());
        $this->assertTrue(PurchaseOrderStatus::REJECTED->isTerminal());
        $this->assertTrue(PurchaseOrderStatus::CANCELLED->isTerminal());
        $this->assertFalse(PurchaseOrderStatus::DRAFT->isTerminal());
    }

    public function test_css_classes()
    {
        $this->assertEquals('bg-amber-100 text-amber-800 border-amber-200', PurchaseOrderStatus::PENDING_APPROVAL->cssClass());
        $this->assertEquals('bg-emerald-100 text-emerald-800 border-emerald-200', PurchaseOrderStatus::APPROVED->cssClass());
        $this->assertEquals('bg-rose-100 text-rose-800 border-rose-200', PurchaseOrderStatus::REJECTED->cssClass());
        $this->assertEquals('bg-orange-100 text-orange-800 border-orange-200', PurchaseOrderStatus::CANCELLED->cssClass());
        $this->assertEquals('bg-slate-100 text-slate-800 border-slate-200', PurchaseOrderStatus::DRAFT->cssClass());
    }

    public function test_from_workflow_status()
    {
        $this->assertEquals(PurchaseOrderStatus::DRAFT, PurchaseOrderStatus::fromWorkflowStatus('DRAFT'));
        $this->assertEquals(PurchaseOrderStatus::PENDING_APPROVAL, PurchaseOrderStatus::fromWorkflowStatus('IN_REVIEW'));
        $this->assertEquals(PurchaseOrderStatus::APPROVED, PurchaseOrderStatus::fromWorkflowStatus('APPROVED'));
        $this->assertEquals(PurchaseOrderStatus::REJECTED, PurchaseOrderStatus::fromWorkflowStatus('REJECTED'));
        $this->assertEquals(PurchaseOrderStatus::CANCELLED, PurchaseOrderStatus::fromWorkflowStatus('CANCELLED'));
    }

    public function test_from_workflow_status_invalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        PurchaseOrderStatus::fromWorkflowStatus('INVALID_STATUS');
    }

    public function test_values()
    {
        $expected = [1, 2, 3, 4, 5];
        $this->assertEquals($expected, PurchaseOrderStatus::values());
    }

    public function test_options()
    {
        $expected = [
            1 => 'Pending Approval',
            2 => 'Approved',
            3 => 'Rejected',
            4 => 'Cancelled',
            5 => 'Draft',
        ];
        $this->assertEquals($expected, PurchaseOrderStatus::options());
    }
}
