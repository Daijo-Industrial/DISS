<x-mail::message>
# New Reply on Ticket #{{ $ticket->ticket_number }}

Hello {{ $recipientName }},

**{{ $authorName }}** has posted a new reply on ticket **#{{ $ticket->ticket_number }}** ({{ $ticket->title }}).

---

### Message:
@if($activity->reason)
{{ $activity->reason }}
@endif

@if($activity->new_state)
*An image attachment was included in this reply.*
@endif

---

<x-mail::button :url="$ticketUrl">
View & Reply in DISS
</x-mail::button>

Thanks,<br>
{{ config('app.name') }} IT Concierge
</x-mail::message>
