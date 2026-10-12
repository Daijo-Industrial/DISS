<?php

declare(strict_types=1);

namespace App\Domain\Project\Enums;

enum ProjectPriority: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
    case CRITICAL = 'CRITICAL';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::CRITICAL => 'Critical',
        };
    }
}
