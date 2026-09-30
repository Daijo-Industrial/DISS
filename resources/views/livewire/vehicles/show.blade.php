<div class="max-w-6xl mx-auto px-3 sm:px-6 py-5 space-y-6">
    {{-- Top Navigation & Quick Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <a href="{{ route('vehicles.index') }}"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
            <i class="bi bi-arrow-left text-sm"></i>
            {{ __('fleet.show.back_to_index') }}
        </a>

        <div class="flex items-center gap-2 self-end sm:self-auto">
            @if ($vehicle->vin)
                <span class="font-mono text-[11px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md">VIN: {{ $vehicle->vin }}</span>
            @endif
            <a href="{{ route('vehicles.edit', $vehicle) }}"
                class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                <i class="bi bi-pencil"></i>
                <span>{{ __('fleet.show.btn_edit_vehicle') }}</span>
            </a>
        </div>
    </div>

    @php
        $last = $vehicle->latestService;
        $lastKm = (int) ($last->odometer ?? 0);
        $kmSinceLastService = max(0, $vehicle->odometer - $lastKm);
        $nextServiceInterval = 10000;
        $serviceProgressPercent = min(100, round(($kmSinceLastService / $nextServiceInterval) * 100));
        $isOut = $vehicle->is_out_on_trip;
        $hasExpired = $documents->where('status', 'expired')->count();
        $hasWarning = $documents->whereIn('status', ['critical', 'warning'])->count();
    @endphp

    {{-- HERO VEHICLE COCKPIT CARD --}}
    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-xs relative overflow-hidden">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
            {{-- Vehicle Identity & License Plate Chassis --}}
            <div class="flex items-start gap-4">
                {{-- Vehicle Profile Photo / Visual Avatar --}}
                <div class="relative shrink-0 group">
                    @if ($vehicle->image_path)
                        <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $vehicle->image_path) }}', title: 'Foto Profil Armada {{ $vehicle->plate_number }}', subtitle: '{{ trim($vehicle->brand . ' ' . $vehicle->model) }}' })" title="Klik untuk memperbesar foto armada"
                            class="relative h-20 w-20 sm:h-24 sm:w-24 rounded-3xl overflow-hidden border-2 border-slate-200/80 shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 block">
                            <img src="{{ asset('storage/' . $vehicle->image_path) }}" alt="{{ $vehicle->plate_number }}"
                                class="h-full w-full object-cover group-hover:scale-105 transition duration-300">
                            <span class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-base transition">
                                <i class="bi bi-zoom-in"></i>
                            </span>
                        </button>
                    @else
                        <div class="h-20 w-20 sm:h-24 sm:w-24 rounded-3xl flex items-center justify-center text-3xl sm:text-4xl shadow-xs shrink-0
                            {{ $vehicle->category === 'commercial_truck' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                            @if ($vehicle->category === 'commercial_truck')
                                <i class="bi bi-truck"></i>
                            @else
                                <i class="bi bi-car-front"></i>
                            @endif
                        </div>
                    @endif

                    @if ($canManage)
                        <button type="button" wire:click="openPhotoModal" title="{{ $vehicle->image_path ? 'Ubah / Hapus Foto' : 'Unggah Foto Profil' }}"
                            class="absolute -bottom-1.5 -right-1.5 h-7 w-7 rounded-xl bg-white border border-slate-200 text-slate-700 hover:text-indigo-600 hover:border-indigo-300 shadow-xs flex items-center justify-center text-xs transition active:scale-95">
                            <i class="bi bi-camera"></i>
                        </button>
                    @endif
                </div>

                <div class="space-y-1.5">
                    {{-- Plate Chassis & Tags --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="inline-flex items-center rounded-xl bg-slate-950 px-3 py-1 text-white shadow-xs">
                            <span class="font-mono text-base sm:text-lg font-black tracking-wider text-slate-50">
                                {{ $vehicle->plate_number }}
                            </span>
                        </div>

                        <span class="rounded-lg bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                            {{ $vehicle->category_label }}
                        </span>

                        <span class="rounded-lg bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">
                            {{ $vehicle->fuel_type_label }}
                        </span>
                    </div>

                    {{-- Vehicle Description & Responsible Driver --}}
                    <div class="flex flex-wrap items-center gap-x-3 text-xs text-slate-500 font-medium">
                        <span>{{ trim($vehicle->brand . ' ' . $vehicle->model) }} {{ $vehicle->year ? "({$vehicle->year})" : '' }}</span>
                        <span>•</span>
                        <span>{{ __('fleet.show.driver_operational') }} <strong class="text-slate-800">{{ $vehicle->driver_name ?: __('fleet.show.driver_unassigned') }}</strong></span>
                    </div>
                </div>
            </div>

            {{-- Operational Action Buttons --}}
            <div class="flex flex-wrap items-center gap-2 pt-2 lg:pt-0">
                {{-- QR Code Button --}}
                <button type="button" wire:click="openQrModal"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition active:scale-95 shadow-xs">
                    <i class="bi bi-qr-code text-sm text-indigo-600"></i>
                    <span>{{ __('fleet.show.btn_print_qr') }}</span>
                </button>

                @if (!$vehicle->is_sold)
                    {{-- P2H Primary Action (Context Aware) --}}
                    @if ($isOut)
                        <a href="{{ route('vehicles.inspect', ['vehicle' => $vehicle, 'type' => 'check_in']) }}"
                            class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-amber-600 transition active:scale-95">
                            <i class="bi bi-box-arrow-in-down text-sm"></i>
                            <span>{{ __('fleet.show.btn_checkin') }}</span>
                        </a>
                    @else
                        <a href="{{ route('vehicles.inspect', ['vehicle' => $vehicle, 'type' => 'check_out']) }}"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition active:scale-95">
                            <i class="bi bi-box-arrow-up-right text-sm"></i>
                            <span>{{ __('fleet.show.btn_checkout') }}</span>
                        </a>
                    @endif

                    {{-- Service Action --}}
                    <a href="{{ route('services.create', $vehicle) }}"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition active:scale-95">
                        <i class="bi bi-wrench"></i>
                        <span>{{ __('fleet.show.btn_add_service') }}</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Live Operational Radar Strip (If Currently Out on Trip) --}}
        @if ($isOut && $vehicle->activeCheckOut)
            <div class="mt-4 rounded-2xl bg-amber-50/80 border border-amber-200 p-3.5 text-xs text-amber-950 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500 animate-ping shrink-0"></span>
                    <div>
                        <span class="font-bold text-amber-900">{{ __('fleet.show.active_trip_alert') }}</span>
                        <span class="text-amber-800 text-[11px] block sm:inline sm:ml-2">
                            {{ __('fleet.index.trip_banner_driver') }} <strong>{{ $vehicle->activeCheckOut->driver_name }}</strong>
                            • {{ __('fleet.show.departed_at') }} {{ $vehicle->activeCheckOut->created_at->isoFormat('DD MMM, HH:mm') }} WIB
                            • KM: {{ number_format($vehicle->activeCheckOut->odometer) }} km
                            @if ($vehicle->activeCheckOut->trip_purpose)
                                — <em>"{{ $vehicle->activeCheckOut->trip_purpose }}"</em>
                            @endif
                        </span>
                    </div>
                </div>

                <a href="{{ route('vehicles.inspect', ['vehicle' => $vehicle, 'type' => 'check_in']) }}"
                    class="rounded-xl bg-amber-600 px-3 py-1.5 font-bold text-white hover:bg-amber-700 shrink-0 text-center text-[11px]">
                    {{ __('fleet.show.btn_checkin') }} →
                </a>
            </div>
        @endif
    </div>

    {{-- 3 COCKPIT KPI CARDS (Minimalist & Punchy) --}}
    <div class="grid gap-3 sm:grid-cols-3">
        {{-- Card 1: Odometer --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('fleet.show.gauge_odometer') }}</span>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="text-2xl font-black font-mono text-slate-900">{{ number_format($vehicle->odometer) }}</span>
                <span class="text-xs font-semibold text-slate-400">KM</span>
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">{{ __('fleet.index.last_service') }}</span>
        </div>

        {{-- Card 2: Periodic Service Progress --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('fleet.tabs.services') }} (10.000 KM)</span>
                <span class="text-xs font-bold font-mono {{ $serviceProgressPercent >= 90 ? 'text-rose-600' : 'text-indigo-600' }}">
                    {{ $serviceProgressPercent }}%
                </span>
            </div>
            <div class="mt-2 h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500 {{ $serviceProgressPercent >= 90 ? 'bg-rose-500' : ($serviceProgressPercent >= 70 ? 'bg-amber-500' : 'bg-indigo-600') }}"
                    style="width: {{ $serviceProgressPercent }}%"></div>
            </div>
            <span class="text-[11px] text-slate-500 mt-1.5 block">
                {{ number_format($kmSinceLastService) }} km sejak servis terakhir
            </span>
        </div>

        {{-- Card 3: Compliance Status --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('fleet.tabs.documents') }}</span>
            <div class="mt-1 flex items-center gap-2">
                @if ($hasExpired > 0)
                    <span class="h-2.5 w-2.5 rounded-full bg-rose-500 shrink-0"></span>
                    <span class="text-sm font-bold text-rose-700">{{ $hasExpired }} Expired</span>
                @elseif ($hasWarning > 0)
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500 shrink-0"></span>
                    <span class="text-sm font-bold text-amber-700">{{ $hasWarning }} Due Soon</span>
                @else
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                    <span class="text-sm font-bold text-emerald-700">OK</span>
                @endif
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">
                {{ $vehicle->requires_kir ? 'KIR & STNK' : 'STNK 1th & 5th' }}
            </span>
        </div>
    </div>

    {{-- SEGMENTED TABS (Clean & Responsive) --}}
    <div class="flex items-center gap-1.5 rounded-2xl bg-slate-100 p-1 border border-slate-200/80 overflow-x-auto">
        <button type="button" wire:click="setTab('inspections')"
            class="rounded-xl px-4 py-2 text-xs font-bold transition whitespace-nowrap
            {{ $tab === 'inspections' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
            <i class="bi bi-clipboard-check mr-1.5"></i>
            {{ __('fleet.tabs.inspections') }}
            <span class="ml-1 rounded-full bg-slate-200 px-1.5 py-0.2 text-[10px] {{ $tab === 'inspections' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600' }}">
                {{ $inspections->total() }}
            </span>
        </button>

        <button type="button" wire:click="setTab('documents')"
            class="rounded-xl px-4 py-2 text-xs font-bold transition whitespace-nowrap
            {{ $tab === 'documents' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
            <i class="bi bi-file-earmark-text mr-1.5"></i>
            {{ __('fleet.tabs.documents') }}
            @if ($hasExpired + $hasWarning > 0)
                <span class="ml-1 rounded-full bg-rose-100 text-rose-700 px-1.5 py-0.2 text-[10px] font-bold">
                    {{ $hasExpired + $hasWarning }}
                </span>
            @endif
        </button>

        <button type="button" wire:click="setTab('services')"
            class="rounded-xl px-4 py-2 text-xs font-bold transition whitespace-nowrap
            {{ $tab === 'services' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
            <i class="bi bi-wrench mr-1.5"></i>
            {{ __('fleet.tabs.services') }}
            <span class="ml-1 rounded-full bg-slate-200 px-1.5 py-0.2 text-[10px] {{ $tab === 'services' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600' }}">
                {{ $records->total() }}
            </span>
        </button>
    </div>

    {{-- TAB 1: LOGBOOK PEMERIKSAAN HARIAN (P2H) --}}
    @if ($tab === 'inspections')
        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Catatan Pemeriksaan &amp; Perjalanan (P2H)</h2>
                    <p class="text-xs text-slate-400">Riwayat kelayakan sebelum berangkat dan saat kembali ke pool.</p>
                </div>

                <div class="flex items-center gap-2">
                    <select wire:model.live="inspectionType"
                        class="rounded-xl border border-slate-200 bg-slate-50 py-1.5 px-3 text-xs text-slate-700 focus:outline-none">
                        <option value="all">Semua Tipe</option>
                        <option value="check_out">Check-out (Berangkat)</option>
                        <option value="check_in">Check-in (Kembali)</option>
                    </select>
                </div>
            </div>

            @if ($inspections->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-xs text-slate-400">
                    <i class="bi bi-clipboard-x text-2xl text-slate-300 block mb-1"></i>
                    Belum ada riwayat inspeksi P2H untuk armada ini.
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($inspections as $ins)
                        @php $isOutTrip = $ins->inspection_type === 'check_out'; @endphp
                        <div x-data="{ showDetails: false }" class="rounded-2xl border p-4 transition
                            {{ $ins->severity === 'critical_grounded' ? 'border-rose-200 bg-rose-50/30' : ($ins->severity === 'minor' ? 'border-amber-200 bg-amber-50/20' : 'border-slate-200/80 bg-white hover:border-slate-300') }}">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                {{-- Left Info --}}
                                <div class="flex items-start gap-3">
                                    <span class="h-9 w-9 rounded-xl flex items-center justify-center text-base shrink-0
                                        {{ $isOutTrip ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                                        <i class="bi {{ $isOutTrip ? 'bi-box-arrow-up-right' : 'bi-box-arrow-in-down' }}"></i>
                                    </span>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-900">
                                                {{ $isOutTrip ? 'Check-out (Berangkat)' : 'Check-in (Kembali)' }}
                                            </span>
                                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $ins->severity_badge_classes }}">
                                                {{ $ins->severity_label }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            <span>Driver: <strong>{{ $ins->driver_name }}</strong></span>
                                            <span class="mx-1">•</span>
                                            <span>{{ $ins->created_at->isoFormat('DD MMM YYYY, HH:mm') }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Right Metrics --}}
                                <div class="flex items-center gap-3">
                                    <div class="text-left sm:text-right">
                                        <span class="font-mono text-xs font-bold text-slate-900">{{ number_format($ins->odometer) }} km</span>
                                        <span class="text-[10px] text-slate-400 block">BBM: {{ $ins->fuel_percentage }}%</span>
                                    </div>

                                    @if ($ins->trip_distance)
                                        <span class="rounded-xl bg-indigo-50 border border-indigo-200 px-2.5 py-1 text-xs font-bold text-indigo-700">
                                            +{{ number_format($ins->trip_distance) }} km
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Defect note or purpose if any --}}
                            @if ($ins->defect_notes || $ins->trip_purpose)
                                <div class="mt-3 pt-2.5 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                    <div class="text-slate-600">
                                        @if ($ins->trip_purpose)
                                            <span class="text-slate-400 mr-1">Tujuan:</span> <em>"{{ $ins->trip_purpose }}"</em>
                                        @endif
                                        @if ($ins->defect_notes)
                                            <div class="text-rose-700 font-semibold mt-0.5">
                                                <i class="bi bi-exclamation-triangle mr-1"></i> Temuan: {{ $ins->defect_notes }}
                                            </div>
                                        @endif
                                    </div>

                                    @if ($ins->defect_notes)
                                        <a href="{{ route('services.create', ['vehicle' => $vehicle, 'odometer' => $ins->odometer, 'notes' => 'Temuan P2H: ' . $ins->defect_notes]) }}"
                                            class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 border border-indigo-200 px-2.5 py-1 text-[11px] font-bold text-indigo-700 hover:bg-indigo-100 transition shrink-0">
                                            <i class="bi bi-wrench"></i> Buat Servis
                                        </a>
                                    @endif
                                </div>
                            @endif

                            {{-- Expandable Checklist Results & Photos --}}
                            @php
                                $chkResults = is_array($ins->checklist_results) ? $ins->checklist_results : json_decode($ins->checklist_results, true);
                                $totalPhotos = 0;
                                if (!empty($chkResults)) {
                                    foreach ($chkResults as $r) {
                                        if (!empty($r['photos'])) {
                                            $totalPhotos += count($r['photos']);
                                        }
                                    }
                                }
                                if (!empty($ins->defect_photos)) {
                                    $totalPhotos += count($ins->defect_photos);
                                }
                            @endphp

                            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                                <button type="button" @click="showDetails = !showDetails"
                                    class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-500 hover:text-indigo-600 transition">
                                    <i class="bi bi-list-check text-indigo-500"></i>
                                    <span x-text="showDetails ? 'Tutup Rincian Checklist' : 'Lihat Rincian Checklist & Foto'"></span>
                                    @if ($totalPhotos > 0)
                                        <span class="rounded-full bg-indigo-50 text-indigo-700 px-1.5 py-0.2 text-[10px] font-bold border border-indigo-200">
                                            <i class="bi bi-camera"></i> {{ $totalPhotos }}
                                        </span>
                                    @endif
                                </button>

                                <span class="text-[10px] text-slate-400 font-mono">
                                    P2H ID #{{ $ins->id }}
                                </span>
                            </div>

                            <div x-show="showDetails" x-cloak class="mt-3 pt-3 border-t border-dashed border-slate-200 space-y-3">
                                @if (!empty($chkResults))
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        @foreach ($chkResults as $itemKey => $item)
                                            @php
                                                $statusOk = ($item['status'] ?? 'ok') === 'ok';
                                                $pointPhotos = $item['photos'] ?? [];
                                            @endphp
                                            <div class="rounded-xl border p-2.5 text-xs {{ $statusOk ? 'border-slate-100 bg-slate-50/70' : 'border-rose-200 bg-rose-50/50' }}">
                                                <div class="flex items-center justify-between gap-1">
                                                    <span class="font-bold {{ $statusOk ? 'text-slate-800' : 'text-rose-900' }}">
                                                        {{ $item['label'] ?? ucfirst($itemKey) }}
                                                    </span>
                                                    <span class="rounded-md px-1.5 py-0.2 text-[10px] font-bold {{ $statusOk ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                                        {{ $statusOk ? 'OK' : 'Ada Isu' }}
                                                    </span>
                                                </div>

                                                @if (!empty($item['notes']))
                                                    <p class="mt-1 text-[11px] text-slate-600">
                                                        <span class="font-semibold text-slate-500">Remark:</span> {{ $item['notes'] }}
                                                    </p>
                                                @endif

                                                @if (!empty($pointPhotos))
                                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                                        @foreach ($pointPhotos as $pPath)
                                                            <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $pPath) }}', title: 'Foto Temuan P2H: {{ addslashes($item['title'] ?? 'Poin Checklist') }}', subtitle: '{{ $vehicle->plate_number }} • {{ $ins->created_at->format('d M Y H:i') }}' })"
                                                                class="group relative block cursor-pointer" title="Perbesar foto">
                                                                <img src="{{ asset('storage/' . $pPath) }}" class="h-10 w-10 object-cover rounded-md border border-slate-200 group-hover:opacity-80 group-hover:ring-2 group-hover:ring-indigo-500 transition shadow-2xs">
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if (!empty($ins->defect_photos))
                                    <div class="pt-2 border-t border-slate-100">
                                        <span class="text-[11px] font-bold text-slate-700 block mb-1">Foto Bukti Kerusakan Tambahan:</span>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($ins->defect_photos as $dPhoto)
                                                <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $dPhoto) }}', title: 'Foto Bukti Kerusakan Utama P2H', subtitle: '{{ $vehicle->plate_number }} • {{ $ins->created_at->format('d M Y H:i') }}' })"
                                                    class="group relative block cursor-pointer" title="Perbesar foto">
                                                    <img src="{{ asset('storage/' . $dPhoto) }}" class="h-12 w-12 object-cover rounded-lg border border-slate-200 group-hover:opacity-80 group-hover:ring-2 group-hover:ring-indigo-500 transition shadow-xs">
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div>{{ $inspections->links() }}</div>
            @endif
        </div>
    @endif

    {{-- TAB 2: LEGALITAS & DOKUMEN --}}
    @if ($tab === 'documents')
        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Status Legalitas KIR &amp; Pajak STNK</h2>
                    <p class="text-xs text-slate-400">Jadwal jatuh tempo uji berkala dan pajak tahunan/5 tahunan.</p>
                </div>

                @if ($canManage)
                    <button type="button" wire:click="openDocModal"
                        class="rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition">
                        <i class="bi bi-plus-lg mr-1"></i> Tambah / Perbarui Dokumen
                    </button>
                @endif
            </div>

            {{-- 3 Key Document Cards --}}
            @php
                $kirDoc = $documents->where('document_type', 'kir')->sortByDesc('expired_date')->first();
                $stnk1 = $documents->where('document_type', 'stnk_annual')->sortByDesc('expired_date')->first();
                $stnk5 = $documents->where('document_type', 'stnk_five_year')->sortByDesc('expired_date')->first();
            @endphp

            <div class="grid gap-3 sm:grid-cols-3">
                {{-- KIR Card --}}
                <div class="rounded-2xl border p-4 transition
                    {{ $vehicle->requires_kir ? ($kirDoc ? ($kirDoc->status === 'expired' ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-slate-50/60') : 'border-amber-300 bg-amber-50/30') : 'border-slate-200 bg-slate-50/30 opacity-70' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Uji Berkala (KIR)</span>
                        <span class="rounded-md {{ $vehicle->requires_kir ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600' }} px-2 py-0.5 text-[10px] font-bold">
                            {{ $vehicle->requires_kir ? 'Wajib Niaga' : 'Opsional' }}
                        </span>
                    </div>

                    <div class="mt-2.5">
                        @if ($kirDoc)
                            <div class="text-base font-black text-slate-900">{{ $kirDoc->expired_date->isoFormat('DD MMMM YYYY') }}</div>
                            <div class="mt-1">
                                <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $kirDoc->status_badge_classes }}">
                                    {{ $kirDoc->status_label }}
                                </span>
                            </div>
                            <div class="font-mono text-[10px] text-slate-400 mt-1.5">No: {{ $kirDoc->document_number ?: '—' }}</div>
                        @else
                            <p class="text-xs text-slate-400 italic">Belum ada data KIR</p>
                            @if ($vehicle->requires_kir && $canManage)
                                <button type="button" wire:click="openDocModal('kir')" class="mt-1.5 text-xs font-bold text-indigo-600 hover:underline">
                                    + Input KIR
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- STNK 1th Card --}}
                <div class="rounded-2xl border p-4 transition
                    {{ $stnk1 ? ($stnk1->status === 'expired' ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-slate-50/60') : 'border-amber-300 bg-amber-50/30' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Pajak STNK 1 Tahun</span>
                        <span class="rounded-md bg-emerald-100 text-emerald-700 px-2 py-0.5 text-[10px] font-bold">Tahunan</span>
                    </div>

                    <div class="mt-2.5">
                        @if ($stnk1)
                            <div class="text-base font-black text-slate-900">{{ $stnk1->expired_date->isoFormat('DD MMMM YYYY') }}</div>
                            <div class="mt-1">
                                <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $stnk1->status_badge_classes }}">
                                    {{ $stnk1->status_label }}
                                </span>
                            </div>
                            <div class="font-mono text-[10px] text-slate-400 mt-1.5">No: {{ $stnk1->document_number ?: '—' }}</div>
                        @else
                            <p class="text-xs text-slate-400 italic">Belum ada data STNK 1th</p>
                            @if ($canManage)
                                <button type="button" wire:click="openDocModal('stnk_annual')" class="mt-1.5 text-xs font-bold text-indigo-600 hover:underline">
                                    + Input Pajak 1 Th
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- STNK 5th Card --}}
                <div class="rounded-2xl border p-4 transition
                    {{ $stnk5 ? ($stnk5->status === 'expired' ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-slate-50/60') : 'border-slate-200 bg-slate-50/30' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">STNK 5 Th (Ganti Plat)</span>
                        <span class="rounded-md bg-blue-100 text-blue-700 px-2 py-0.5 text-[10px] font-bold">5 Tahun</span>
                    </div>

                    <div class="mt-2.5">
                        @if ($stnk5)
                            <div class="text-base font-black text-slate-900">{{ $stnk5->expired_date->isoFormat('DD MMMM YYYY') }}</div>
                            <div class="mt-1">
                                <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $stnk5->status_badge_classes }}">
                                    {{ $stnk5->status_label }}
                                </span>
                            </div>
                            <div class="font-mono text-[10px] text-slate-400 mt-1.5">No: {{ $stnk5->document_number ?: '—' }}</div>
                        @else
                            <p class="text-xs text-slate-400 italic">Belum ada data STNK 5th</p>
                            @if ($canManage)
                                <button type="button" wire:click="openDocModal('stnk_five_year')" class="mt-1.5 text-xs font-bold text-indigo-600 hover:underline">
                                    + Input STNK 5 Th
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- Table All Historical Documents --}}
            <div class="space-y-2 pt-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Daftar Berkas Terunggah</span>

                @if ($documents->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-200 p-6 text-center text-xs text-slate-400">
                        Belum ada dokumen yang diunggah.
                    </div>
                @else
                    <div class="overflow-x-auto rounded-2xl border border-slate-200/80">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                            <thead class="bg-slate-50 font-semibold text-slate-600 uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3">Jenis Dokumen</th>
                                    <th class="px-4 py-3">No. Dokumen</th>
                                    <th class="px-4 py-3">Jatuh Tempo</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Berkas</th>
                                    @if ($canManage)
                                        <th class="px-4 py-3 text-right">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                                @foreach ($documents as $doc)
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $doc->type_label }}</td>
                                        <td class="px-4 py-3 font-mono text-slate-600">{{ $doc->document_number ?: '—' }}</td>
                                        <td class="px-4 py-3 font-bold">{{ $doc->expired_date->isoFormat('DD MMM YYYY') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $doc->status_badge_classes }}">
                                                {{ $doc->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($doc->attachment_path)
                                                @php $isImageDoc = (bool) preg_match('/\.(jpg|jpeg|png|webp)$/i', $doc->attachment_path); @endphp
                                                @if ($isImageDoc)
                                                    <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $doc->attachment_path) }}', title: 'Lampiran Dokumen: {{ $doc->document_type_label }}', subtitle: '{{ $vehicle->plate_number }} • No: {{ $doc->document_number ?: '-' }}' })"
                                                        class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-medium hover:underline cursor-pointer">
                                                        <i class="bi bi-eye mr-1"></i> Pratinjau
                                                    </button>
                                                @else
                                                    <a href="{{ asset('storage/' . $doc->attachment_path) }}" target="_blank"
                                                        class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-medium hover:underline">
                                                        <i class="bi bi-file-earmark-arrow-down mr-1"></i> Unduh File
                                                    </a>
                                                @endif
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        @if ($canManage)
                                            <td class="px-4 py-3 text-right">
                                                <button type="button" wire:click="deleteDocument({{ $doc->id }})"
                                                    wire:confirm="Hapus dokumen ini?" class="text-rose-600 hover:text-rose-800">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- TAB 3: RIWAYAT SERVIS BENGKEL & BIAYA --}}
    @if ($tab === 'services')
        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Riwayat Pemeliharaan &amp; Biaya Servis</h2>
                    <p class="text-xs text-slate-400">Catatan perbaikan berkala dan penggantian suku cadang.</p>
                </div>

                @if (!$vehicle->is_sold)
                    <a href="{{ route('services.create', $vehicle) }}"
                        class="rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition">
                        <i class="bi bi-plus-lg mr-1"></i> Catat Servis Baru
                    </a>
                @endif
            </div>

            {{-- Cost Summary Chips --}}
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Biaya Servis {{ now()->year }} (YTD)</span>
                    <div class="text-xl font-black text-slate-900 mt-1">Rp {{ number_format($ytdCost, 0, ',', '.') }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Biaya Seumur Hidup</span>
                    <div class="text-xl font-black text-indigo-700 mt-1">Rp {{ number_format($lifetimeCost, 0, ',', '.') }}</div>
                </div>
            </div>

            {{-- Records Table --}}
            @if ($records->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-xs text-slate-400">
                    Belum ada riwayat servis untuk armada ini.
                </div>
            @else
                <div class="overflow-x-auto rounded-2xl border border-slate-200/80">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                        <thead class="bg-slate-50 font-semibold text-slate-600 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Tanggal Servis</th>
                                <th class="px-4 py-3">Bengkel</th>
                                <th class="px-4 py-3">Odometer</th>
                                <th class="px-4 py-3">Suku Cadang / Tindakan</th>
                                <th class="px-4 py-3 text-right">Biaya</th>
                                @if ($canManage)
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                            @foreach ($records as $rec)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-4 py-3 font-bold text-slate-900 whitespace-nowrap">{{ $rec->service_date->isoFormat('DD MMM YYYY') }}</td>
                                    <td class="px-4 py-3">{{ $rec->workshop ?: 'Bengkel Internal' }}</td>
                                    <td class="px-4 py-3 font-semibold whitespace-nowrap">{{ number_format($rec->odometer) }} km</td>
                                    <td class="px-4 py-3">
                                        @if ($rec->items->isNotEmpty())
                                            <ul class="list-disc list-inside space-y-0.5 text-slate-600">
                                                @foreach ($rec->items->take(2) as $it)
                                                    <li>{{ $it->part_name }} ({{ $it->action }})</li>
                                                @endforeach
                                                @if ($rec->items->count() > 2)
                                                    <li class="text-slate-400">+{{ $rec->items->count() - 2 }} item lainnya</li>
                                                @endif
                                            </ul>
                                        @else
                                            <span class="text-slate-400 italic">{{ $rec->notes ?: '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-900 whitespace-nowrap">
                                        Rp {{ number_format($rec->total_cost, 0, ',', '.') }}
                                    </td>
                                    @if ($canManage)
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('services.edit', $rec) }}" class="text-indigo-600 hover:text-indigo-800 mr-2">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" wire:click="deleteService({{ $rec->id }})" wire:confirm="Hapus catatan servis ini?" class="text-rose-600 hover:text-rose-800">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div>{{ $records->links() }}</div>
            @endif
        </div>
    @endif

    {{-- MODAL CETAK STIKER QR CODE (Dashboard Physical Sticker) --}}
    @if ($showQrModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto">
            <div class="relative w-full max-w-sm rounded-3xl bg-white shadow-2xl border border-slate-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-qr-code text-indigo-600 text-lg"></i>
                        <h3 class="text-sm font-bold text-slate-900">Stiker QR Code Armada</h3>
                    </div>
                    <button type="button" wire:click="closeQrModal" class="text-slate-400 hover:text-slate-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                {{-- Printable Sticker Body --}}
                <div id="printable-qr-sticker" class="rounded-2xl border-2 border-slate-900 p-5 bg-white text-center space-y-3">
                    {{-- Company Badge --}}
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                        DAIJO INDUSTRIAL • P2H FLEET
                    </div>

                    {{-- Plate Chassis --}}
                    <div class="inline-block rounded-xl bg-slate-950 px-4 py-1.5 text-white shadow-xs">
                        <div class="font-mono text-xl font-black tracking-wider text-slate-50">
                            {{ $vehicle->plate_number }}
                        </div>
                    </div>

                    <div class="text-xs font-semibold text-slate-700">
                        {{ trim($vehicle->brand . ' ' . $vehicle->model) }} {{ $vehicle->year ? "({$vehicle->year})" : '' }}
                    </div>

                    {{-- High-Resolution QR Code --}}
                    <div class="flex justify-center p-2 bg-white rounded-xl">
                        @if ($qrCodeBase64)
                            <img src="data:image/png;base64,{{ $qrCodeBase64 }}" alt="QR Code {{ $vehicle->plate_number }}"
                                class="w-48 h-48 rounded-lg">
                        @else
                            <div class="w-48 h-48 flex items-center justify-center text-xs text-rose-500 border border-rose-200 rounded-lg">
                                Gagal menghasilkan QR Code
                            </div>
                        @endif
                    </div>

                    {{-- Scanning Instruction --}}
                    <div class="text-[11px] font-bold text-slate-800">
                        Scan untuk P2H Check-out &amp; Check-in
                    </div>

                    {{-- UUID Monospace Footer --}}
                    <div class="text-[9px] font-mono text-slate-400 truncate">
                        ID: {{ $vehicle->id }}
                    </div>
                </div>

                {{-- Actions: Print & Close --}}
                <div class="flex items-center gap-2 pt-2">
                    <button type="button" onclick="window.print()"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-slate-800 transition active:scale-95">
                        <i class="bi bi-printer"></i>
                        <span>Cetak Stiker</span>
                    </button>
                    <button type="button" wire:click="closeQrModal"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL TAMBAH / PERPANJANG DOKUMEN --}}
    @if ($showDocModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4 overflow-y-auto">
            <div class="relative w-full max-w-lg rounded-3xl bg-white shadow-2xl border border-slate-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Input / Perbarui Dokumen Legalitas</h3>
                    <button type="button" wire:click="closeDocModal" class="text-slate-400 hover:text-slate-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <form wire:submit.prevent="saveDocument" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Dokumen <span class="text-rose-500">*</span></label>
                        <select wire:model.defer="doc_type"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                            <option value="kir">Uji Berkala (KIR) — Khusus Mobil Gede / Niaga</option>
                            <option value="stnk_annual">Pajak STNK 1 Tahunan</option>
                            <option value="stnk_five_year">STNK 5 Tahunan &amp; Ganti Plat Kaleng</option>
                            <option value="insurance">Asuransi Kendaraan</option>
                            <option value="other">Dokumen Lainnya</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Dokumen / Surat</label>
                        <input type="text" wire:model.defer="doc_number" placeholder="Contoh: KIR-JKT-123456"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jatuh Tempo <span class="text-rose-500">*</span></label>
                            <input type="date" wire:model.defer="expired_date"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                            @error('expired_date')
                                <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Diperbarui</label>
                            <input type="date" wire:model.defer="last_renewed_date"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Unggah Scan / Foto Fisik</label>
                        <input type="file" wire:model="attachment" accept="image/*,application/pdf"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan</label>
                        <textarea wire:model.defer="notes" rows="2" placeholder="Catatan instansi / keterangan perpanjangan..."
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" wire:click="closeDocModal"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white hover:bg-indigo-700 disabled:opacity-60">
                            Simpan Dokumen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- UNIVERSAL LIGHTBOX MODULE --}}
    <x-universal-lightbox :vehicle="$vehicle" />

    {{-- MODAL UBAH / UNGGAH FOTO PROFIL ARMADA --}}
    @if ($showPhotoModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4 overflow-y-auto">
            <div class="relative w-full max-w-md rounded-3xl bg-white shadow-2xl border border-slate-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-camera text-indigo-600 text-lg"></i>
                        <h3 class="text-sm font-bold text-slate-900">{{ __('fleet.show.photo_modal_title') }}</h3>
                    </div>
                    <button type="button" wire:click="closePhotoModal" class="text-slate-400 hover:text-slate-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <form wire:submit.prevent="saveVehiclePhoto" class="space-y-4">
                    {{-- Current or New Preview --}}
                    <div class="flex flex-col items-center justify-center text-center p-3 rounded-2xl bg-slate-50 border border-slate-200">
                        @if ($new_photo)
                            <img src="{{ $new_photo->temporaryUrl() }}" class="h-32 w-32 object-cover rounded-2xl border border-slate-200 shadow-xs mb-2">
                            <span class="text-xs font-semibold text-indigo-700">Preview</span>
                        @elseif ($vehicle->image_path)
                            <img src="{{ asset('storage/' . $vehicle->image_path) }}" class="h-32 w-32 object-cover rounded-2xl border border-slate-200 shadow-xs mb-2">
                            <span class="text-xs text-slate-500 font-medium">{{ __('fleet.show.photo_modal_title') }}</span>
                        @else
                            <div class="h-24 w-24 rounded-2xl bg-slate-100 flex flex-col items-center justify-center text-slate-400 border border-dashed border-slate-300 mb-2">
                                <i class="bi bi-camera text-3xl"></i>
                            </div>
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </div>

                    {{-- Upload input --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">{{ __('fleet.form.profile_photo') }}</label>
                        <input type="file" wire:model="new_photo" accept="image/*"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <div wire:loading wire:target="new_photo" class="text-xs text-indigo-600 font-medium mt-1">
                            <i class="bi bi-arrow-repeat animate-spin mr-1"></i> {{ __('fleet.common.loading') }}
                        </div>
                        @error('new_photo')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-3 border-t border-slate-100">
                        <div>
                            @if ($vehicle->image_path)
                                <button type="button" wire:click="deleteVehiclePhoto" wire:confirm="{{ __('fleet.show.photo_delete_confirm') }}"
                                    class="text-xs font-bold text-rose-600 hover:text-rose-800">
                                    <i class="bi bi-trash mr-1"></i> {{ __('fleet.show.photo_delete') }}
                                </button>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="closePhotoModal"
                                class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                {{ __('fleet.common.cancel') }}
                            </button>
                            <button type="submit" wire:loading.attr="disabled"
                                class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white hover:bg-indigo-700 disabled:opacity-60">
                                {{ __('fleet.show.photo_save') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- PRINT STYLESHEET (Isolates QR Sticker on Print) --}}
    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }
            #printable-qr-sticker, #printable-qr-sticker * {
                visibility: visible !important;
            }
            #printable-qr-sticker {
                position: fixed !important;
                left: 50% !important;
                top: 50% !important;
                transform: translate(-50%, -50%) !important;
                width: 80mm !important;
                border: 2px solid #0f172a !important;
                box-shadow: none !important;
            }
        }
    </style>
</div>
