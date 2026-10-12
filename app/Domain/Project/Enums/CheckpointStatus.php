<?php

declare(strict_types=1);

namespace App\Domain\Project\Enums;

enum CheckpointStatus: string
{
    case PENDING = 'PENDING';
    case COMPLETED = 'COMPLETED';
    case SKIPPED = 'SKIPPED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::COMPLETED => 'Completed',
            self::SKIPPED => 'Skipped',
        };
    }
}
