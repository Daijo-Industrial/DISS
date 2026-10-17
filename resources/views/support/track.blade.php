@extends('new.layouts.guest')

@section('title', __('ticketing.track.title'))

@section('content')
    @if ($ticket)
        {{-- Found Ticket View --}}
        <div class="space-y-6">
            {{-- Ticket Header & Badge --}}
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                <div>
                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700 ring-1 ring-inset ring-indigo-500/10">
                        {{ $ticket->ticket_number }}
                    </span>
                    <h2 class="mt-2 text-lg font-black text-slate-900 tracking-tight leading-snug">
                        {{ $ticket->title }}
                    </h2>
                    <p class="text-[11px] text-slate-400 mt-1">
                        {{ __('ticketing.track.created_at') }}: <span class="font-semibold text-slate-600">{{ $ticket->created_at->format('d M Y, H:i') }}</span>
                    </p>
                </div>

                @php
                    $statusStyles = [
                        'Open' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                        'In Progress' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'On Hold' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'Resolved' => 'bg-slate-100 text-slate-800 border-slate-200',
                        'Closed' => 'bg-slate-100 text-slate-500 border-slate-200',
                    ];
                    $badgeStyle = $statusStyles[$ticket->status->value] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                @endphp

                <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold {{ $badgeStyle }} shrink-0">
                    {{ $ticket->status->value }}
                </span>
            </div>

            {{-- Key Metrics & Info Grid --}}
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">{{ __('ticketing.detail.change_category') }}</div>
                    <div class="font-bold text-slate-800 truncate">{{ $ticket->category->name }}</div>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">{{ __('ticketing.detail.priority') }}</div>
                    <div class="font-bold text-slate-800">{{ $ticket->priority->value }}</div>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">{{ __('ticketing.track.sla_target') }}</div>
                    <div class="font-bold text-slate-800">{{ $ticket->getFormattedSlaTarget() }}</div>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">{{ __('ticketing.track.assigned_to') }}</div>
                    <div class="font-bold text-slate-800 truncate">
                        {{ $ticket->assignee->name ?? __('ticketing.track.unassigned') }}
                    </div>
                </div>
            </div>

            {{-- Problem Description --}}
            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 text-xs">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">{{ __('ticketing.login_help.details') }}</div>
                <p class="text-slate-700 whitespace-pre-line leading-relaxed">{{ $ticket->getCleanDescription() }}</p>
            </div>

            {{-- Recent Progress Timeline --}}
            <div class="space-y-3">
                <div class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ __('ticketing.track.status_timeline') }}</span>
                </div>

                @if ($ticket->activities->isNotEmpty())
                    <div class="space-y-2 border-l-2 border-indigo-100 pl-3 ml-2">
                        @foreach ($ticket->activities as $act)
                            <div class="text-xs">
                                <div class="flex items-center justify-between text-[11px] text-slate-400 mb-0.5">
                                    <span class="font-bold text-slate-700">{{ optional($act->user)->name ?? 'IT Support System' }}</span>
                                    <span>{{ $act->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="text-slate-600 bg-white rounded-xl border border-slate-100 p-2.5 shadow-2xs">
                                    @if ($act->new_state)
                                        <span class="font-bold text-indigo-600">{{ $act->new_state }}</span> —
                                    @endif
                                    {{ $act->reason }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-400 italic">
                        {{ __('ticketing.track.no_activities') }}
                    </p>
                @endif
            </div>

            {{-- Navigation Actions --}}
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                <a href="{{ route('support.track') }}"
                    class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 transition-colors">
                    ← {{ __('ticketing.track.check_button') }}
                </a>
                <a href="{{ route('login') }}"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-slate-800 transition-colors">
                    {{ __('ticketing.track.back_to_login') }}
                </a>
            </div>
        </div>
    @else
        {{-- Search / Lookup Form --}}
        <div>
            <div class="mb-6 text-center">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 shadow-sm mb-3">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <h2 class="text-xl font-black text-slate-900 tracking-tight">
                    {{ __('ticketing.track.title') }}
                </h2>
                <p class="mt-1.5 text-xs text-slate-500 font-medium">
                    {{ __('ticketing.track.subtitle') }}
                </p>
            </div>

            @if ($error)
                <div class="mb-5 flex items-start gap-2.5 rounded-2xl bg-rose-50/80 backdrop-blur-sm border border-rose-100 p-3.5 text-xs text-rose-700">
                    <svg class="h-4 w-4 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-semibold">{{ $error }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('support.track') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="ticket_input" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        {{ __('ticketing.track.ticket_number') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="ticket" id="ticket_input" required value="{{ old('ticket', $ticketNumber) }}"
                        placeholder="{{ __('ticketing.track.ticket_number_placeholder') }}"
                        class="block w-full rounded-xl border border-slate-200/80 bg-white/60 backdrop-blur-sm py-2.5 px-3.5 text-xs font-medium text-slate-900 placeholder-slate-400 shadow-sm focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 hover:border-slate-300">
                </div>

                <div>
                    <label for="nik_input" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        {{ __('ticketing.track.nik') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="nik" id="nik_input" required value="{{ old('nik', $nik) }}"
                        placeholder="{{ __('ticketing.track.nik_placeholder') }}"
                        class="block w-full rounded-xl border border-slate-200/80 bg-white/60 backdrop-blur-sm py-2.5 px-3.5 text-xs font-medium text-slate-900 placeholder-slate-400 shadow-sm focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 hover:border-slate-300">
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 py-3 text-xs font-bold text-white shadow-md shadow-indigo-200 hover:shadow-lg hover:shadow-indigo-300 hover:scale-[1.01] transition-all">
                        {{ __('ticketing.track.check_button') }}
                    </button>
                </div>

                <div class="pt-3 text-center">
                    <a href="{{ route('login') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
                        ← {{ __('ticketing.track.back_to_login') }}
                    </a>
                </div>
            </form>
        </div>
    @endif
@endsection
