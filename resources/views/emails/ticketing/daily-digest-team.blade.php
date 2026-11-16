<x-mail::message>
# IT Support Morning Queue Digest
**Date:** {{ $dateStr }} &bull; **Time:** 07:30 AM (Asia/Jakarta)

### Queue Overview
- **Unassigned (Needs Review):** {{ $unassignedTickets->count() }}
- **Stalled (>24h Inactive):** {{ $stalledTickets->count() }}
- **Total Active:** {{ $totalActiveCount }}

---

@if($unassignedTickets->isNotEmpty())
### Unassigned Tickets
| Ticket | Subject | Reporter | Age |
| :--- | :--- | :--- | :--- |
@foreach($unassignedTickets as $ticket)
| **#{{ $ticket->ticket_number }}** | [{{ \Illuminate\Support\Str::limit($ticket->title, 35) }}]({{ route('ticketing.show', $ticket->id) }}) | {{ optional($ticket->reporter)->name ?? optional($ticket->reporter)->Nama ?? ($ticket->reporter_id ?? 'Unknown') }} | {{ $ticket->created_at->diffForHumans(null, true) }} |
@endforeach

<x-mail::button :url="$queueUrl" color="primary">
Open Unassigned Queue
</x-mail::button>
@else
### Unassigned Tickets
All active tickets currently have an assigned technician.
@endif

---

@if($stalledTickets->isNotEmpty())
### Stalled Tickets (>24h Without Updates)
| Ticket | Subject | Assignee | Status | Last Active |
| :--- | :--- | :--- | :--- | :--- |
@foreach($stalledTickets as $ticket)
| **#{{ $ticket->ticket_number }}** | [{{ \Illuminate\Support\Str::limit($ticket->title, 30) }}]({{ route('ticketing.show', $ticket->id) }}) | {{ optional($ticket->assignee)->name ?? 'Unassigned' }} | {{ $ticket->status->value }} | {{ $ticket->updated_at->diffForHumans(null, true) }} |
@endforeach
@endif

---

@if(!empty($workloadStats))
### Specialist Workload
| Specialist | Active Tickets |
| :--- | :--- |
@foreach($workloadStats as $techName => $count)
| {{ $techName }} | **{{ $count }}** |
@endforeach
@endif

Thanks,<br>
{{ config('app.name') }} Automated Operations
</x-mail::message>
