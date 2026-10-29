<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Header & Subtitle --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('ticketing.list.title') }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">{{ __('ticketing.list.subtitle') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-slate-400 font-medium">
                {{ $tickets->total() }} {{ strtolower(__('ticketing.list.ticket')) }}
            </span>
            <button type="button"
                class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-2 text-xs font-semibold shadow-xs transition-colors cursor-pointer shrink-0"
                @click.stop="$dispatch('open-support-bubble', { tab: 'new' })">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('ticketing.nav.new_ticket') }}</span>
            </button>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session()->has('success'))
        <div class="px-4 py-3 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Control Center: Unified Apple / Linear 2-Row Toolbar --}}
    <div class="space-y-3">
        {{-- Row 1: Scope Tabs (Linear / Apple Text Tabs) & Secondary Filters --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-3">
            {{-- Left: Scope Tabs --}}
            <div class="flex items-center gap-1 overflow-x-auto no-scrollbar" role="group" aria-label="Scope Filter">
                <button type="button" wire:click="setScope('all')"
                    aria-pressed="{{ $scopeFilter === 'all' ? 'true' : 'false' }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer shrink-0 {{ $scopeFilter === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    <span>{{ __('ticketing.list.all_tickets') }}</span>
                    <span class="text-[10px] ml-1 opacity-70">({{ $counts['all'] }})</span>
                </button>

                @if ($canManage)
                    <button type="button" wire:click="setScope('assigned_to_me')"
                        aria-pressed="{{ $scopeFilter === 'assigned_to_me' ? 'true' : 'false' }}"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer shrink-0 {{ $scopeFilter === 'assigned_to_me' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <span>{{ __('ticketing.list.assigned_to_me') }}</span>
                        @if ($counts['assigned_to_me'] > 0)
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full font-bold {{ $scopeFilter === 'assigned_to_me' ? 'bg-slate-800 text-white' : 'bg-slate-200 text-slate-700' }}">
                                {{ $counts['assigned_to_me'] }}
                            </span>
                        @endif
                    </button>

                    <button type="button" wire:click="setScope('unassigned')"
                        aria-pressed="{{ $scopeFilter === 'unassigned' ? 'true' : 'false' }}"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer shrink-0 {{ $scopeFilter === 'unassigned' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <span>{{ __('ticketing.list.unassigned_queue') }}</span>
                        @if ($counts['unassigned'] > 0)
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full font-bold {{ $scopeFilter === 'unassigned' ? 'bg-amber-500 text-white' : 'bg-amber-100 text-amber-700' }}">
                                {{ $counts['unassigned'] }}
                            </span>
                        @endif
                    </button>
                @endif
            </div>

            {{-- Right: Compact Secondary Filters (Category, Priority) & Reset Action --}}
            <div class="flex items-center gap-2 flex-wrap">
                <select wire:model.live="categoryFilter"
                    class="rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200/80 py-1.5 pl-2.5 pr-7 text-xs text-slate-700 font-medium focus:bg-white focus:border-slate-400 focus:outline-none transition-colors cursor-pointer">
                    <option value="">{{ __('ticketing.list.all_categories') }}</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="priorityFilter"
                    class="rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200/80 py-1.5 pl-2.5 pr-7 text-xs text-slate-700 font-medium focus:bg-white focus:border-slate-400 focus:outline-none transition-colors cursor-pointer">
                    <option value="">{{ __('ticketing.list.all_priorities') }}</option>
                    @foreach ($priorities as $pri)
                        <option value="{{ $pri->value }}">{{ __('ticketing.concierge.priorities.' . strtolower($pri->value)) }}</option>
                    @endforeach
                </select>

                @if ($search || $statusFilter || $priorityFilter || $categoryFilter || $scopeFilter !== 'all')
                    <button type="button" wire:click="resetFilters"
                        class="text-xs font-medium text-slate-400 hover:text-rose-600 transition-colors inline-flex items-center gap-1 cursor-pointer pl-1">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>{{ __('ticketing.list.clear_filters') }}</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- Row 2: Search Input & Sleek Status Filter Chips --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            {{-- Search Bar --}}
            <div class="relative w-full md:w-80 shrink-0">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('ticketing.list.search_placeholder') }}"
                    class="block w-full rounded-xl bg-slate-100/70 border border-transparent pl-9 pr-3.5 py-1.5 text-xs text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-slate-300 focus:outline-none transition-all">
            </div>

            {{-- Status Filter Chips (Linear Style) --}}
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 no-scrollbar" role="group" aria-label="Status Filters">
                <button type="button" wire:click="setStatusFilter('')"
                    aria-pressed="{{ empty($statusFilter) ? 'true' : 'false' }}"
                    class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer shrink-0 {{ empty($statusFilter) ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100' }}">
                    <span>{{ __('ticketing.list.all_statuses') }}</span>
                </button>

                @foreach ($statuses as $st)
                    @php
                        $val = $st->value;
                        $sKey = strtolower(str_replace(' ', '_', $val));
                        $sLabel = __('ticketing.status.' . $sKey);
                        $countKey = match ($val) {
                            'Open' => 'open',
                            'In Progress' => 'in_progress',
                            'On Hold' => 'on_hold',
                            'Resolved' => 'resolved',
                            'Closed' => 'closed',
                            default => null,
                        };
                        $c = $countKey ? ($counts[$countKey] ?? 0) : 0;
                        $dotColor = match ($val) {
                            'Open' => 'bg-blue-500',
                            'In Progress' => 'bg-emerald-500',
                            'On Hold' => 'bg-amber-500',
                            'Resolved' => 'bg-slate-400',
                            'Closed' => 'bg-slate-300',
                            default => 'bg-slate-400',
                        };
                        $isActive = ($statusFilter === $val);
                    @endphp
                    <button type="button" wire:click="setStatusFilter('{{ $val }}')"
                        aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                        class="px-2.5 py-1 rounded-lg text-xs font-medium transition-all flex items-center gap-1.5 cursor-pointer shrink-0 {{ $isActive ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $dotColor }} shrink-0" aria-hidden="true"></span>
                        <span>{{ $sLabel !== 'ticketing.status.' . $sKey ? $sLabel : $val }}</span>
                        @if ($c > 0)
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full font-bold {{ $isActive ? 'bg-slate-800 text-slate-200' : 'bg-slate-200/80 text-slate-600' }}">
                                {{ $c }}
                            </span>
                        @endif
                    </button>
                @endforeach

                @if ($counts['critical'] > 0)
                    <button type="button" wire:click="setPriorityFilter('Critical')"
                        aria-pressed="{{ $priorityFilter === 'Critical' ? 'true' : 'false' }}"
                        class="px-2.5 py-1 rounded-lg border transition-all inline-flex items-center gap-1.5 cursor-pointer text-xs font-medium shrink-0 {{ $priorityFilter === 'Critical' ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $priorityFilter === 'Critical' ? 'bg-white' : 'bg-rose-500' }}" aria-hidden="true"></span>
                        <span>{{ __('ticketing.concierge.priorities.critical') }}</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full font-bold {{ $priorityFilter === 'Critical' ? 'bg-rose-700 text-white' : 'bg-rose-200/80 text-rose-800' }}">
                            {{ $counts['critical'] }}
                        </span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Apple-Style Fluid Ticket List (Zero Horizontal Scrolling) --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse($tickets as $ticket)
                @php
                    $statusKey = strtolower(str_replace(' ', '_', $ticket->status->value));
                    $localizedStatus = __('ticketing.status.' . $statusKey);
                    $sla = $ticket->getSlaStatus();
                @endphp
                <div wire:click="gotoDetail({{ $ticket->id }})"
                    tabindex="0"
                    role="link"
                    @keydown.enter="$wire.gotoDetail({{ $ticket->id }})"
                    class="group px-4 sm:px-6 py-3.5 flex items-center justify-between gap-4 hover:bg-slate-50/80 transition-colors cursor-pointer focus:outline-none focus:bg-slate-50/80">

                    {{-- Left Section: Title and Metadata (Simplified, No duplicate status dot) --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <span class="font-mono text-xs font-semibold text-slate-400 shrink-0">
                                {{ $ticket->ticket_number }}
                            </span>
                            <h3 class="text-sm font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors truncate">
                                {{ $ticket->title }}
                            </h3>
                            @if ($ticket->priority->value === 'Critical')
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    {{ __('ticketing.concierge.priorities.critical') }}
                                </span>
                            @endif
                        </div>

                        {{-- Clean Subtitle Metadata Line --}}
                        <div class="flex items-center gap-1.5 text-xs text-slate-500 font-normal truncate">
                            <span class="text-slate-600 font-medium">{{ $ticket->category->name }}</span>
                            <span class="text-slate-300">•</span>
                            <span>{{ optional($ticket->reporter)->name ?? 'User' }}</span>
                            @if (optional(optional($ticket->reporter)->department)->name)
                                <span class="text-slate-400 hidden md:inline">({{ optional($ticket->reporter->department)->name }})</span>
                            @endif
                            <span class="text-slate-300 hidden sm:inline">•</span>
                            <span class="text-slate-400 hidden sm:inline" title="{{ $ticket->created_at->format('M d, Y H:i') }}">
                                {{ $ticket->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>

                    {{-- Right Section: Status Pill, SLA Urgency, Assignee, and Chevron --}}
                    <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                        {{-- SLA Status (Quiet & Contextual) --}}
                        @if ($sla['state'] === 'breached')
                            <span class="hidden md:inline-flex items-center gap-1 text-[11px] font-semibold text-rose-600 bg-rose-50 border border-rose-100 rounded-full px-2 py-0.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-600 animate-pulse"></span>
                                <span>{{ $sla['remaining'] }}</span>
                            </span>
                        @elseif($sla['state'] === 'due_soon')
                            <span class="hidden md:inline-flex items-center gap-1 text-[11px] font-semibold text-amber-600 bg-amber-50 border border-amber-100 rounded-full px-2 py-0.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                                <span>{{ $sla['remaining'] }}</span>
                            </span>
                        @endif

                        {{-- Status Pill --}}
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border {{ $ticket->status->getBadgeColor() }}">
                            {{ $localizedStatus !== 'ticketing.status.' . $statusKey ? $localizedStatus : $ticket->status->value }}
                        </span>

                        {{-- Assignee Avatar or Unassigned Tag --}}
                        @if ($ticket->assignee)
                            <div class="flex items-center gap-1.5" title="{{ __('ticketing.detail.assignee') }}: {{ $ticket->assignee->name }}">
                                <div class="h-6 w-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] font-bold uppercase shrink-0">
                                    {{ substr($ticket->assignee->name, 0, 2) }}
                                </div>
                                <span class="text-xs text-slate-600 font-medium hidden lg:inline truncate max-w-[100px]">
                                    {{ $ticket->assignee->name }}
                                </span>
                            </div>
                        @else
                            <div class="flex items-center gap-1">
                                <span class="text-[11px] text-slate-400 font-medium hidden sm:inline">
                                    {{ __('ticketing.detail.unassigned') }}
                                </span>
                                @if ($canManage)
                                    <button type="button"
                                        wire:click.stop="claimTicket({{ $ticket->id }})"
                                        title="{{ __('ticketing.list.claim_ticket') }}"
                                        class="rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-0.5 text-[11px] font-semibold transition-colors cursor-pointer">
                                        {{ __('ticketing.list.claim') }}
                                    </button>
                                @endif
                            </div>
                        @endif

                        {{-- Apple Subtle Disclosure Chevron --}}
                        <svg class="h-4 w-4 text-slate-300 group-hover:text-slate-500 transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>

                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <div class="h-10 w-10 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-2.5">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-800">{{ __('ticketing.list.empty_title') }}</h3>
                    <p class="mt-1 text-xs text-slate-400 max-w-sm mx-auto">{{ __('ticketing.list.empty_subtitle') }}</p>
                    @if ($search || $statusFilter || $priorityFilter || $categoryFilter || $scopeFilter !== 'all')
                        <div class="mt-3">
                            <button type="button" wire:click="resetFilters"
                                class="text-xs font-semibold text-slate-900 underline hover:text-slate-600 transition-colors cursor-pointer">
                                {{ __('ticketing.list.clear_filters') }}
                            </button>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>

        {{-- Clean Pagination --}}
        @if ($tickets->hasPages())
            <div class="border-t border-slate-100 px-6 py-3 bg-slate-50/50">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
