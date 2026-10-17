<?php

namespace App\Domains\Ticketing\Enums;

enum TicketStatus: string
{
    case OPEN = 'Open';
    case IN_PROGRESS = 'In Progress';
    case ON_HOLD = 'On Hold';
    case RESOLVED = 'Resolved';
    case CLOSED = 'Closed';

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::OPEN => 'bg-blue-50 text-blue-700 border-blue-200',
            self::IN_PROGRESS => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::ON_HOLD => 'bg-amber-50 text-amber-700 border-amber-200',
            self::RESOLVED => 'bg-slate-100 text-slate-700 border-slate-200',
            self::CLOSED => 'bg-slate-50 text-slate-500 border-slate-200',
        };
    }
}
