{{-- ========================================================================= --}}
{{-- TAB 1: LOGBOOK PEMERIKSAAN HARIAN (P2H) - PAIRED JOURNEY LOGBOOK         --}}
{{-- ========================================================================= --}}
<div class="space-y-4 min-w-0">

    {{-- ACTIVE TRIP BANNER (If currently out on trip) --}}
    @if ($activeTrip)
        <div class="rounded-2xl border border-amber-200/80 bg-gradient-to-r from-amber-50/90 via-amber-50/50 to-white p-4 sm:p-5 shadow-2xs space-y-3 min-w-0">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 min-w-0">
                <div class="flex items-start gap-3 min-w-0">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-2xs">
                        <i class="bi bi-geo-alt-fill text-base animate-pulse"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-xs sm:text-sm font-bold text-amber-950">
                                {{ __('fleet.show.active_journey_title') }}
                            </h3>
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                {{ $activeTrip->elapsed_text }}
                            </span>
                        </div>
                        <p class="text-xs text-amber-800/90 mt-0.5 break-words">
                            {{ __('fleet.show.active_journey_desc', ['time' => ($activeTrip->checked_at ?? $activeTrip->created_at)->isoFormat('HH:mm') . ' WIB']) }}
                            • Driver: <strong>{{ $activeTrip->driver_name }}</strong>
                            • Odo Awal: <strong>{{ number_format($activeTrip->odometer) }} km</strong>
                            @if ($activeTrip->trip_purpose)
                                • <span class="italic">"{{ $activeTrip->trip_purpose }}"</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if ($canInspect && !$vehicle->is_sold)
                    <a href="{{ route('vehicles.inspect', ['vehicle' => $vehicle, 'type' => 'check_in']) }}"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition active:scale-95 shrink-0">
                        <i class="bi bi-box-arrow-in-down text-sm"></i>
                        <span>{{ __('fleet.show.btn_checkin') }} →</span>
                    </a>
                @endif
            </div>
        </div>
    @endif

    {{-- MAIN CARD: TITLE, FILTERS, & JOURNEYS TABLE --}}
    <div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs space-y-4 min-w-0">
        {{-- Minimal Filter Toolbar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3.5 min-w-0">
            <div>
                <h2 class="text-sm font-bold text-slate-900">{{ __('fleet.show.inspections_title') }}</h2>
                <p class="text-xs text-slate-400">{{ __('fleet.show.inspections_desc') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Live Search Input --}}
                <div class="relative min-w-[200px] sm:min-w-[240px] flex-1 sm:flex-none">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                        <i class="bi bi-search text-xs"></i>
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="p2hSearch"
                        placeholder="{{ __('fleet.show.search_p2h_placeholder') }}"
                        class="w-full pl-8 pr-7 py-1.5 rounded-xl border border-slate-200 bg-slate-50/70 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition shadow-2xs">
                    @if ($p2hSearch !== '')
                        <button type="button" wire:click="$set('p2hSearch', '')"
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                            <i class="bi bi-x-circle-fill text-xs"></i>
                        </button>
                    @endif
                </div>

                {{-- Defect Toggle Pill Group --}}
                <div class="inline-flex items-center p-0.5 rounded-xl bg-slate-100 border border-slate-200/70 shadow-2xs text-xs">
                    <button type="button" wire:click="$set('p2hDefect', 'all')"
                        class="px-2.5 py-1 rounded-lg font-semibold transition cursor-pointer {{ $p2hDefect === 'all' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800' }}">
                        {{ __('fleet.show.filter_all_trips') }}
                    </button>
                    <button type="button" wire:click="$set('p2hDefect', 'defects_only')"
                        class="px-2.5 py-1 rounded-lg font-semibold transition cursor-pointer inline-flex items-center gap-1 {{ $p2hDefect === 'defects_only' ? 'bg-rose-500 text-white shadow-2xs' : 'text-slate-500 hover:text-rose-600' }}">
                        <span>{{ __('fleet.show.filter_defects_only') }}</span>
                        @if ($p2hDefect !== 'defects_only')
                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                        @endif
                    </button>
                </div>
            </div>
        </div>

        @if ($inspections->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-xs text-slate-400">
                <i class="bi bi-clipboard-x text-2xl text-slate-300 block mb-1"></i>
                {{ __('fleet.show.empty_inspections') }}
            </div>
        @else
            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-2xs overflow-hidden min-w-0">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-2.5 px-3">{{ __('fleet.show.th_date_time') }}</th>
                            <th class="py-2.5 px-2.5">{{ __('fleet.show.th_driver_purpose') }}</th>
                            <th class="py-2.5 px-2.5 text-right">{{ __('fleet.show.th_distance_odo') }}</th>
                            <th class="py-2.5 px-2 text-center">{{ __('fleet.show.th_fuel') }}</th>
                            <th class="py-2.5 px-2 text-center">{{ __('fleet.show.th_status') }}</th>
                            <th class="py-2.5 px-1.5 text-center w-8"></th>
                        </tr>
                    </thead>
                    @foreach ($inspections as $ins)
                        @php
                            $parent = $ins->parentCheckOut;
                            $isCheckIn = $ins->inspection_type === 'check_in';
                            $isPaired = $isCheckIn && $parent;
                            $isDirectCheckIn = $isCheckIn && !$parent;
                            $isUnclosedCheckOut = !$isCheckIn;

                            $depTime = ($isPaired || $isUnclosedCheckOut) ? ($parent ? ($parent->checked_at ?? $parent->created_at) : ($ins->checked_at ?? $ins->created_at)) : null;
                            $retTime = $isCheckIn ? ($ins->checked_at ?? $ins->created_at) : null;

                            // Duration calculation
                            $durationText = null;
                            if ($depTime && $retTime) {
                                $diffMin = (int) $depTime->diffInMinutes($retTime);
                                if ($diffMin < 60) {
                                    $durationText = $diffMin . 'm';
                                } else {
                                    $hrs = floor($diffMin / 60);
                                    $m = $diffMin % 60;
                                    $durationText = $hrs . 'j' . ($m > 0 ? ' ' . $m . 'm' : '');
                                }
                            }

                            // Telemetry
                            $driver = $ins->driver_name ?: ($parent?->driver_name ?: '-');
                            $purpose = $ins->trip_purpose ?: ($parent?->trip_purpose ?: '');
                            $depOdo = $isPaired ? $parent->odometer : ($isUnclosedCheckOut ? $ins->odometer : null);
                            $retOdo = $isCheckIn ? $ins->odometer : null;
                            $tripDist = $isPaired ? $ins->trip_distance : null;

                            $depFuel = $isPaired ? $parent->fuel_percentage : ($isUnclosedCheckOut ? $ins->fuel_percentage : null);
                            $retFuel = $isCheckIn ? $ins->fuel_percentage : null;

                            // Defect evaluation
                            $hasDefect = in_array($ins->severity, ['minor', 'critical_grounded'])
                                || ($parent && in_array($parent->severity, ['minor', 'critical_grounded']))
                                || !empty($ins->defect_notes)
                                || ($parent && !empty($parent->defect_notes));
                            $isCritical = $ins->severity === 'critical_grounded'
                                || ($parent && $parent->severity === 'critical_grounded');

                            // Checklist & photos aggregation
                            $chkResults = is_array($ins->checklist_results) ? $ins->checklist_results : (json_decode($ins->checklist_results, true) ?: []);
                            $parentChkResults = $parent ? (is_array($parent->checklist_results) ? $parent->checklist_results : (json_decode($parent->checklist_results, true) ?: [])) : [];

                            $allPhotos = [];
                            foreach ($chkResults as $k => $item) {
                                if (!empty($item['photos'])) {
                                    foreach ($item['photos'] as $p) {
                                        $allPhotos[] = ['path' => $p, 'label' => ($item['label'] ?? ($item['title'] ?? ucfirst($k))) . ($isCheckIn ? ' (Kembali)' : ' (Berangkat)')];
                                    }
                                }
                            }
                            if (!empty($ins->defect_photos)) {
                                foreach ($ins->defect_photos as $dp) {
                                    $allPhotos[] = ['path' => $dp, 'label' => 'Defek Utama' . ($isCheckIn ? ' (Kembali)' : ' (Berangkat)')];
                                }
                            }
                            foreach ($parentChkResults as $pk => $pitem) {
                                if (!empty($pitem['photos'])) {
                                    foreach ($pitem['photos'] as $pp) {
                                        $allPhotos[] = ['path' => $pp, 'label' => ($pitem['label'] ?? ($pitem['title'] ?? ucfirst($pk))) . ' (Berangkat)'];
                                    }
                                }
                            }
                            if ($parent && !empty($parent->defect_photos)) {
                                foreach ($parent->defect_photos as $pdp) {
                                    $allPhotos[] = ['path' => $pdp, 'label' => 'Defek Utama (Berangkat)'];
                                }
                            }

                            $defectNotesText = $ins->defect_notes ?: ($parent?->defect_notes ?: '');
                        @endphp

                        <tbody x-data="{ open: false }" class="border-b border-slate-100 last:border-b-0">
                            {{-- Glanceable Single-Line Journey Row --}}
                            <tr @click="open = !open"
                                class="hover:bg-slate-50/80 transition cursor-pointer select-none {{ $hasDefect ? ($isCritical ? 'bg-rose-50/20' : 'bg-amber-50/20') : '' }}">
                                {{-- 1. Date, Time & Duration --}}
                                <td class="py-2.5 px-3 min-w-[130px]">
                                    <div class="font-bold text-slate-900 leading-tight">
                                        {{ ($retTime ?? $depTime)->isoFormat('D MMM YYYY') }}
                                    </div>
                                    <div class="flex items-center gap-1 mt-0.5 text-[11px] text-slate-500 font-mono">
                                        @if ($isPaired)
                                            <span>{{ $depTime->format('H:i') }}–{{ $retTime->format('H:i') }}</span>
                                            @if ($durationText)
                                                <span class="rounded bg-slate-100 border border-slate-200/60 px-1 py-0.2 text-[9px] font-sans font-bold text-slate-700">
                                                    {{ $durationText }}
                                                </span>
                                            @endif
                                        @elseif ($isDirectCheckIn)
                                            <span>{{ $retTime->format('H:i') }}</span>
                                            <span class="rounded bg-indigo-50 border border-indigo-200/60 px-1 py-0.2 text-[9px] font-sans font-bold text-indigo-700 truncate" title="{{ __('fleet.show.direct_checkin') }}">
                                                Check-in
                                            </span>
                                        @else
                                            <span>{{ $depTime->format('H:i') }}</span>
                                            <span class="rounded bg-amber-50 border border-amber-200/60 px-1 py-0.2 text-[9px] font-sans font-bold text-amber-700 truncate" title="{{ __('fleet.show.unclosed_trip') }}">
                                                Check-out
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- 2. Driver & Purpose --}}
                                <td class="py-2.5 px-2.5 min-w-0">
                                    <span class="font-bold text-slate-900 block truncate max-w-[120px] sm:max-w-[160px]" title="{{ $driver }}">
                                        {{ $driver }}
                                    </span>
                                    @if ($purpose)
                                        <span class="text-[11px] text-slate-400 italic block truncate max-w-[120px] sm:max-w-[160px]" title="{{ $purpose }}">
                                            "{{ $purpose }}"
                                        </span>
                                    @endif
                                </td>

                                {{-- 3. Distance & Odometer --}}
                                <td class="py-2.5 px-2.5 text-right whitespace-nowrap">
                                    @if ($tripDist !== null)
                                        <span class="font-mono text-xs font-black text-slate-900 block">
                                            +{{ number_format($tripDist) }} km
                                        </span>
                                        <span class="font-mono text-[10px] text-slate-400 block">
                                            {{ number_format($depOdo) }} → {{ number_format($retOdo) }}
                                        </span>
                                    @elseif ($isDirectCheckIn)
                                        <span class="font-mono text-xs font-bold text-slate-800 block">
                                            {{ number_format($retOdo) }} km
                                        </span>
                                        <span class="text-[10px] text-slate-400 block font-sans">
                                            ({{ __('fleet.show.telemetry_return') }})
                                        </span>
                                    @else
                                        <span class="font-mono text-xs font-bold text-slate-800 block">
                                            {{ number_format($depOdo) }} km
                                        </span>
                                        <span class="text-[10px] text-slate-400 block font-sans">
                                            ({{ __('fleet.show.telemetry_departure') }})
                                        </span>
                                    @endif
                                </td>

                                {{-- 4. Fuel Delta --}}
                                <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                    @if ($retFuel !== null && $depFuel !== null)
                                        <span class="font-mono text-xs font-semibold text-slate-800 block">
                                            {{ $depFuel }}% → {{ $retFuel }}%
                                        </span>
                                        @if ($retFuel < $depFuel)
                                            <span class="text-[10px] font-mono text-rose-500 font-semibold block">
                                                (-{{ $depFuel - $retFuel }}%)
                                            </span>
                                        @endif
                                    @else
                                        <span class="font-mono text-xs text-slate-600 block">
                                            {{ $retFuel ?? $depFuel ?? '-' }}%
                                        </span>
                                    @endif
                                </td>

                                {{-- 5. Status Pill --}}
                                <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                    @if ($isCritical)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 border border-rose-200/80 px-2 py-0.5 text-[10px] font-bold text-rose-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                            {{ __('fleet.inspection.severity_critical') }}
                                        </span>
                                    @elseif ($hasDefect)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200/80 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            {{ __('fleet.inspection.severity_minor') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            {{ __('fleet.inspection.severity_none') }}
                                        </span>
                                    @endif
                                </td>

                                {{-- 6. Actions / Chevron --}}
                                <td class="py-2.5 px-1.5 text-center whitespace-nowrap w-8">
                                    <div class="inline-flex items-center gap-1">
                                        @if (count($allPhotos) > 0)
                                            <span class="rounded-full bg-slate-100 text-slate-600 px-1.5 py-0.2 text-[9px] font-bold flex items-center gap-0.5" title="{{ count($allPhotos) }} Foto Tersedia">
                                                <i class="bi bi-camera"></i>
                                                {{ count($allPhotos) }}
                                            </span>
                                        @endif
                                        <button type="button"
                                            class="h-6 w-6 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition cursor-pointer">
                                            <i class="bi bi-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            {{-- Expandable Journey Drawer --}}
                            <tr x-show="open" x-cloak class="bg-slate-50/60">
                                <td colspan="6" class="p-3 sm:p-4 border-t border-slate-100 space-y-3">
                                    {{-- Row 1: Dual Telemetry (Departure & Return Details) --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                        {{-- Departure Telemetry Card --}}
                                        <div class="rounded-xl border border-slate-200/70 bg-white p-2.5 sm:p-3 space-y-1">
                                            <div class="flex items-center justify-between text-slate-400 text-[10px] font-bold uppercase tracking-wider">
                                                <span>{{ __('fleet.show.departure_telemetry') }}</span>
                                                <i class="bi bi-box-arrow-up-right text-slate-500"></i>
                                            </div>
                                            @if ($depTime)
                                                <div class="font-bold text-slate-900">
                                                    {{ $depTime->isoFormat('D MMM YYYY, HH:mm') }} WIB
                                                </div>
                                                <div class="text-[11px] text-slate-600 flex flex-wrap gap-x-3 gap-y-0.5">
                                                    <span>Petugas: <strong>{{ ($parent ? ($parent->created_by ?: ($parent->inspector?->name ?? '—')) : ($ins->created_by ?: ($ins->inspector?->name ?? '—'))) }}</strong></span>
                                                    <span>Odo: <strong>{{ number_format($depOdo) }} km</strong></span>
                                                    <span>BBM: <strong>{{ $depFuel ?? '-' }}%</strong></span>
                                                </div>
                                            @else
                                                <div class="text-xs text-slate-400 italic py-2 flex items-center gap-1.5">
                                                    <i class="bi bi-dash-circle text-slate-300"></i>
                                                    <span>{{ __('fleet.show.no_departure_data') }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Return Telemetry Card --}}
                                        <div class="rounded-xl border border-slate-200/70 bg-white p-2.5 sm:p-3 space-y-1">
                                            <div class="flex items-center justify-between text-slate-400 text-[10px] font-bold uppercase tracking-wider">
                                                <span>{{ __('fleet.show.return_telemetry') }}</span>
                                                <i class="bi bi-box-arrow-in-down text-slate-500"></i>
                                            </div>
                                            @if ($retTime)
                                                <div class="font-bold text-slate-900">
                                                    {{ $retTime->isoFormat('D MMM YYYY, HH:mm') }} WIB
                                                </div>
                                                <div class="text-[11px] text-slate-600 flex flex-wrap gap-x-3 gap-y-0.5">
                                                    <span>Petugas: <strong>{{ $ins->created_by ?: ($ins->inspector?->name ?? '—') }}</strong></span>
                                                    <span>Odo: <strong>{{ number_format($retOdo) }} km</strong></span>
                                                    <span>BBM: <strong>{{ $retFuel ?? '-' }}%</strong></span>
                                                </div>
                                            @else
                                                <div class="text-xs text-amber-700 font-semibold py-2 flex items-center gap-1.5">
                                                    <i class="bi bi-hourglass-split text-amber-500"></i>
                                                    <span>{{ __('fleet.show.unclosed_trip') }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Defect Finding & Escalate to Workshop Service Banner --}}
                                    @if ($hasDefect)
                                        <div class="rounded-xl border {{ $isCritical ? 'border-rose-200 bg-rose-50/70 text-rose-950' : 'border-amber-200 bg-amber-50/70 text-amber-950' }} p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                            <div class="flex items-start gap-2 min-w-0">
                                                <i class="bi bi-exclamation-triangle-fill text-sm {{ $isCritical ? 'text-rose-600' : 'text-amber-600' }} shrink-0 mt-0.5"></i>
                                                <div class="text-xs min-w-0">
                                                    <span class="font-bold block">{{ __('fleet.show.finding_label') }}</span>
                                                    <p class="text-xs break-words {{ $isCritical ? 'text-rose-900' : 'text-amber-900' }}">
                                                        {{ $defectNotesText ?: 'Ditemukan catatan ketidaksesuaian pada item checklist di bawah.' }}
                                                    </p>
                                                </div>
                                            </div>

                                            @if ($canManage)
                                                <a href="{{ route('services.create', ['vehicle' => $vehicle, 'odometer' => $ins->odometer, 'notes' => 'Temuan P2H: ' . $defectNotesText]) }}"
                                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white px-3 py-1.5 text-xs font-bold shadow-2xs transition shrink-0 self-end sm:self-auto">
                                                    <i class="bi bi-wrench text-xs"></i>
                                                    <span>{{ __('fleet.show.escalate_service_btn') }} →</span>
                                                </a>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- 7-Point Checklist Grid --}}
                                    @if (!empty($chkResults))
                                        <div class="space-y-1.5">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                Hasil Pemeriksaan 7 Titik ({{ $isCheckIn ? 'Check-in' : 'Check-out' }})
                                            </span>
                                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                                @foreach ($chkResults as $itemKey => $item)
                                                    @php
                                                        $statusOk = ($item['status'] ?? 'ok') === 'ok' || ($item['status'] ?? '') === 'good';
                                                        $itemLabel = $item['label'] ?? ($item['title'] ?? ucfirst($itemKey));
                                                    @endphp
                                                    <div class="rounded-xl border p-2 text-xs min-w-0 {{ $statusOk ? 'border-slate-200/60 bg-white' : 'border-rose-200 bg-rose-50/50' }}">
                                                        <div class="flex items-center justify-between gap-1 min-w-0">
                                                            <span class="font-bold min-w-0 break-words truncate {{ $statusOk ? 'text-slate-800' : 'text-rose-900' }}" title="{{ $itemLabel }}">
                                                                {{ $itemLabel }}
                                                            </span>
                                                            <span class="rounded-md px-1.5 py-0.2 text-[9px] font-bold shrink-0 {{ $statusOk ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                                                {{ $statusOk ? __('fleet.show.checklist_ok') : __('fleet.show.checklist_issue') }}
                                                            </span>
                                                        </div>

                                                        @if (!empty($item['notes']))
                                                            <p class="mt-1 text-[10px] text-slate-600 line-clamp-2" title="{{ $item['notes'] }}">
                                                                <span class="font-semibold text-slate-400">{{ __('fleet.show.checklist_remark') }}</span> {{ $item['notes'] }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Photo Evidence Thumbnails Gallery --}}
                                    @if (count($allPhotos) > 0)
                                        <div class="space-y-1.5 pt-1 border-t border-slate-200/60">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                Foto Bukti Pemeriksaan ({{ count($allPhotos) }})
                                            </span>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($allPhotos as $photoItem)
                                                    <button type="button"
                                                        @click.prevent="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $photoItem['path']) }}', title: '{{ addslashes($photoItem['label']) }}', subtitle: '{{ $vehicle->plate_number }} • {{ ($ins->checked_at ?? $ins->created_at)->format('d M Y H:i') }}' })"
                                                        class="group relative block cursor-pointer rounded-xl overflow-hidden border border-slate-200/80 shadow-2xs hover:ring-2 hover:ring-indigo-500 transition"
                                                        title="Perbesar: {{ $photoItem['label'] }}">
                                                        <img src="{{ asset('storage/' . $photoItem['path']) }}" alt="{{ $photoItem['label'] }}"
                                                            class="h-14 w-14 sm:h-16 sm:w-16 object-cover group-hover:scale-105 transition duration-200">
                                                        <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs">
                                                            <i class="bi bi-zoom-in"></i>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>

            <div class="pt-2">{{ $inspections->links() }}</div>
        @endif
    </div>
</div>
