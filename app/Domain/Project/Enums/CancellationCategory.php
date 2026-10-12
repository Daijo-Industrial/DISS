<?php

declare(strict_types=1);

namespace App\Domain\Project\Enums;

enum CancellationCategory: string
{
    case REQUESTER_DEPEN_DEP_PRIORITIZED = 'REQUESTER_DEPEN_DEP_PRIORITIZED';
    case BUDGET_CUT = 'BUDGET_CUT';
    case TECHNICAL_INFEASIBILITY = 'TECHNICAL_INFEASIBILITY';
    case DUPLICATE = 'DUPLICATE';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::REQUESTER_DEPEN_DEP_PRIORITIZED => 'Requester / Business Deprioritized',
            self::BUDGET_CUT => 'Budget / Resource Cut',
            self::TECHNICAL_INFEASIBILITY => 'Technical Infeasibility',
            self::DUPLICATE => 'Duplicate / Superseded',
            self::OTHER => 'Other Reason',
        };
    }
}
