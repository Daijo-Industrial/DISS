<div class="max-w-7xl mx-auto py-6 sm:py-8 px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Flash Notifications --}}
    @if (session()->has('success'))
        <div class="px-4 py-3 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 cursor-pointer">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Top Header & Dual Mode Switcher (Visible to IT Staff) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                {{ $effectiveMode === 'agent_queue' ? __('ticketing.list.title') : __('ticketing.list.mode_self_service') }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                {{ $effectiveMode === 'agent_queue' ? __('ticketing.list.subtitle') : __('ticketing.list.hero_subtitle') }}
            </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            {{-- Mode Switcher for IT Staff --}}
            @if ($canManage)
                <div class="inline-flex items-center p-1 rounded-xl bg-slate-100 border border-slate-200/80 shadow-xs" role="tablist">
                    <button type="button"
                        wire:click="setViewMode('self_service')"
                        role="tab"
                        aria-selected="{{ $effectiveMode === 'self_service' ? 'true' : 'false' }}"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all inline-flex items-center gap-1.5 cursor-pointer {{ $effectiveMode === 'self_service' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>{{ __('ticketing.list.mode_self_service') }}</span>
                    </button>
                    <button type="button"
                        wire:click="setViewMode('agent_queue')"
                        role="tab"
                        aria-selected="{{ $effectiveMode === 'agent_queue' ? 'true' : 'false' }}"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all inline-flex items-center gap-1.5 cursor-pointer {{ $effectiveMode === 'agent_queue' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>{{ __('ticketing.list.mode_agent_queue') }}</span>
                    </button>
                </div>
            @endif

            {{-- New Ticket Primary Button --}}
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

    {{-- ========================================================================= --}}
    {{-- SECTION 1: EMPLOYEE SELF-SERVICE PORTAL (CLEAN STEPPER CARDS)            --}}
    {{-- ========================================================================= --}}
    @if ($effectiveMode === 'self_service')

        {{-- Help-Center Hero Banner --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 text-white p-6 sm:p-8 shadow-xs border border-slate-800/80">
            <div class="absolute -right-10 -bottom-10 h-64 w-64 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
            <div class="absolute -left-10 -top-10 h-64 w-64 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 max-w-2xl space-y-4">
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-white/10 text-white/90 text-[11px] font-medium backdrop-blur-xs">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>IT Service Desk & Support</span>
                </div>

                <div class="space-y-1.5">
                    <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white">
                        {{ __('ticketing.list.hero_greeting', ['name' => auth()->user()->name]) }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-300">
                        {{ __('ticketing.list.hero_subtitle') }}
                    </p>
                </div>

                {{-- Hero Search & Submit Action --}}
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
                    <div class="relative flex-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('ticketing.list.search_placeholder') }}"
                            class="block w-full rounded-xl bg-white/10 hover:bg-white/15 focus:bg-white text-xs text-white focus:text-slate-900 placeholder:text-slate-400 border border-white/10 focus:border-white pl-10 pr-4 py-2.5 transition-all outline-none">
                    </div>
                </div>
            </div>
        </div>

        {{-- Self-Service Simple Tab Bar (Active vs Past) --}}
        <div class="flex items-center justify-between border-b border-slate-200/80 pb-3">
            <div class="flex items-center gap-2" role="tablist" aria-label="Ticket Scope">
                <button type="button"
                    wire:click="setSelfServiceTab('active')"
                    role="tab"
                    aria-selected="{{ $selfServiceTab === 'active' ? 'true' : 'false' }}"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all inline-flex items-center gap-2 cursor-pointer {{ $selfServiceTab === 'active' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    <span>{{ __('ticketing.list.tab_active') }}</span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-full font-bold {{ $selfServiceTab === 'active' ? 'bg-slate-800 text-slate-200' : 'bg-slate-200 text-slate-700' }}">
                        {{ $selfServiceCounts['active'] }}
                    </span>
                </button>

                <button type="button"
                    wire:click="setSelfServiceTab('past')"
                    role="tab"
                    aria-selected="{{ $selfServiceTab === 'past' ? 'true' : 'false' }}"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all inline-flex items-center gap-2 cursor-pointer {{ $selfServiceTab === 'past' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    <span>{{ __('ticketing.list.tab_past') }}</span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-full font-bold {{ $selfServiceTab === 'past' ? 'bg-slate-800 text-slate-200' : 'bg-slate-200 text-slate-700' }}">
                        {{ $selfServiceCounts['past'] }}
                    </span>
                </button>
            </div>

            @if ($search)
                <button type="button" wire:click="$set('search', '')"
                    class="text-xs font-medium text-slate-400 hover:text-rose-600 transition-colors inline-flex items-center gap-1 cursor-pointer">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>{{ __('ticketing.list.clear_filters') }}</span>
                </button>
            @endif
        </div>

        {{-- Simplified Ticket Cards --}}
        <div class="space-y-3">
            @forelse($tickets as $ticket)
                @php
                    $val = $ticket->status->value;
                    $statusKey = strtolower(str_replace(' ', '_', $val));
                    $localizedStatus = __('ticketing.status.' . $statusKey);
                @endphp

                <div wire:click="gotoDetail({{ $ticket->id }})"
                    tabindex="0"
                    role="link"
                    @keydown.enter="$wire.gotoDetail({{ $ticket->id }})"
                    class="group rounded-2xl border border-slate-200/80 bg-white hover:border-slate-300 hover:shadow-xs p-4 sm:p-5 transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-slate-900/10">

                    <div class="flex items-start justify-between gap-4">
                        {{-- Left: Metadata, Title & Assignee --}}
                        <div class="min-w-0 flex-1 space-y-1.5">
                            {{-- Line 1: Ticket # & Subtitle Meta --}}
                            <div class="flex items-center gap-2 text-xs text-slate-400">
                                <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200/60">
                                    {{ $ticket->ticket_number }}
                                </span>
                                <span>•</span>
                                <span class="font-medium text-slate-600">{{ $ticket->category->name }}</span>
                                <span class="hidden sm:inline">•</span>
                                <span class="hidden sm:inline">{{ $ticket->created_at->diffForHumans() }}</span>
                            </div>

                            {{-- Line 2: Title --}}
                            <h3 class="text-sm sm:text-base font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors line-clamp-1">
                                {{ $ticket->title }}
                            </h3>

                            {{-- Line 3: Assignee & Update --}}
                            <div class="flex items-center gap-2 text-xs text-slate-500 pt-0.5">
                                @if ($ticket->assignee)
                                    <div class="flex items-center gap-1.5">
                                        <div class="h-4.5 w-4.5 rounded-full bg-slate-900 text-white flex items-center justify-center text-[9px] font-bold uppercase shrink-0">
                                            {{ substr($ticket->assignee->name, 0, 2) }}
                                        </div>
                                        <span class="font-medium text-slate-700 truncate max-w-[140px]">{{ $ticket->assignee->name }}</span>
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-amber-700 text-[11px] font-medium">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                        <span>{{ __('ticketing.list.in_triage_queue') }}</span>
                                    </span>
                                @endif

                                @if ($ticket->latestActivity)
                                    <span class="text-slate-300 hidden md:inline">•</span>
                                    <span class="text-slate-400 hidden md:inline truncate max-w-sm" title="{{ $ticket->latestActivity->comment }}">
                                        {{ Str::limit($ticket->latestActivity->comment ?? __('ticketing.detail.update_status'), 50) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Right: Status Pill & Disclosure Chevron --}}
                        <div class="flex items-center gap-3 shrink-0 pt-0.5">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border {{ $ticket->status->getBadgeColor() }}">
                                {{ $localizedStatus !== 'ticketing.status.' . $statusKey ? $localizedStatus : $val }}
                            </span>
                            <svg class="h-4 w-4 text-slate-300 group-hover:text-slate-600 group-hover:translate-x-0.5 transition-all shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-slate-200/80 bg-white p-12 text-center shadow-xs">
                    <div class="h-12 w-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900">
                        {{ $selfServiceTab === 'past' ? __('ticketing.list.empty_past_title') : __('ticketing.list.empty_self_service_title') }}
                    </h3>
                    <p class="mt-1 text-xs text-slate-500 max-w-sm mx-auto">
                        {{ $selfServiceTab === 'past' ? __('ticketing.list.empty_past_subtitle') : __('ticketing.list.empty_self_service_subtitle') }}
                    </p>

                    @if ($search)
                        <div class="mt-3">
                            <button type="button" wire:click="$set('search', '')"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors cursor-pointer">
                                {{ __('ticketing.list.clear_filters') }}
                            </button>
                        </div>
                    @else
                        <div class="mt-4">
                            <button type="button"
                                @click.stop="$dispatch('open-support-bubble', { tab: 'new' })"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-2 text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>{{ __('ticketing.list.submit_request') }}</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>

    {{-- ========================================================================= --}}
    {{-- SECTION 2: IT HELPDESK QUEUE (ACTION-FOCUSED TRIAGE MODE)               --}}
    {{-- ========================================================================= --}}
    @else

        {{-- Streamlined Action-Oriented Toolbar --}}
        <div class="space-y-3" x-data="{ showAdvanced: @entangle('showAdvancedFilters') }">
            {{-- Primary Bar: Core Action Tabs & Search --}}
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 border-b border-slate-100 pb-3">
                {{-- Left: 3 Core Action Tabs (Highlights Action Items for IT) --}}
                <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar" role="tablist" aria-label="Action Scope">
                    {{-- Tab 1: Unassigned Action Queue (Default Landing) --}}
                    <button type="button" wire:click="setScope('unassigned')"
                        role="tab"
                        aria-selected="{{ $scopeFilter === 'unassigned' ? 'true' : 'false' }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all inline-flex items-center gap-2 cursor-pointer shrink-0 {{ $scopeFilter === 'unassigned' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <span>{{ __('ticketing.list.tab_unassigned') }}</span>
                        @if ($counts['unassigned'] > 0)
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full font-bold {{ $scopeFilter === 'unassigned' ? 'bg-amber-400 text-slate-950' : 'bg-amber-100 text-amber-800' }}">
                                {{ $counts['unassigned'] }}
                            </span>
                        @endif
                    </button>

                    {{-- Tab 2: Assigned to Me --}}
                    <button type="button" wire:click="setScope('assigned_to_me')"
                        role="tab"
                        aria-selected="{{ $scopeFilter === 'assigned_to_me' ? 'true' : 'false' }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all inline-flex items-center gap-2 cursor-pointer shrink-0 {{ $scopeFilter === 'assigned_to_me' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <span>{{ __('ticketing.list.tab_assigned_to_me') }}</span>
                        @if ($counts['assigned_to_me'] > 0)
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full font-bold {{ $scopeFilter === 'assigned_to_me' ? 'bg-slate-800 text-white' : 'bg-slate-200 text-slate-700' }}">
                                {{ $counts['assigned_to_me'] }}
                            </span>
                        @endif
                    </button>

                    {{-- Tab 3: All Active Incidents --}}
                    <button type="button" wire:click="setScope('all')"
                        role="tab"
                        aria-selected="{{ $scopeFilter === 'all' ? 'true' : 'false' }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all inline-flex items-center gap-2 cursor-pointer shrink-0 {{ $scopeFilter === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <span>{{ __('ticketing.list.tab_all_active') }}</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full font-bold {{ $scopeFilter === 'all' ? 'bg-slate-800 text-slate-200' : 'bg-slate-200 text-slate-600' }}">
                            {{ $counts['all_active'] }}
                        </span>
                    </button>
                </div>

                {{-- Right: Search & Expandable Advanced Filters Toggle --}}
                <div class="flex items-center gap-2.5">
                    {{-- Search Bar --}}
                    <div class="relative w-full sm:w-64 shrink-0">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('ticketing.list.search_placeholder') }}"
                            class="block w-full rounded-xl bg-slate-100/80 border border-transparent pl-9 pr-3.5 py-1.5 text-xs text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-slate-300 focus:outline-none transition-all">
                    </div>

                    {{-- Advanced Filters Toggle Button --}}
                    @php
                        $hasAdvancedFilters = !empty($categoryFilter) || (!empty($statusFilter) && $statusFilter !== '');
                    @endphp
                    <button type="button" wire:click="toggleAdvancedFilters"
                        aria-expanded="{{ $showAdvancedFilters ? 'true' : 'false' }}"
                        class="px-3 py-1.5 rounded-xl border text-xs font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer shrink-0 {{ $showAdvancedFilters || $hasAdvancedFilters ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span>{{ __('ticketing.list.filters') }}</span>
                        @if ($hasAdvancedFilters)
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                        @endif
                    </button>

                    @if ($search || $hasAdvancedFilters || $scopeFilter !== 'unassigned')
                        <button type="button" wire:click="resetFilters"
                            title="{{ __('ticketing.list.clear_filters') }}"
                            class="text-xs font-medium text-slate-400 hover:text-rose-600 transition-colors inline-flex items-center gap-1 cursor-pointer">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span class="hidden sm:inline">{{ __('ticketing.list.clear_filters') }}</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Collapsible Advanced Filters Drawer (Hidden by Default) --}}
            @if ($showAdvancedFilters)
                <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 space-y-3 transition-all"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        {{-- Filter 1: Category Selector --}}
                        <div class="space-y-1 sm:w-64">
                            <label class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">
                                {{ __('ticketing.list.filter_category') }}
                            </label>
                            <select wire:model.live="categoryFilter"
                                class="w-full rounded-xl bg-white border border-slate-200/80 py-1.5 pl-2.5 pr-7 text-xs text-slate-800 font-medium focus:border-slate-400 focus:outline-none transition-colors cursor-pointer">
                                <option value="">{{ __('ticketing.list.all_categories') }}</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Filter 2: Status & Archive Scope Selector --}}
                        <div class="space-y-1 sm:w-72">
                            <label class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">
                                {{ __('ticketing.list.filter_status') }}
                            </label>
                            <select wire:model.live="statusFilter"
                                class="w-full rounded-xl bg-white border border-slate-200/80 py-1.5 pl-2.5 pr-7 text-xs text-slate-800 font-medium focus:border-slate-400 focus:outline-none transition-colors cursor-pointer">
                                <option value="">{{ __('ticketing.list.active_tickets_only') }}</option>
                                <option value="all_history">{{ __('ticketing.list.all_tickets_history') }}</option>
                                <option disabled>──────────</option>
                                @foreach ($statuses as $st)
                                    @php
                                        $val = $st->value;
                                        $sKey = strtolower(str_replace(' ', '_', $val));
                                        $sLabel = __('ticketing.status.' . $sKey);
                                    @endphp
                                    <option value="{{ $val }}">{{ $sLabel !== 'ticketing.status.' . $sKey ? $sLabel : $val }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Reset Action --}}
                        @if ($hasAdvancedFilters)
                            <div class="sm:pt-5">
                                <button type="button" wire:click="resetFilters"
                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors cursor-pointer">
                                    {{ __('ticketing.list.clear_filters') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Linear High-Density Scannable Table --}}
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

                        {{-- Left Section: Title and Metadata --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span class="font-mono text-xs font-semibold text-slate-400 shrink-0">
                                    {{ $ticket->ticket_number }}
                                </span>
                                <h3 class="text-sm font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors truncate">
                                    {{ $ticket->title }}
                                </h3>

                                {{-- Context Indicators (Screenshot, URL, Comments) --}}
                                <div class="inline-flex items-center gap-1.5 ml-1 text-slate-400">
                                    @if ($ticket->screenshot_path)
                                        <span title="{{ __('ticketing.detail.has_screenshot') }}" class="hover:text-slate-600">
                                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </span>
                                    @endif
                                    @if ($ticket->current_url)
                                        <span title="{{ __('ticketing.detail.has_url') }}" class="hover:text-slate-600">
                                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                            </svg>
                                        </span>
                                    @endif
                                    @if ($ticket->activities_count > 0)
                                        <span class="inline-flex items-center gap-0.5 text-[11px] font-semibold text-slate-400" title="{{ $ticket->activities_count }} {{ __('ticketing.detail.activities') }}">
                                            <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            <span>{{ $ticket->activities_count }}</span>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Clean Subtitle Metadata Line --}}
                            <div class="flex items-center gap-1.5 text-xs text-slate-500 font-normal truncate">
                                <span class="text-slate-600 font-medium">{{ $ticket->category->name }}</span>
                                <span class="text-slate-300">•</span>
                                <span>{{ optional($ticket->reporter)->name ?? 'User' }}</span>
                                @if ($ticket->reporter_id)
                                    <span class="text-slate-400 font-mono text-[11px]">(NIK: {{ $ticket->reporter_id }})</span>
                                @endif
                                @if (optional(optional($ticket->reporter)->department)->name)
                                    <span class="text-slate-400 hidden md:inline">• {{ optional($ticket->reporter->department)->name }}</span>
                                @endif
                                <span class="text-slate-300 hidden sm:inline">•</span>
                                <span class="text-slate-400 hidden sm:inline" title="{{ $ticket->created_at->format('M d, Y H:i') }}">
                                    {{ $ticket->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>

                        {{-- Right Section: SLA, Status, Assignee, Chevron --}}
                        <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
                            {{-- SLA Status --}}
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
                                    <button type="button"
                                        wire:click.stop="claimTicket({{ $ticket->id }})"
                                        title="{{ __('ticketing.list.claim_ticket') }}"
                                        class="rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 px-2 py-0.5 text-[11px] font-semibold transition-colors cursor-pointer">
                                        {{ __('ticketing.list.claim') }}
                                    </button>
                                </div>
                            @endif

                            {{-- Disclosure Chevron --}}
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
                        @if ($search || $statusFilter || $categoryFilter || $scopeFilter !== 'all')
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
        </div>

    @endif

    {{-- Clean Unified Pagination --}}
    @if ($tickets->hasPages())
        <div class="border-t border-slate-100 pt-3">
            {{ $tickets->links() }}
        </div>
    @endif
</div>
