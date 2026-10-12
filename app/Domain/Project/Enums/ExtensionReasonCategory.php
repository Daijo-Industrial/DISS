<?php

declare(strict_types=1);

namespace App\Domain\Project\Enums;

enum ExtensionReasonCategory: string
{
    case SCOPE_CHANGE = 'SCOPE_CHANGE';
    case VENDOR_DELAY = 'VENDOR_DELAY';
    case MANAGEMENT_PREEMPTION = 'MANAGEMENT_PREEMPTION';
    case PAUSE_EXTENSION = 'PAUSE_EXTENSION';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::SCOPE_CHANGE => 'Scope Change',
            self::VENDOR_DELAY => 'Vendor / External Blocker',
            self::MANAGEMENT_PREEMPTION => 'Priority Preemption',
            self::PAUSE_EXTENSION => 'On-Hold Pause Extension',
            self::OTHER => 'Other',
        };
    }
}
