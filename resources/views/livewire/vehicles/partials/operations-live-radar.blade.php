<div wire:poll.30s="$refresh" class="rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs space-y-4">
    {{-- Header Bar: Title, Active Counter, and Auto-Refresh Controls --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
        <div class="flex items-center gap-3">
            <span class="relative flex h-3 w-3">
                @if ($liveActiveTrips->isNotEmpty())
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                @else
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                @endif
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight">
                        {{ __('fleet.operations.live_radar_title') }}
                    </h2>
                    @if ($liveActiveTrips->isNotEmpty())
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 ring-1 ring-inset ring-amber-300">
                            {{ __('fleet.operations.active_trips_count', ['count' => $liveActiveTrips->count()]) }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 ring-1 ring-inset ring-emerald-300">
                            {{ __('fleet.operations.all_in_pool') }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    {{ __('fleet.operations.live_radar_subtitle') }}
                </p>
            </div>
        </div>

        {{-- Right Controls: Refresh Button & Pulse Timestamp --}}
        <div class="flex items-center gap-2.5 self-end sm:self-auto text-xs text-slate-500">
            <span class="hidden sm:inline-flex items-center gap-1.5 text-[11px] text-slate-400">
                <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                <span>{{ __('fleet.operations.auto_refresh') }} (30s)</span>
            </span>
            <span class="text-slate-300 hidden sm:inline">•</span>
            <span class="text-[11px] text-slate-500 font-mono">
                {{ __('fleet.operations.last_updated') }} {{ now()->format('H:i') }}
            </span>
            <button type="button" wire:click="$refresh"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition active:scale-95 cursor-pointer shadow-2xs">
                <i class="bi bi-arrow-clockwise text-xs"></i>
                <span class="hidden sm:inline">{{ __('fleet.operations.refresh') }}</span>
            </button>
        </div>
    </div>

    {{-- Content: Active Trip Cards or Standby State --}}
    @if ($liveActiveTrips->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
            @foreach ($liveActiveTrips as $trip)
                @php
                    $v = $trip->vehicle;
                    $depTime = $trip->checked_at ?? $trip->created_at;
                @endphp
                <div wire:key="live-trip-{{ $trip->id }}"
                    class="group relative flex flex-col justify-between rounded-2xl border-2 border-amber-300/80 bg-linear-to-b from-amber-50/50 to-white p-3.5 sm:p-4 shadow-xs hover:shadow-md transition">
                    
                    <div class="space-y-3">
                        {{-- Top: Vehicle Plate & Model & Category --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                @if ($v?->image_path)
                                    <button type="button" @click.stop="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $v->image_path) }}', title: '{{ __('fleet.show.lightbox_vehicle_photo', ['plate' => $v->plate_number]) }}', subtitle: '{{ trim($v->brand . ' ' . $v->model) }}' })"
                                        class="shrink-0 cursor-pointer">
                                        <img src="{{ asset('storage/' . $v->image_path) }}" alt="{{ $v->plate_number }}"
                                            class="h-9 w-9 rounded-xl object-cover border border-amber-200/80 shadow-2xs">
                                    </button>
                                @else
                                    <span class="h-9 w-9 rounded-xl flex items-center justify-center text-base bg-amber-100 text-amber-800 shrink-0">
                                        <i class="bi bi-car-front-fill"></i>
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ $v ? route('vehicles.show', $v) : '#' }}" wire:navigate
                                        class="font-mono text-sm font-bold text-slate-900 hover:text-indigo-600 transition truncate block">
                                        {{ $v?->plate_number ?: '—' }}
                                    </a>
                                    <div class="text-[11px] text-slate-500 truncate">
                                        {{ trim(($v?->brand ?? '') . ' ' . ($v?->model ?? '')) ?: __('fleet.common.vehicle') }}
                                    </div>
                                </div>
                            </div>

                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800 shrink-0">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                <span>{{ __('fleet.index.badge_on_trip') }}</span>
                            </span>
                        </div>

                        {{-- Elapsed Duration Badge --}}
                        <div class="rounded-xl bg-amber-100/70 border border-amber-200/60 px-3 py-2 text-xs flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 text-amber-950 font-semibold">
                                <i class="bi bi-stopwatch text-amber-700"></i>
                                <span>{{ __('fleet.operations.elapsed') }} {{ $trip->elapsed_text }}</span>
                            </div>
                            <span class="text-[11px] text-amber-800 font-mono font-medium">
                                {{ $depTime ? $depTime->format('H:i') . ' WIB' : '—' }}
                            </span>
                        </div>

                        {{-- Trip Details: Driver & Purpose --}}
                        <div class="space-y-1.5 text-xs">
                            <div class="flex items-center gap-2 text-slate-700">
                                <i class="bi bi-person-fill text-slate-400 text-xs shrink-0"></i>
                                <span class="font-medium truncate">{{ $trip->driver_name ?: '—' }}</span>
                            </div>

                            @if ($trip->trip_purpose)
                                <div class="flex items-start gap-2 text-slate-600">
                                    <i class="bi bi-geo-alt-fill text-slate-400 text-xs shrink-0 mt-0.5"></i>
                                    <span class="italic text-[11px] leading-snug line-clamp-2">"{{ $trip->trip_purpose }}"</span>
                                </div>
                            @endif

                            {{-- Initial Telemetry: Odometer & Fuel --}}
                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                                <span class="font-mono font-medium">
                                    <i class="bi bi-speedometer2 text-slate-400 mr-1"></i>
                                    {{ number_format($trip->odometer) }} km
                                </span>
                                <span class="font-semibold text-slate-700">
                                    <i class="bi bi-fuel-pump text-slate-400 mr-1"></i>
                                    {{ $trip->fuel_percentage }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Footer: Inspector Info & Cockpit Link --}}
                    <div class="mt-3.5 pt-2.5 border-t border-amber-200/50 flex items-center justify-between text-xs">
                        <span class="text-[11px] text-slate-400 font-medium truncate" title="{{ __('fleet.operations.inspector') }}: {{ $trip->created_by ?: ($trip->inspector?->name ?? ($trip->inspector_name ?? '—')) }}">
                            {{ $trip->created_by ?: ($trip->inspector?->name ?? ($trip->inspector_name ?? '—')) }}
                        </span>
                        @if ($v)
                            <a href="{{ route('vehicles.show', $v) }}" wire:navigate
                                class="shrink-0 inline-flex items-center gap-1 text-xs font-semibold text-amber-900 hover:text-amber-950 transition">
                                <span>{{ __('fleet.operations.view_vehicle') }}</span>
                                <i class="bi bi-arrow-right text-[11px]"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Standby Pool Banner --}}
        <div class="rounded-2xl bg-emerald-50/60 border border-emerald-200/60 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-emerald-950">
            <div class="flex items-center gap-3">
                <span class="h-10 w-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-700 text-lg shrink-0">
                    <i class="bi bi-check-circle-fill"></i>
                </span>
                <div>
                    <h3 class="text-xs sm:text-sm font-bold text-emerald-950">
                        {{ __('fleet.operations.all_in_pool') }}
                    </h3>
                    <p class="text-[11px] sm:text-xs text-emerald-800/80 mt-0.5">
                        {{ __('fleet.operations.all_in_pool_desc') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-emerald-200/80 text-xs font-semibold text-emerald-900 shadow-2xs">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>{{ __('fleet.operations.ready_units_count', ['count' => $metrics['in_pool']]) }}</span>
                </span>
            </div>
        </div>
    @endif
</div>
