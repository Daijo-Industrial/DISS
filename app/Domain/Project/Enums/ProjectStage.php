<?php

declare(strict_types=1);

namespace App\Domain\Project\Enums;

enum ProjectStage: string
{
    case DRAFT = 'DRAFT';
    case INITIATION = 'INITIATION';
    case PLANNING = 'PLANNING';
    case DEVELOPMENT = 'DEVELOPMENT';
    case MONITORING = 'MONITORING';
    case REVIEW = 'REVIEW';
    case CLOSURE = 'CLOSURE';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::INITIATION => 'Initiation',
            self::PLANNING => 'Planning',
            self::DEVELOPMENT => 'Development',
            self::MONITORING => 'Monitoring',
            self::REVIEW => 'Review',
            self::CLOSURE => 'Closure',
        };
    }

    /**
     * Determines whether the project is in an active execution phase.
     */
    public function isExecution(): bool
    {
        return in_array($this, [self::DEVELOPMENT, self::MONITORING, self::REVIEW], true);
    }
}
