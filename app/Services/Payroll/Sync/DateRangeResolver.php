<?php

declare(strict_types=1);

namespace App\Services\Payroll\Sync;

use Carbon\CarbonImmutable;

/**
 * Resolves an optional from/to pair into a concrete date range.
 * Defaults: to = yesterday end-of-day; from = start of to-date's month.
 * On the 1st of any month, this cleanly syncs the completed previous month
 * without date-range inversion.
 */
final class DateRangeResolver
{
    public function resolve(
        CarbonImmutable|string|null $from,
        CarbonImmutable|string|null $to,
        string $tz,
    ): array {
        $yesterday = now($tz)->subDay()->endOfDay()->toImmutable();

        // 1. Resolve 'to'
        if ($to instanceof CarbonImmutable) {
            $t = $to;
        } elseif ($to) {
            $t = CarbonImmutable::parse($to, $tz)->endOfDay();
        } else {
            // When 'to' is null, default to yesterday end-of-day.
            // If 'from' is explicitly provided and lies past yesterday,
            // default 'to' to the end of that 'from' date so the range is valid.
            if ($from) {
                $parsedFrom = $from instanceof CarbonImmutable ? $from : CarbonImmutable::parse($from, $tz);
                $t = $parsedFrom->gt($yesterday)
                    ? $parsedFrom->endOfDay()
                    : $yesterday;
            } else {
                $t = $yesterday;
            }
        }

        // 2. Resolve 'from'
        if ($from instanceof CarbonImmutable) {
            $f = $from;
        } elseif ($from) {
            $f = CarbonImmutable::parse($from, $tz)->startOfDay();
        } else {
            // Default 'from' to the beginning of 'to' date's month
            $f = $t->startOfMonth()->startOfDay();
        }

        return ['from' => $f, 'to' => $t];
    }
}
