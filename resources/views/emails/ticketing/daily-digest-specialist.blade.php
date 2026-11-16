<x-mail::message>
# Daily Queue: {{ $technician->name }}
**Date:** {{ $dateStr }} &bull; **Time:** 07:30 AM (Asia/Jakarta)

### Your Assigned Active Tickets ({{ $assignedTickets->count() }})

| Ticket | Subject | Status | Target Due / SLA |
| :--- | :--- | :--- | :--- |
@foreach($assignedTickets as $ticket)
| **#{{ $ticket->ticket_number }}** | [{{ \Illuminate\Support\Str::limit($ticket->title, 35) }}]({{ route('ticketing.show', $ticket->id) }}) | {{ $ticket->status->value }} | {{ $ticket->getFormattedSlaTarget() }} |
@endforeach

<x-mail::button :url="$myTicketsUrl" color="primary">
Open My Active Tickets in DISS
</x-mail::button>

@if($unassignedCount > 0)
---
**Note:** There are currently **{{ $unassignedCount }}** unassigned ticket(s) in the general pool awaiting review.
@endif

Thanks,<br>
{{ config('app.name') }} Automated Operations
</x-mail::message>
