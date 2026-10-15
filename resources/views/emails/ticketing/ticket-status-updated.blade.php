<x-mail::message>
# 📌 Ticket #{{ $ticket->ticket_number }} Update

Hello {{ $ticket->reporter->Nama ?? 'Colleague' }},

Your IT support ticket has been updated to: **{{ $status }}**.

**Ticket:** {{ $ticket->title }}  
**Category:** {{ $ticket->category->name ?? 'General' }}  
**Status:** {{ $status }}  

@if($reason)
### Technician Note / Resolution:
{{ $reason }}
@endif

<x-mail::button :url="$ticketUrl">
View Ticket Progress
</x-mail::button>

If you have any further questions or if the issue persists, you can respond or re-open the request directly through the IT Concierge in DISS.

Thanks,<br>
{{ config('app.name') }} IT Support
</x-mail::message>
