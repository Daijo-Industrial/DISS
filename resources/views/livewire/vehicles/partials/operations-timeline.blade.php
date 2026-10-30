<div class="space-y-4">
    {{-- Single-Day Navigation Bar & Period Summary --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-3.5 sm:p-4 shadow-xs space-y-3">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3">
            {{-- Left: Weekly Day-Strip Navigation & Calendar Picker --}}
            <div class="flex flex-wrap items-center gap-2">
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
                            class="flex flex-col items-center justify-center px-1.5 sm:px-2.5 py-1 rounded-xl text-xs transition {{ $day['is_selected'] ? 'bg-slate-900 text-white font-bold shadow-xs' : ($day['is_future'] ? 'opacity-30 text-slate-400 cursor-not-allowed' : 'text-slate-600 hover:bg-white hover:text-slate-900 cursor-pointer active:scale-95') }}">
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

                {{-- Quick Jump to Today (if not on today) --}}
                @if (! $this->isToday)
                    <button type="button" wire:click="goToToday"
                        class="px-2.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition active:scale-95 cursor-pointer">
                        {{ __('fleet.operations.filter_today') }}
                    </button>
                @endif
            </div>

            {{-- Right: Date Label & Interactive Activity Filter Badges --}}
            <div class="flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                <button type="button" wire:click="setActivityFilter('all')"
                    class="inline-flex items-center px-2.5 py-1 rounded-xl font-bold transition cursor-pointer active:scale-95 {{ $activityFilter === 'all' ? 'bg-slate-900 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200/70' }}">
                    {{ __('fleet.operations.completed_trips_count', ['count' => $dayLog->total_trips]) }}
                </button>

                @if ($dayLog->total_distance > 0)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-slate-100 text-slate-700 font-bold font-mono">
                        +{{ number_format($dayLog->total_distance) }} km
                    </span>
                @endif

                @if ($dayLog->total_defects > 0)
                    <button type="button" wire:click="setActivityFilter('defects')"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl font-bold transition cursor-pointer active:scale-95 {{ $activityFilter === 'defects' ? 'bg-rose-600 text-white shadow-2xs' : 'bg-rose-50 text-rose-700 border border-rose-200/80 hover:bg-rose-100' }}">
                        <i class="bi bi-exclamation-circle-fill text-[11px]"></i>
                        {{ $dayLog->total_defects }} {{ __('fleet.operations.defects') }}
                    </button>
                @endif

                @if ($dayLog->total_services > 0)
                    <button type="button" wire:click="setActivityFilter('services')"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl font-bold transition cursor-pointer active:scale-95 {{ $activityFilter === 'services' ? 'bg-purple-600 text-white shadow-2xs' : 'bg-purple-50 text-purple-700 border border-purple-200/80 hover:bg-purple-100' }}">
                        <i class="bi bi-wrench text-[11px]"></i>
                        {{ $dayLog->total_services }} {{ __('fleet.operations.services') }}
                    </button>
                @endif
            </div>
        </div>

        {{-- Active Day Title Bar --}}
        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full {{ $dayLog->is_today ? 'bg-indigo-600 ring-4 ring-indigo-100' : ($dayLog->is_yesterday ? 'bg-amber-500' : 'bg-slate-400') }}"></span>
                <span class="font-bold text-slate-900 capitalize">{{ $dayLog->label }}</span>
            </div>
            <span class="text-[11px] text-slate-400 font-medium">
                {{ __('fleet.operations.day_log_title') }}
            </span>
        </div>
    </div>

    {{-- Day Content: Chronological Vertical Timeline with Progressive Disclosure Drill-Down --}}
    @if ($dayLog->has_activity)
        <div class="relative border-l-2 border-slate-200/80 ml-3.5 sm:ml-5 space-y-5 sm:space-y-6 pt-1 pb-2">
            @foreach ($dayLog->timeline_events as $event)
                @if ($event->type === 'trip_completed')
                    @php
                        $trip = $event;
                        $v = $trip->vehicle;
                    @endphp
                    <div wire:key="timeline-trip-{{ $trip->id }}" class="relative pl-5 sm:pl-7">

                        {{-- Timeline Node on the Left Spine --}}
                        <div class="absolute -left-[15px] sm:-left-[17px] top-3.5 flex items-center justify-center">
                            @if ($trip->has_defect)
                                <span class="flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-rose-600 text-white shadow-2xs ring-4 ring-rose-100" title="{{ __('fleet.operations.defect_reported') }}">
                                    <i class="bi bi-exclamation-triangle-fill text-[11px] sm:text-xs"></i>
                                </span>
                            @elseif ($trip->is_direct_check_in)
                                <span class="flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-purple-600 text-white shadow-2xs ring-4 ring-purple-100" title="{{ __('fleet.show.direct_check_in') }}">
                                    <i class="bi bi-box-arrow-in-down-left text-[11px] sm:text-xs"></i>
                                </span>
                            @else
                                <span class="flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-emerald-600 text-white shadow-2xs ring-4 ring-emerald-100" title="{{ __('fleet.operations.passed_p2h') }}">
                                    <i class="bi bi-check-lg text-xs sm:text-sm font-black"></i>
                                </span>
                            @endif
                        </div>

                        {{-- Event Card --}}
                        <div class="rounded-2xl border transition-all duration-200 shadow-xs hover:shadow-sm {{ $trip->has_defect ? 'border-rose-300/80 bg-rose-50/15' : 'border-slate-200/90 bg-white' }} p-3.5 sm:p-4 space-y-3">
                            
                            {{-- Top Header Row: Time, Type, Status --}}
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 border-b border-slate-100">
                                <div class="flex flex-wrap items-center gap-2">
                                    {{-- Primary Event Time Badge --}}
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-900 text-white font-mono text-xs font-bold shadow-2xs">
                                        <i class="bi bi-clock text-[10px] text-slate-300"></i>
                                        <span>{{ $trip->ret_time ? $trip->ret_time->format('H:i') . ' WIB' : '—' }}</span>
                                    </span>

                                    {{-- Trip Span or Direct Check-in Tag --}}
                                    @if ($trip->is_direct_check_in)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200/80 text-[11px] font-bold">
                                            <i class="bi bi-box-arrow-in-down-left text-[10px]"></i>
                                            <span>{{ __('fleet.show.direct_check_in') }}</span>
                                        </span>
                                    @elseif ($trip->dep_time)
                                        <span class="text-xs text-slate-600 font-medium">
                                            {{ $trip->dep_time->format('H:i') }} → {{ $trip->ret_time ? $trip->ret_time->format('H:i') : '' }}
                                            @if ($trip->duration_text)
                                                <span class="text-slate-400 font-mono text-[11px]">({{ $trip->duration_text }})</span>
                                            @endif
                                        </span>
                                    @endif
                                </div>

                                {{-- Right: Health Badge --}}
                                <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
                                    @if ($trip->has_defect)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-100 text-rose-800 text-[10px] font-bold ring-1 ring-inset ring-rose-200">
                                            <i class="bi bi-exclamation-triangle-fill text-[9px]"></i>
                                            <span>{{ __('fleet.operations.defect_reported') }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-semibold ring-1 ring-inset ring-emerald-200">
                                            <i class="bi bi-check-circle-fill text-[9px]"></i>
                                            <span>{{ __('fleet.operations.passed_p2h') }}</span>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Main Body: Vehicle, Driver, Purpose & Defect Notes --}}
                            <div class="flex items-start gap-3 min-w-0">
                                @if ($v?->image_path)
                                    <button type="button" @click.stop="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $v->image_path) }}', title: '{{ __('fleet.show.lightbox_vehicle_photo', ['plate' => $v->plate_number]) }}', subtitle: '{{ trim($v->brand . ' ' . $v->model) }}' })"
                                        class="shrink-0 cursor-pointer">
                                        <img src="{{ asset('storage/' . $v->image_path) }}" alt="{{ $v->plate_number }}"
                                            class="h-10 w-10 rounded-xl object-cover border border-slate-200/80 shadow-2xs">
                                    </button>
                                @else
                                    <span class="h-10 w-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-sm shrink-0">
                                        <i class="bi bi-car-front-fill"></i>
                                    </span>
                                @endif

                                <div class="min-w-0 space-y-0.5 flex-1">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ $v ? route('vehicles.show', ['vehicle' => $v, 'from' => 'operations']) : '#' }}" wire:navigate
                                            class="font-mono text-sm font-bold text-slate-900 hover:text-indigo-600 transition whitespace-nowrap">
                                            {{ $v?->plate_number ?: '—' }}
                                        </a>
                                        <span class="text-slate-300">•</span>
                                        <span class="text-slate-600 text-xs truncate">{{ trim(($v?->brand ?? '') . ' ' . ($v?->model ?? '')) }}</span>
                                    </div>

                                    <div class="text-xs text-slate-600 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                        <span class="font-medium text-slate-800 flex items-center gap-1">
                                            <i class="bi bi-person text-slate-400"></i>
                                            {{ $trip->driver_name }}
                                        </span>
                                        @if ($trip->trip_purpose)
                                            <span class="text-slate-300">•</span>
                                            <span class="italic text-slate-500 truncate max-w-sm">"{{ $trip->trip_purpose }}"</span>
                                        @endif
                                    </div>

                                    @if ($trip->has_defect && $trip->defect_notes)
                                        <div class="text-[11px] font-medium text-rose-700 flex items-center gap-1.5 pt-0.5">
                                            <i class="bi bi-exclamation-triangle-fill text-rose-500 text-[10px] shrink-0"></i>
                                            <span>{{ $trip->defect_notes }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Unified Footer Ledger Bar --}}
                            <div class="pt-2.5 border-t border-slate-100 flex flex-wrap items-center justify-between gap-x-4 gap-y-1.5 text-xs text-slate-500">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 font-mono text-[11px]">
                                    @if ($trip->is_direct_check_in)
                                        <span class="font-bold text-slate-900">{{ number_format($trip->ret_odometer) }} km</span>
                                        <span class="text-slate-300">•</span>
                                        <span class="text-slate-600">BBM {{ $trip->ret_fuel ?? '-' }}%</span>
                                    @else
                                        <span class="font-bold text-slate-900">+{{ number_format($trip->distance) }} km</span>
                                        <span class="text-slate-400">({{ number_format($trip->dep_odometer) }} → {{ number_format($trip->ret_odometer) }} km)</span>
                                        @if ($trip->ret_fuel !== null)
                                            <span class="text-slate-300">•</span>
                                            @php
                                                $fuelDelta = ($trip->dep_fuel !== null) ? ((int) $trip->ret_fuel - (int) $trip->dep_fuel) : 0;
                                            @endphp
                                            <span class="text-slate-600">
                                                BBM {{ $trip->ret_fuel }}%
                                                @if ($trip->dep_fuel !== null && $fuelDelta !== 0)
                                                    <span class="{{ $fuelDelta < 0 ? 'text-slate-500' : 'text-emerald-600 font-semibold' }}">({{ $fuelDelta > 0 ? '+' . $fuelDelta : $fuelDelta }}%)</span>
                                                @endif
                                            </span>
                                        @endif
                                    @endif
                                    <span class="text-slate-300">•</span>
                                    <span class="font-sans text-slate-600">
                                        <span class="text-slate-400">{{ __('fleet.operations.inspector') }}:</span>
                                        <span class="font-medium text-slate-700">{{ $trip->inspector_name }}</span>
                                    </span>
                                </div>

                                @if ($v)
                                    <a href="{{ route('vehicles.show', ['vehicle' => $v->id, 'tab' => 'inspections', 'from' => 'operations']) }}" wire:navigate
                                        class="text-indigo-600 hover:text-indigo-800 text-[11px] font-semibold transition inline-flex items-center gap-1 shrink-0">
                                        <span>{{ __('fleet.operations.view_vehicle') }}</span>
                                        <i class="bi bi-arrow-right text-[10px]"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif ($event->type === 'service')
                    @php
                        $service = $event;
                        $sv = $service->vehicle;
                    @endphp
                    <div wire:key="timeline-service-{{ $service->id }}" class="relative pl-5 sm:pl-7">
                        {{-- Timeline Node on the Left Spine --}}
                        <div class="absolute -left-[15px] sm:-left-[17px] top-3.5 flex items-center justify-center">
                            <span class="flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-purple-600 text-white shadow-2xs ring-4 ring-purple-100" title="{{ __('fleet.operations.service_workshop') }}">
                                <i class="bi bi-wrench text-[11px] sm:text-xs"></i>
                            </span>
                        </div>

                        {{-- Service Event Card --}}
                        <div class="rounded-2xl border border-purple-200/80 bg-purple-50/20 p-3.5 sm:p-4 text-xs space-y-3 shadow-xs">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 border-b border-purple-100">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-purple-700 text-white font-mono text-xs font-bold shadow-2xs">
                                        <i class="bi bi-wrench text-[10px] text-purple-200"></i>
                                        <span>{{ __('fleet.operations.service_workshop') }}</span>
                                    </span>
                                    <span class="text-xs text-purple-900 font-semibold">
                                        {{ $service->workshop }}
                                    </span>
                                </div>

                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 text-[10px] font-bold self-end sm:self-auto">
                                    <i class="bi bi-tools text-[9px]"></i>
                                    <span>{{ __('fleet.operations.routine_service') }}</span>
                                </span>
                            </div>

                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="h-10 w-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-sm shrink-0">
                                        <i class="bi bi-tools"></i>
                                    </span>
                                    <div class="min-w-0 space-y-0.5">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ $sv ? route('vehicles.show', ['vehicle' => $sv->id, 'tab' => 'services', 'from' => 'operations']) : '#' }}" wire:navigate
                                                class="font-mono font-bold text-slate-900 hover:text-purple-700 transition whitespace-nowrap">
                                                {{ $sv?->plate_number ?: '—' }}
                                            </a>
                                            <span class="text-slate-300">•</span>
                                            <span class="text-slate-600 truncate">{{ trim(($sv?->brand ?? '') . ' ' . ($sv?->model ?? '')) }}</span>
                                        </div>
                                        <p class="text-slate-600 truncate text-xs">
                                            {{ $service->notes ?: __('fleet.operations.routine_service') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 self-start md:self-center shrink-0">
                                    <span class="px-2.5 py-1 rounded-xl bg-white border border-purple-200/80 font-mono text-xs text-slate-700">
                                        {{ number_format($service->odometer) }} km
                                    </span>
                                    @if ($canViewCosts && $service->total_cost)
                                        <span class="px-2.5 py-1 rounded-xl bg-purple-100 text-purple-900 font-mono font-bold text-xs">
                                            Rp {{ number_format($service->total_cost, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach

            {{-- Timeline Terminal Node (Start of Operational Day) --}}
            <div class="relative pl-5 sm:pl-7 pt-1 text-xs text-slate-400 flex items-center gap-2">
                <span class="absolute -left-[7px] top-2 h-3.5 w-3.5 rounded-full bg-slate-300 ring-4 ring-white shadow-2xs"></span>
                <span class="font-medium text-slate-500 text-[11px]">
                    {{ __('fleet.operations.timeline_start_of_day') }} — {{ $dayLog->date_carbon->translatedFormat('d F Y') }}
                </span>
            </div>
        </div>
    @else
        @if ($dayLog->has_any_day_activity)
            {{-- Filter resulted in empty set --}}
            <div class="rounded-2xl border border-dashed border-slate-200/90 bg-white p-8 text-center text-xs text-slate-400 space-y-2 shadow-2xs">
                <i class="bi bi-funnel text-2xl text-slate-300 block mb-1"></i>
                <h3 class="font-bold text-slate-700 text-sm">Tidak ada catatan untuk filter ini pada tanggal ini.</h3>
                <p class="text-slate-400 text-[11px]">Coba ubah filter kategori atau klik tombol di bawah untuk menampilkan semua aktivitas.</p>
                <button type="button" wire:click="setActivityFilter('all')" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition cursor-pointer">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <span>Tampilkan Semua ({{ $dayLog->total_trips + $dayLog->total_services }})</span>
                </button>
            </div>
        @else
            {{-- Quiet / No Activity on this Selected Day --}}
            <div class="rounded-2xl border border-dashed border-slate-200/90 bg-white p-8 sm:p-12 text-center text-xs text-slate-400 space-y-1.5 shadow-2xs">
                <i class="bi bi-calendar2-check text-3xl text-slate-300 block mb-2"></i>
                <h3 class="font-bold text-slate-700 text-sm">{{ __('fleet.operations.no_day_activity') }}</h3>
                <p class="text-slate-400 text-[11px]">{{ __('fleet.operations.all_in_pool_desc') }}</p>
            </div>
        @endif
    @endif
</div>
