<?php

namespace App\Domains\Ticketing\Enums;

enum TicketPriority: string
{
    case LOW = 'Low';
    case MEDIUM = 'Medium';
    case HIGH = 'High';
    case CRITICAL = 'Critical';

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::LOW => 'bg-slate-100 text-slate-700 border-slate-200',
            self::MEDIUM => 'bg-blue-50 text-blue-700 border-blue-200',
            self::HIGH => 'bg-amber-50 text-amber-700 border-amber-200',
            self::CRITICAL => 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }
}
