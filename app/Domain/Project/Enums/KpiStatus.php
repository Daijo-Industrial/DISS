<?php

declare(strict_types=1);

namespace App\Domain\Project\Enums;

enum KpiStatus: string
{
    case ON_TRACK = 'ON_TRACK';
    case AT_RISK = 'AT_RISK';
    case OVERDUE = 'OVERDUE';
    case ON_HOLD = 'ON_HOLD';
    case CANCELLED = 'CANCELLED';
    case ON_TIME = 'ON_TIME';
    case LATE = 'LATE';

    public function label(): string
    {
        return match ($this) {
            self::ON_TRACK => 'On Track',
            self::AT_RISK => 'At Risk',
            self::OVERDUE => 'Overdue',
            self::ON_HOLD => 'On Hold',
            self::CANCELLED => 'Cancelled',
            self::ON_TIME => 'Completed On Time',
            self::LATE => 'Completed Late',
        };
    }
}
