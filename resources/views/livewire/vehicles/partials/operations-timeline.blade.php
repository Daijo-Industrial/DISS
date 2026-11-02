<div class="space-y-4">
    {{-- Navigation Bar & Period Summary --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-3.5 sm:p-4 shadow-xs space-y-3">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3">
            
            {{-- Left: Scope-Specific Date Controls --}}
            <div class="flex flex-wrap items-center gap-2">
                @if ($scope === 'day')
                    {{-- Week Navigation Strip: [ ‹ ] [ Sen 26 ] ... [ Min 1 ] [ › ] --}}
                    <div class="inline-flex items-center rounded-2xl border border-slate-200/80 bg-slate-50/70 p-1 shadow-2xs gap-0.5 sm:gap-1">
                        {{-- Previous Week Stepper --}}
                        <button type="button" wire:click="previousWeek" title="{{ __('fleet.operations.previous_week') }}"
                            class="p-1 sm:p-1.5 rounded-xl hover:bg-white text-slate-600 hover:text-slate-900 transition active:scale-95 cursor-pointer">
                            <i class="bi bi-chevron-left text-xs"></i>
                        </button>

                        {{-- 7 Days of the Week --}}
                        @foreach ($this->weekDays as $day)
                            <button type="button"
                                @if (! $day['is_future']) wire:click="selectDate('{{ $day['date'] }}')" @endif
                                @disabled($day['is_future'])
                                class="relative flex flex-col items-center justify-center px-1.5 sm:px-2.5 py-1 rounded-xl text-xs transition {{ $day['is_selected'] ? 'bg-slate-900 text-white font-bold shadow-xs' : ($day['is_future'] ? 'opacity-30 text-slate-400 cursor-not-allowed' : 'text-slate-600 hover:bg-white hover:text-slate-900 cursor-pointer active:scale-95') }}">
                                {{-- Small Red Dot Indicator if Day has Defect --}}
                                @if (! empty($day['has_defect']))
                                    <span class="absolute top-1 right-1 sm:top-1.5 sm:right-1.5 h-1.5 w-1.5 rounded-full bg-rose-500 ring-1 {{ $day['is_selected'] ? 'ring-slate-900' : 'ring-white' }}"
                                        title="{{ __('fleet.operations.defect_reported') }}"></span>
                                @endif

                                <span class="text-[9px] uppercase font-bold {{ $day['is_selected'] ? 'text-slate-300' : 'text-slate-400' }}">
                                    {{ $day['day_short'] }}
                                </span>
                                <span class="text-xs font-mono font-bold leading-tight flex items-center gap-0.5">
                                    {{ $day['day_number'] }}
                                    @if ($day['is_today'] && ! $day['is_selected'])
                                        <span class="h-1 w-1 rounded-full bg-indigo-600"></span>
                                    @endif
                                </span>
                            </button>
                        @endforeach

                        {{-- Next Week Stepper --}}
                        <button type="button" wire:click="nextWeek" title="{{ __('fleet.operations.next_week') }}"
                            @disabled(! $this->canGoNextWeek)
                            class="p-1 sm:p-1.5 rounded-xl transition {{ ! $this->canGoNextWeek ? 'opacity-30 text-slate-400 cursor-not-allowed' : 'hover:bg-white text-slate-600 hover:text-slate-900 cursor-pointer active:scale-95' }}">
                            <i class="bi bi-chevron-right text-xs"></i>
                        </button>
                    </div>

                    {{-- Fast Calendar Jump Picker [ 📅 ] --}}
                    <div class="relative inline-flex items-center rounded-2xl border border-slate-200/80 bg-white p-1 shadow-2xs" title="{{ __('fleet.operations.filter_custom') }}">
                        <input type="date" wire:model.live="selectedDate" max="{{ now()->toDateString() }}"
                            class="rounded-xl border-0 bg-transparent py-1 px-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 focus:bg-white focus:ring-1 focus:ring-indigo-500 focus:outline-none transition cursor-pointer font-mono">
                    </div>

                    {{-- Quick Jump to Today --}}
                    @if (! $this->isToday)
                        <button type="button" wire:click="goToToday"
                            class="px-2.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition active:scale-95 cursor-pointer">
                            {{ __('fleet.operations.filter_today') }}
                        </button>
                    @endif

                @elseif ($scope === 'week')
                    {{-- Weekly Scope Navigator: [ ‹ ] [ 26 Okt - 01 Nov 2026 ] [ › ] --}}
                    <div class="inline-flex items-center rounded-2xl border border-slate-200/80 bg-slate-50/70 p-1 shadow-2xs gap-1">
                        <button type="button" wire:click="previousWeek" title="{{ __('fleet.operations.previous_week') }}"
                            class="p-1.5 rounded-xl hover:bg-white text-slate-600 hover:text-slate-900 transition active:scale-95 cursor-pointer">
                            <i class="bi bi-chevron-left text-xs"></i>
                        </button>
                        <span class="px-3 py-1 font-bold text-xs text-slate-800">
                            {{ $periodLog->label }}
                        </span>
                        <button type="button" wire:click="nextWeek" title="{{ __('fleet.operations.next_week') }}"
                            @disabled(! $this->canGoNextWeek)
                            class="p-1.5 rounded-xl transition {{ ! $this->canGoNextWeek ? 'opacity-30 text-slate-400 cursor-not-allowed' : 'hover:bg-white text-slate-600 hover:text-slate-900 cursor-pointer active:scale-95' }}">
                            <i class="bi bi-chevron-right text-xs"></i>
                        </button>
                    </div>

                    @if ($this->canGoNextWeek)
                        <button type="button" wire:click="goToThisWeek"
                            class="px-2.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition active:scale-95 cursor-pointer">
                            {{ __('fleet.operations.this_week') }}
                        </button>
                    @endif

                @elseif ($scope === 'month')
                    {{-- Monthly Scope Navigator: [ ‹ ] [ Input Month ] [ › ] --}}
                    <div class="inline-flex items-center rounded-2xl border border-slate-200/80 bg-slate-50/70 p-1 shadow-2xs gap-1">
                        <button type="button" wire:click="previousMonth" title="{{ __('fleet.operations.previous_month') }}"
                            class="p-1.5 rounded-xl hover:bg-white text-slate-600 hover:text-slate-900 transition active:scale-95 cursor-pointer">
                            <i class="bi bi-chevron-left text-xs"></i>
                        </button>
                        <div class="relative inline-flex items-center">
                            <input type="month" wire:model.live="selectedMonth" max="{{ now()->format('Y-m') }}"
                                class="rounded-xl border-0 bg-transparent py-1 px-2.5 text-xs font-bold text-slate-800 hover:bg-slate-50 focus:bg-white focus:ring-1 focus:ring-indigo-500 focus:outline-none transition cursor-pointer font-mono">
                        </div>
                        <button type="button" wire:click="nextMonth" title="{{ __('fleet.operations.next_month') }}"
                            @disabled(! $this->canGoNextMonth)
                            class="p-1.5 rounded-xl transition {{ ! $this->canGoNextMonth ? 'opacity-30 text-slate-400 cursor-not-allowed' : 'hover:bg-white text-slate-600 hover:text-slate-900 cursor-pointer active:scale-95' }}">
                            <i class="bi bi-chevron-right text-xs"></i>
                        </button>
                    </div>

                    @if ($this->canGoNextMonth)
                        <button type="button" wire:click="goToThisMonth"
                            class="px-2.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition active:scale-95 cursor-pointer">
                            {{ __('fleet.operations.this_month') }}
                        </button>
                    @endif

                @elseif ($scope === 'custom')
                    {{-- Custom Date Range: [ Start Date ] → [ End Date ] + Presets --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="inline-flex items-center rounded-2xl border border-slate-200/80 bg-white p-1 shadow-2xs gap-1 text-xs">
                            <span class="text-[11px] font-semibold text-slate-400 pl-2">{{ __('fleet.operations.start_date') }}:</span>
                            <input type="date" wire:model.live="startDate" max="{{ now()->toDateString() }}"
                                class="rounded-xl border-0 bg-transparent py-1 px-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 focus:bg-white focus:ring-1 focus:ring-indigo-500 focus:outline-none transition cursor-pointer font-mono">
                            <span class="text-slate-300">→</span>
                            <span class="text-[11px] font-semibold text-slate-400">{{ __('fleet.operations.end_date') }}:</span>
                            <input type="date" wire:model.live="endDate" max="{{ now()->toDateString() }}"
                                class="rounded-xl border-0 bg-transparent py-1 px-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 focus:bg-white focus:ring-1 focus:ring-indigo-500 focus:outline-none transition cursor-pointer font-mono">
                        </div>

                        <div class="inline-flex items-center gap-1">
                            <button type="button" wire:click="setCustomPreset('last_7_days')"
                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200/70 text-slate-700 text-xs font-medium transition active:scale-95 cursor-pointer">
                                {{ __('fleet.operations.preset_last_7') }}
                            </button>
                            <button type="button" wire:click="setCustomPreset('last_30_days')"
                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200/70 text-slate-700 text-xs font-medium transition active:scale-95 cursor-pointer">
                                {{ __('fleet.operations.preset_last_30') }}
                            </button>
                            <button type="button" wire:click="setCustomPreset('this_month')"
                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200/70 text-slate-700 text-xs font-medium transition active:scale-95 cursor-pointer">
                                {{ __('fleet.operations.preset_this_month') }}
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right: Period Summary & Activity Filter Badges --}}
            <div class="flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                <button type="button" wire:click="setActivityFilter('all')"
                    class="inline-flex items-center px-2.5 py-1 rounded-xl font-bold transition cursor-pointer active:scale-95 {{ $activityFilter === 'all' ? 'bg-slate-900 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200/70' }}">
                    {{ __('fleet.operations.completed_trips_count', ['count' => $periodLog->total_trips]) }}
                </button>

                @if ($periodLog->total_distance > 0)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-slate-100 text-slate-700 font-bold font-mono" title="{{ __('fleet.operations.total_mileage') }}">
                        +{{ number_format($periodLog->total_distance) }} km
                    </span>
                @endif

                @if ($periodLog->total_defects > 0)
                    <button type="button" wire:click="setActivityFilter('defects')"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl font-bold transition cursor-pointer active:scale-95 {{ $activityFilter === 'defects' ? 'bg-rose-600 text-white shadow-2xs' : 'bg-rose-50 text-rose-700 border border-rose-200/80 hover:bg-rose-100' }}">
                        <i class="bi bi-exclamation-circle-fill text-[11px]"></i>
                        {{ $periodLog->total_defects }} {{ __('fleet.operations.defects') }}
                        @if ($activityFilter === 'defects')
                            <i class="bi bi-x-circle-fill text-[10px] ml-0.5 opacity-80 hover:opacity-100"></i>
                        @endif
                    </button>
                @endif

                @if ($periodLog->total_services > 0)
                    <button type="button" wire:click="setActivityFilter('services')"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl font-bold transition cursor-pointer active:scale-95 {{ $activityFilter === 'services' ? 'bg-purple-600 text-white shadow-2xs' : 'bg-purple-50 text-purple-700 border border-purple-200/80 hover:bg-purple-100' }}">
                        <i class="bi bi-wrench text-[11px]"></i>
                        {{ $periodLog->total_services }} {{ __('fleet.operations.services') }}
                        @if ($activityFilter === 'services')
                            <i class="bi bi-x-circle-fill text-[10px] ml-0.5 opacity-80 hover:opacity-100"></i>
                        @endif
                    </button>
                @endif

                @if ($canViewCosts && $periodLog->total_service_cost > 0)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200/80 font-bold font-mono text-xs" title="{{ __('fleet.operations.total_repair_cost') }}">
                        Rp {{ number_format($periodLog->total_service_cost, 0, ',', '.') }}
                    </span>
                @endif

                @if ($activityFilter !== 'all')
                    <button type="button" wire:click="setActivityFilter('all')"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition cursor-pointer active:scale-95" title="{{ __('fleet.operations.clear_filter') }}">
                        <i class="bi bi-x-circle text-xs text-slate-400"></i>
                        <span>{{ __('fleet.operations.clear_filter') }}</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- Active Period Title Bar --}}
        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full {{ ($scope === 'day' && $periodLog->is_today) ? 'bg-indigo-600 ring-4 ring-indigo-100' : (($scope === 'day' && $periodLog->is_yesterday) ? 'bg-amber-500' : 'bg-slate-400') }}"></span>
                <span class="font-bold text-slate-900 capitalize">{{ $periodLog->label }}</span>
            </div>
            <span class="text-[11px] text-slate-400 font-medium">
                {{ $scope === 'day' ? __('fleet.operations.day_log_title') : __('fleet.operations.period_summary') }}
            </span>
        </div>

        {{-- Active Filter Announcement Bar --}}
        @if ($activityFilter === 'defects')
            <div class="flex items-center justify-between px-3 py-1.5 rounded-xl bg-rose-50 border border-rose-200/80 text-xs text-rose-800">
                <div class="flex items-center gap-1.5 font-medium">
                    <i class="bi bi-funnel-fill text-rose-600"></i>
                    <span>{{ __('fleet.operations.filter_active_defects') }}</span>
                </div>
                <button type="button" wire:click="setActivityFilter('all')"
                    class="font-semibold text-rose-700 hover:text-rose-900 underline text-[11px] cursor-pointer inline-flex items-center gap-1">
                    <i class="bi bi-x-circle"></i>
                    <span>{{ __('fleet.operations.show_all_activities') }}</span>
                </button>
            </div>
        @elseif ($activityFilter === 'services')
            <div class="flex items-center justify-between px-3 py-1.5 rounded-xl bg-purple-50 border border-purple-200/80 text-xs text-purple-800">
                <div class="flex items-center gap-1.5 font-medium">
                    <i class="bi bi-funnel-fill text-purple-600"></i>
                    <span>{{ __('fleet.operations.filter_active_services') }}</span>
                </div>
                <button type="button" wire:click="setActivityFilter('all')"
                    class="font-semibold text-purple-700 hover:text-purple-900 underline text-[11px] cursor-pointer inline-flex items-center gap-1">
                    <i class="bi bi-x-circle"></i>
                    <span>{{ __('fleet.operations.show_all_activities') }}</span>
                </button>
            </div>
        @endif
    </div>

    {{-- Timeline Feed Content --}}
    @if ($periodLog->has_activity)
        @if ($scope === 'day')
            {{-- Single-Day Mode: Chronological Vertical Timeline with Terminal Node --}}
            <div class="relative border-l-2 border-slate-200/80 ml-3.5 sm:ml-5 space-y-5 sm:space-y-6 pt-1 pb-2">
                @foreach ($periodLog->timeline_events as $event)
                    @include('livewire.vehicles.partials.operations-timeline-event-card', ['event' => $event, 'canViewCosts' => $canViewCosts])
                @endforeach

                {{-- Timeline Terminal Node (Start of Operational Day) --}}
                <div class="relative pl-5 sm:pl-7 pt-1 text-xs text-slate-400 flex items-center gap-2">
                    <span class="absolute -left-[7px] top-2 h-3.5 w-3.5 rounded-full bg-slate-300 ring-4 ring-white shadow-2xs"></span>
                    <span class="font-medium text-slate-500 text-[11px]">
                        {{ __('fleet.operations.timeline_start_of_day') }} — {{ $periodLog->date_carbon->translatedFormat('d F Y') }}
                    </span>
                </div>
            </div>
        @else
            {{-- Multi-Day Mode (Week, Month, Custom): Date-Grouped Timeline with Sticky Headers --}}
            <div class="space-y-6">
                @foreach ($periodLog->grouped_timeline as $group)
                    <div class="space-y-4">
                        {{-- Sticky Day Header Bar --}}
                        <div class="sticky top-2 z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2.5 sm:p-3 rounded-2xl bg-slate-50/95 backdrop-blur-md border border-slate-200/80 shadow-2xs text-xs">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full {{ $group->is_today ? 'bg-indigo-600 ring-4 ring-indigo-100' : ($group->is_yesterday ? 'bg-amber-500' : 'bg-slate-400') }}"></span>
                                <span class="font-bold text-slate-900 capitalize">{{ $group->date_label }}</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-[11px] font-mono text-slate-500">
                                <span class="font-bold text-slate-700">{{ __('fleet.operations.completed_trips_count', ['count' => $group->total_trips]) }}</span>
                                @if ($group->total_distance > 0)
                                    <span class="text-slate-300">•</span>
                                    <span class="font-bold text-slate-700">+{{ number_format($group->total_distance) }} km</span>
                                @endif
                                @if ($group->total_defects > 0)
                                    <span class="text-slate-300">•</span>
                                    <span class="font-bold text-rose-600">{{ $group->total_defects }} {{ __('fleet.operations.defects') }}</span>
                                @endif
                                @if ($group->total_services > 0)
                                    <span class="text-slate-300">•</span>
                                    <span class="font-bold text-purple-600">{{ $group->total_services }} {{ __('fleet.operations.services') }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Day Events Spine --}}
                        <div class="relative border-l-2 border-slate-200/80 ml-3.5 sm:ml-5 space-y-4 sm:space-y-5 pt-1 pb-2">
                            @foreach ($group->events as $event)
                                @include('livewire.vehicles.partials.operations-timeline-event-card', ['event' => $event, 'canViewCosts' => $canViewCosts])
                            @endforeach

                            {{-- Day Terminal Node --}}
                            <div class="relative pl-5 sm:pl-7 pt-1 text-xs text-slate-400 flex items-center gap-2">
                                <span class="absolute -left-[7px] top-2 h-3.5 w-3.5 rounded-full bg-slate-300 ring-4 ring-white shadow-2xs"></span>
                                <span class="font-medium text-slate-400 text-[11px]">
                                    {{ __('fleet.operations.timeline_start_of_day') }} — {{ $group->date_carbon->translatedFormat('d F Y') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        @if ($periodLog->has_any_day_activity)
            {{-- Filter resulted in empty set --}}
            <div class="rounded-2xl border border-dashed border-slate-200/90 bg-white p-8 text-center text-xs text-slate-400 space-y-2 shadow-2xs">
                <i class="bi bi-funnel text-2xl text-slate-300 block mb-1"></i>
                <h3 class="font-bold text-slate-700 text-sm">Tidak ada catatan untuk filter ini pada periode ini.</h3>
                <p class="text-slate-400 text-[11px]">Coba ubah filter kategori atau klik tombol di bawah untuk menampilkan semua aktivitas.</p>
                <button type="button" wire:click="setActivityFilter('all')" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition cursor-pointer">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <span>Tampilkan Semua ({{ $periodLog->total_trips + $periodLog->total_services }})</span>
                </button>
            </div>
        @else
            {{-- Quiet / No Activity for this period --}}
            <div class="rounded-2xl border border-dashed border-slate-200/90 bg-white p-8 sm:p-12 text-center text-xs text-slate-400 space-y-1.5 shadow-2xs">
                <i class="bi bi-calendar2-check text-3xl text-slate-300 block mb-2"></i>
                <h3 class="font-bold text-slate-700 text-sm">{{ $scope === 'day' ? __('fleet.operations.no_day_activity') : __('fleet.operations.no_period_activity_title') }}</h3>
                <p class="text-slate-400 text-[11px]">{{ $scope === 'day' ? __('fleet.operations.all_in_pool_desc') : __('fleet.operations.no_period_activity_desc') }}</p>
            </div>
        @endif
    @endif
</div>
