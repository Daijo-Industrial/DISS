<div class="space-y-4">
    {{-- Single-Day Navigation Bar & Period Summary --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-3.5 sm:p-4 shadow-xs space-y-3">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            {{-- Left: Day Switcher Controls (Separated Presets and Left / Custom Date / Right Stepper) --}}
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs font-semibold">
                {{-- Quick Presets: Hari Ini & Kemarin --}}
                <div class="inline-flex items-center gap-1 p-0.5 rounded-xl bg-slate-100 border border-slate-200/80 shadow-2xs">
                    <button type="button" wire:click="setDatePreset('today')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer active:scale-95 {{ $this->isToday ? 'bg-indigo-600 text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60' }}">
                        {{ __('fleet.operations.filter_today') }}
                    </button>
                    <button type="button" wire:click="setDatePreset('yesterday')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer active:scale-95 {{ $this->isYesterday ? 'bg-indigo-600 text-white shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60' }}">
                        {{ __('fleet.operations.filter_yesterday') }}
                    </button>
                </div>

                {{-- Subtle Divider --}}
                <div class="hidden sm:block h-5 w-px bg-slate-200"></div>

                {{-- Stepper Group: [ < ] [ Custom Date Picker ] [ > ] --}}
                <div class="inline-flex items-center rounded-xl border border-slate-200/80 bg-white p-0.5 shadow-2xs">
                    {{-- Previous Day Arrow (Left) --}}
                    <button type="button" wire:click="previousDay" title="{{ __('fleet.operations.previous_day') }}"
                        class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-700 hover:text-slate-900 transition active:scale-95 cursor-pointer">
                        <i class="bi bi-chevron-left text-xs"></i>
                    </button>

                    {{-- Custom Date Picker --}}
                    <div class="relative inline-flex items-center px-1">
                        <input type="date" wire:model.live="selectedDate" max="{{ now()->toDateString() }}"
                            class="rounded-lg border-0 bg-slate-50 py-1 px-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 focus:bg-white focus:ring-1 focus:ring-indigo-500 focus:outline-none transition cursor-pointer font-mono">
                    </div>

                    {{-- Next Day Arrow (Right, Disabled if today or future) --}}
                    <button type="button" wire:click="nextDay" title="{{ __('fleet.operations.next_day') }}"
                        @disabled(! $this->canGoNextDay)
                        class="p-1.5 rounded-lg transition {{ ! $this->canGoNextDay ? 'opacity-30 text-slate-400 cursor-not-allowed' : 'hover:bg-slate-100 text-slate-700 hover:text-slate-900 cursor-pointer active:scale-95' }}">
                        <i class="bi bi-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>

            {{-- Right: Date Label & Day Counter Badges --}}
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs text-slate-500">
                <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-slate-100 text-slate-800 font-bold">
                    {{ __('fleet.operations.completed_trips_count', ['count' => $dayLog->total_trips]) }}
                </span>
                @if ($dayLog->total_distance > 0)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-slate-100 text-slate-800 font-bold font-mono">
                        +{{ number_format($dayLog->total_distance) }} km
                    </span>
                @endif
                @if ($dayLog->total_defects > 0)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-rose-100 text-rose-800 font-bold">
                        <i class="bi bi-exclamation-circle-fill text-[11px]"></i>
                        {{ $dayLog->total_defects }} {{ __('fleet.operations.defects') }}
                    </span>
                @endif
                @if ($dayLog->total_services > 0)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-purple-100 text-purple-800 font-bold">
                        <i class="bi bi-wrench text-[11px]"></i>
                        {{ $dayLog->total_services }} {{ __('fleet.operations.services') }}
                    </span>
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

    {{-- Day Content: Completed Trips & Workshop Services (Progressive Disclosure) --}}
    @if ($dayLog->has_activity)
        <div class="space-y-3">
            {{-- 1. Completed Trips on this Selected Day --}}
            @foreach ($dayLog->completed_trips as $trip)
                @php
                    $v = $trip->vehicle;
                @endphp
                <div wire:key="completed-trip-{{ $trip->id }}"
                    x-data="{ expanded: {{ $trip->has_defect ? 'true' : 'false' }} }"
                    class="rounded-2xl border-l-4 {{ $trip->has_defect ? 'border-l-rose-500 bg-rose-50/15 border-rose-200/70' : 'border-l-emerald-500 bg-white border-slate-200/80' }} border shadow-xs transition p-3.5 sm:p-4 space-y-3">
                    
                    {{-- Slim Glanceable Header Row --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                        <div class="flex items-center gap-3 min-w-0">
                            @if ($v?->image_path)
                                <button type="button" @click.stop="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $v->image_path) }}', title: '{{ __('fleet.show.lightbox_vehicle_photo', ['plate' => $v->plate_number]) }}', subtitle: '{{ trim($v->brand . ' ' . $v->model) }}' })"
                                    class="shrink-0 cursor-pointer">
                                    <img src="{{ asset('storage/' . $v->image_path) }}" alt="{{ $v->plate_number }}"
                                        class="h-8 w-8 rounded-xl object-cover border border-slate-200/80 shadow-2xs">
                                </button>
                            @else
                                <span class="h-8 w-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xs shrink-0">
                                    <i class="bi bi-car-front-fill"></i>
                                </span>
                            @endif

                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <a href="{{ $v ? route('vehicles.show', $v) : '#' }}" wire:navigate
                                        class="font-mono text-xs sm:text-sm font-bold text-slate-900 hover:text-indigo-600 transition truncate">
                                        {{ $v?->plate_number ?: '—' }}
                                    </a>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-slate-600 text-xs truncate">{{ trim(($v?->brand ?? '') . ' ' . ($v?->model ?? '')) }}</span>
                                </div>
                                <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                                    <span class="font-medium truncate"><i class="bi bi-person mr-1 text-slate-400"></i>{{ $trip->driver_name }}</span>
                                    @if ($trip->trip_purpose)
                                        <span class="text-slate-300">•</span>
                                        <span class="italic truncate text-slate-400 max-w-xs">"{{ $trip->trip_purpose }}"</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Right: Time, Distance, Health Badge & Expand Toggle --}}
                        <div class="flex items-center gap-2.5 self-end sm:self-auto shrink-0 text-xs">
                            <div class="text-right hidden sm:block">
                                <div class="font-medium text-slate-900 font-mono text-[11px]">
                                    {{ $trip->dep_time ? $trip->dep_time->format('H:i') : '—' }} → {{ $trip->ret_time ? $trip->ret_time->format('H:i') : '—' }}
                                    <span class="text-slate-400">({{ $trip->duration_text }})</span>
                                </div>
                                <div class="text-[11px] font-bold text-slate-700 font-mono">
                                    +{{ number_format($trip->distance) }} km
                                </div>
                            </div>

                            {{-- Health Status Pill --}}
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

                            {{-- Progressive Disclosure Expand Toggle --}}
                            <button type="button" @click="expanded = !expanded"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition active:scale-95 cursor-pointer shadow-2xs">
                                <span x-text="expanded ? '{{ __('fleet.operations.hide_telemetry') }}' : '{{ __('fleet.operations.view_telemetry') }}'"></span>
                                <i class="bi bi-chevron-down text-[9px] transition-transform duration-200" :class="expanded ? 'rotate-180' : ''"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Collapsible Drawer: Full Telemetry & Inspection Details --}}
                    <div x-show="expanded" x-transition class="pt-3 border-t border-slate-100 space-y-2.5 text-xs">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 bg-slate-50/80 rounded-xl p-3 border border-slate-200/60">
                            {{-- Duration --}}
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ __('fleet.operations.duration') }}</span>
                                <div class="font-medium text-slate-900 font-mono">
                                    {{ $trip->dep_time ? $trip->dep_time->format('H:i') : '—' }} → {{ $trip->ret_time ? $trip->ret_time->format('H:i') : '—' }}
                                </div>
                                <div class="text-[10px] text-slate-500 font-medium">
                                    {{ $trip->duration_text ?: '—' }}
                                </div>
                            </div>

                            {{-- Distance & Odometers --}}
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ __('fleet.operations.distance') }}</span>
                                <div class="font-bold text-slate-900 font-mono">
                                    +{{ number_format($trip->distance) }} km
                                </div>
                                <div class="text-[10px] text-slate-500 font-mono">
                                    {{ number_format($trip->dep_odometer) }} → {{ number_format($trip->ret_odometer) }} km
                                </div>
                            </div>

                            {{-- Fuel Delta --}}
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ __('fleet.operations.fuel') }}</span>
                                <div class="font-medium text-slate-900 font-mono">
                                    {{ $trip->dep_fuel }}% → {{ $trip->ret_fuel }}%
                                </div>
                                @php
                                    $fuelDiff = (int) $trip->ret_fuel - (int) $trip->dep_fuel;
                                @endphp
                                <div class="text-[10px] font-medium {{ $fuelDiff < 0 ? 'text-slate-500' : 'text-emerald-600' }}">
                                    {{ $fuelDiff > 0 ? '+' . $fuelDiff : $fuelDiff }}%
                                </div>
                            </div>

                            {{-- Inspector & Verification --}}
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ __('fleet.operations.inspector') }}</span>
                                <div class="font-medium text-slate-900 truncate">
                                    {{ $trip->inspector_name }}
                                </div>
                                @if ($v)
                                    <a href="{{ route('vehicles.show', ['vehicle' => $v->id, 'tab' => 'inspections']) }}" wire:navigate
                                        class="text-indigo-600 hover:text-indigo-800 text-[10px] font-semibold transition inline-flex items-center gap-1 mt-0.5">
                                        <span>{{ __('fleet.operations.view_details') }}</span>
                                        <i class="bi bi-arrow-right text-[9px]"></i>
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Defect Finding Callout --}}
                        @if ($trip->has_defect)
                            <div class="rounded-xl bg-rose-50/80 border border-rose-200/80 p-3 space-y-1.5 text-rose-950">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5 font-bold text-rose-900">
                                        <i class="bi bi-exclamation-triangle-fill text-rose-600"></i>
                                        <span>{{ __('fleet.operations.defect_findings') }}</span>
                                    </div>
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $trip->defect_severity === 'critical_grounded' ? 'bg-rose-200 text-rose-900' : 'bg-amber-100 text-amber-900' }}">
                                        {{ $trip->defect_severity === 'critical_grounded' ? __('fleet.inspection.severity_critical') : __('fleet.inspection.severity_minor') }}
                                    </span>
                                </div>

                                @if ($trip->defect_notes)
                                    <p class="text-rose-900/90 text-xs pl-5 font-normal">
                                        {{ $trip->defect_notes }}
                                    </p>
                                @endif

                                @if (!empty($trip->defect_photos) && is_array($trip->defect_photos))
                                    <div class="flex items-center gap-2 pl-5 pt-1">
                                        @foreach ($trip->defect_photos as $photo)
                                            <button type="button" @click.stop="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $photo) }}', title: '{{ __('fleet.operations.defect_photo_title', ['plate' => $v?->plate_number]) }}', subtitle: '{{ $trip->defect_notes }}' })"
                                                class="group/photo relative rounded-lg overflow-hidden border border-rose-200 shadow-2xs hover:scale-105 transition cursor-pointer">
                                                <img src="{{ asset('storage/' . $photo) }}" alt="{{ __('fleet.operations.defects') }}" class="h-10 w-10 object-cover">
                                                <div class="absolute inset-0 bg-black/20 opacity-0 group-hover/photo:opacity-100 flex items-center justify-center transition">
                                                    <i class="bi bi-zoom-in text-white text-xs"></i>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- 2. Workshop Services on this Selected Day --}}
            @foreach ($dayLog->services as $service)
                @php
                    $sv = $service->vehicle;
                @endphp
                <div wire:key="service-card-{{ $service->id }}"
                    class="rounded-2xl border-l-4 border-l-purple-500 bg-purple-50/20 border border-purple-200/60 p-3.5 sm:p-4 text-xs space-y-2.5 shadow-xs">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="h-7 w-7 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center text-xs shrink-0">
                                <i class="bi bi-wrench"></i>
                            </span>
                            <div class="flex items-center gap-2 truncate">
                                <a href="{{ $sv ? route('vehicles.show', ['vehicle' => $sv->id, 'tab' => 'services']) : '#' }}" wire:navigate
                                    class="font-mono font-bold text-slate-900 hover:text-purple-700 transition">
                                    {{ $sv?->plate_number ?: '—' }}
                                </a>
                                <span class="text-slate-300">•</span>
                                <span class="text-slate-600 truncate">{{ trim(($sv?->brand ?? '') . ' ' . ($sv?->model ?? '')) }}</span>
                            </div>
                        </div>

                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 text-[10px] font-bold">
                            <i class="bi bi-tools text-[9px]"></i>
                            <span>{{ __('fleet.operations.service_workshop') }}</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-slate-700 bg-white/70 rounded-xl p-2.5 border border-purple-200/50">
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ __('fleet.operations.workshop') }}</span>
                            <span class="font-semibold text-slate-900">{{ $service->workshop }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ __('fleet.operations.service_notes') }}</span>
                            <span class="text-slate-700 truncate block">{{ $service->notes ?: __('fleet.operations.routine_service') }}</span>
                        </div>
                        <div>
                            @if ($canViewCosts && $service->total_cost)
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ __('fleet.operations.service_cost') }}</span>
                                <span class="font-bold text-slate-900 font-mono">Rp {{ number_format($service->total_cost, 0, ',', '.') }}</span>
                            @else
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">{{ __('fleet.operations.service_odometer') }}</span>
                                <span class="font-mono font-medium">{{ number_format($service->odometer) }} km</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Quiet / No Activity on this Selected Day --}}
        <div class="rounded-2xl border border-dashed border-slate-200/90 bg-white p-8 sm:p-12 text-center text-xs text-slate-400 space-y-1.5 shadow-2xs">
            <i class="bi bi-calendar2-check text-3xl text-slate-300 block mb-2"></i>
            <h3 class="font-bold text-slate-700 text-sm">{{ __('fleet.operations.no_day_activity') }}</h3>
            <p class="text-slate-400 text-[11px]">{{ __('fleet.operations.all_in_pool_desc') }}</p>
        </div>
    @endif
</div>
