<?php

namespace Tests\Unit\Services\Payroll;

use App\Services\Payroll\Sync\DateRangeResolver;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class DateRangeResolverTest extends TestCase
{
    private DateRangeResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new DateRangeResolver();
    }

    public function test_resolves_day_one_of_month_to_full_previous_month(): void
    {
        // On 1st of September 2026 at 09:30
        Carbon::setTestNow(Carbon::parse('2026-09-01 09:30:00', 'Asia/Jakarta'));

        $range = $this->resolver->resolve(null, null, 'Asia/Jakarta');

        $this->assertSame('2026-08-01 00:00:00', $range['from']->toDateTimeString());
        $this->assertSame('2026-08-31 23:59:59', $range['to']->toDateTimeString());
        $this->assertTrue($range['from']->lte($range['to']));
    }

    public function test_resolves_day_two_of_month_correctly(): void
    {
        // On 2nd of September 2026 at 09:30
        Carbon::setTestNow(Carbon::parse('2026-09-02 09:30:00', 'Asia/Jakarta'));

        $range = $this->resolver->resolve(null, null, 'Asia/Jakarta');

        $this->assertSame('2026-09-01 00:00:00', $range['from']->toDateTimeString());
        $this->assertSame('2026-09-01 23:59:59', $range['to']->toDateTimeString());
        $this->assertTrue($range['from']->lte($range['to']));
    }

    public function test_resolves_end_of_month_correctly(): void
    {
        // On 31st of August 2026 at 09:30
        Carbon::setTestNow(Carbon::parse('2026-08-31 09:30:00', 'Asia/Jakarta'));

        $range = $this->resolver->resolve(null, null, 'Asia/Jakarta');

        $this->assertSame('2026-08-01 00:00:00', $range['from']->toDateTimeString());
        $this->assertSame('2026-08-30 23:59:59', $range['to']->toDateTimeString());
        $this->assertTrue($range['from']->lte($range['to']));
    }

    public function test_resolves_year_rollover_on_january_first(): void
    {
        // On 1st of January 2027 at 09:30
        Carbon::setTestNow(Carbon::parse('2027-01-01 09:30:00', 'Asia/Jakarta'));

        $range = $this->resolver->resolve(null, null, 'Asia/Jakarta');

        $this->assertSame('2026-12-01 00:00:00', $range['from']->toDateTimeString());
        $this->assertSame('2026-12-31 23:59:59', $range['to']->toDateTimeString());
        $this->assertTrue($range['from']->lte($range['to']));
    }

    public function test_resolves_explicit_from_date_ahead_of_yesterday(): void
    {
        // On 1st of September 2026, user requests from '2026-09-01' with null to
        Carbon::setTestNow(Carbon::parse('2026-09-01 09:30:00', 'Asia/Jakarta'));

        $range = $this->resolver->resolve('2026-09-01', null, 'Asia/Jakarta');

        $this->assertSame('2026-09-01 00:00:00', $range['from']->toDateTimeString());
        $this->assertSame('2026-09-01 23:59:59', $range['to']->toDateTimeString());
        $this->assertTrue($range['from']->lte($range['to']));
    }

    public function test_resolves_explicit_from_and_to_dates(): void
    {
        $range = $this->resolver->resolve('2026-08-10', '2026-08-20', 'Asia/Jakarta');

        $this->assertSame('2026-08-10 00:00:00', $range['from']->toDateTimeString());
        $this->assertSame('2026-08-20 23:59:59', $range['to']->toDateTimeString());
        $this->assertTrue($range['from']->lte($range['to']));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
