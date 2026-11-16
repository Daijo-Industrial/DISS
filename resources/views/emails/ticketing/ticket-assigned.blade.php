<x-mail::message>
# Ticket Assigned: #{{ $ticket->ticket_number }}

Hello {{ $assignee->name }},

@if($assignedBy && $assignedBy->id !== $assignee->id)
You have been assigned to handle this IT support ticket by **{{ $assignedBy->name }}**.
@else
You have been assigned to handle this IT support ticket.
@endif

**Ticket:** {{ $ticket->title }}  
**Category:** {{ $ticket->category->name ?? 'General' }}  
**Reporter:** {{ $ticket->reporter->Nama ?? $ticket->reporter_id }} (NIK: {{ $ticket->reporter_id }})  
@if($ticket->reporter_email)
**Reporter Email:** {{ $ticket->reporter_email }}  
@endif
@php
    $sla = $ticket->getSlaStatus();
@endphp
@if(!empty($sla['remaining']))
**SLA Target:** {{ $sla['remaining'] }}  
@endif

---

### Issue Description:
{{ $ticket->description }}

<x-mail::button :url="$ticketUrl">
Open Ticket in DISS
</x-mail::button>

Thanks,<br>
{{ config('app.name') }} IT Concierge
</x-mail::message>
