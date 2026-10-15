<?php

namespace App\Http\Controllers\Support;

use App\Domains\Ticketing\Entities\Ticket;
use App\Domains\Ticketing\Enums\ActivityType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackTicketController extends Controller
{
    /**
     * Public ticket progress lookup page.
     * Enforces ISO 27001 anti-enumeration by requiring both Ticket Number + Employee NIK.
     */
    public function index(Request $request): View
    {
        $ticketNumber = trim((string) ($request->input('ticket') ?? $request->input('ticket_number') ?? session('submitted_ticket_number', '')));
        $nik = trim((string) ($request->input('nik') ?? session('submitted_ticket_nik', '')));

        $ticket = null;
        $error = null;

        if ($ticketNumber !== '' && $nik !== '') {
            $ticket = Ticket::with([
                'category',
                'assignee',
                'activities' => function ($query) {
                    $query->where('type', ActivityType::STATUS_CHANGE)
                        ->latest();
                },
            ])
                ->where('ticket_number', $ticketNumber)
                ->where('reporter_id', $nik)
                ->first();

            if (! $ticket) {
                $error = __('ticketing.track.not_found');
            }
        } elseif ($request->isMethod('post') || ($request->has('ticket') && ! $request->has('nik'))) {
            if ($ticketNumber === '' || $nik === '') {
                $error = __('ticketing.track.not_found');
            }
        }

        return view('support.track', [
            'ticket' => $ticket,
            'ticketNumber' => $ticketNumber,
            'nik' => $nik,
            'error' => $error,
        ]);
    }
}
