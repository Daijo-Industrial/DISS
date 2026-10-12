<?php

declare(strict_types=1);

namespace App\Domain\Project\Enums;

enum ProjectStatus: string
{
    case ACTIVE = 'ACTIVE';
    case ON_HOLD = 'ON_HOLD';
    case CANCELLED = 'CANCELLED';
    case COMPLETED = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::ON_HOLD => 'On Hold',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::CANCELLED, self::COMPLETED], true);
    }
}
