<x-mail::message>
# 🎫 New IT Support Ticket: #{{ $ticket->ticket_number }}

A new support ticket has been submitted and requires attention from the IT team.

**Summary:** {{ $ticket->title }}  
**Category:** {{ $ticket->category->name ?? 'General' }}  
**Priority:** {{ $ticket->priority->value ?? $ticket->priority }}  
**Reporter:** {{ $ticket->reporter->Nama ?? $ticket->reporter_id }} (NIK: {{ $ticket->reporter_id }})  
@if($ticket->reporter_email)
**Contact Email:** {{ $ticket->reporter_email }}  
@endif
@if($ticket->current_url)
**Page / Route:** [{{ $ticket->current_url }}]({{ $ticket->current_url }})  
@endif

---

### Description:
{{ $ticket->description }}

@if($ticket->screenshot_path)
> 📷 *A screenshot was attached to this ticket and can be viewed directly in the dashboard.*
@endif

<x-mail::button :url="$ticketUrl">
View Ticket in DISS
</x-mail::button>

Thanks,<br>
{{ config('app.name') }} IT Concierge
</x-mail::message>
