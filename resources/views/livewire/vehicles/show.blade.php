<div class="max-w-6xl mx-auto px-3 sm:px-6 py-4 sm:py-6 space-y-4 sm:space-y-5">
    {{-- Breadcrumb Navigation --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('vehicles.index') }}"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900 transition">
            <i class="bi bi-chevron-left text-[11px]"></i>
            <span>{{ __('fleet.show.back_to_index') }}</span>
        </a>

        @if ($vehicle->vin)
            <span class="font-mono text-[11px] text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200/60">
                VIN: {{ $vehicle->vin }}
            </span>
        @endif
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

    {{-- HERO VEHICLE COCKPIT CARD (Unified Apple Hero Bar) --}}
    <div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-6 shadow-xs relative z-20">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 sm:gap-5">
            {{-- Vehicle Identity --}}
            <div class="flex items-start gap-3.5 sm:gap-4">
                {{-- Profile Photo with Sleek Lightbox Trigger & Manage Button --}}
                <div class="relative shrink-0 group">
                    @if ($vehicle->image_path)
                        <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $vehicle->image_path) }}', title: 'Foto Profil Armada {{ $vehicle->plate_number }}', subtitle: '{{ trim($vehicle->brand . ' ' . $vehicle->model) }}' })"
                            title="Klik untuk memperbesar foto armada"
                            class="relative h-20 w-20 sm:h-22 sm:w-22 rounded-2xl overflow-hidden border border-slate-200/90 shadow-2xs focus:outline-none focus:ring-2 focus:ring-slate-400 block cursor-pointer">
                            <img src="{{ asset('storage/' . $vehicle->image_path) }}" alt="{{ $vehicle->plate_number }}"
                                class="h-full w-full object-cover group-hover:scale-105 transition duration-300">
                            <span class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-base transition">
                                <i class="bi bi-zoom-in"></i>
                            </span>
                        </button>
                    @else
                        <div class="h-20 w-20 sm:h-22 sm:w-22 rounded-2xl flex items-center justify-center text-3xl shadow-2xs shrink-0 {{ $vehicle->category === 'commercial_truck' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700' }}">
                            @if ($vehicle->category === 'commercial_truck')
                                <i class="bi bi-truck"></i>
                            @else
                                <i class="bi bi-car-front"></i>
                            @endif
                        </div>
                    @endif

                    @if ($canManage)
                        <button type="button" wire:click="openPhotoModal" title="{{ $vehicle->image_path ? 'Ubah / Hapus Foto' : 'Unggah Foto Profil' }}"
                            class="absolute -bottom-1 -right-1 h-7 w-7 rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-slate-900 hover:border-slate-300 shadow-2xs flex items-center justify-center text-xs transition active:scale-95 cursor-pointer">
                            <i class="bi bi-camera"></i>
                        </button>
                    @endif
                </div>

                <div class="space-y-1.5">
                    {{-- Plate Chassis & Badges --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="inline-flex items-center rounded-xl bg-slate-950 px-3 py-1 text-white shadow-xs">
                            <span class="font-mono text-base sm:text-lg font-black tracking-wider text-slate-50">
                                {{ $vehicle->plate_number }}
                            </span>
                        </div>

                        <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 border border-slate-200/60">
                            {{ $vehicle->category_label }}
                        </span>

                        <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 border border-slate-200/60">
                            {{ $vehicle->fuel_type_label }}
                        </span>

                        @if ($isOut)
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200/60">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                On-Trip
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200/60">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Standby di Pool
                            </span>
                        @endif
                    </div>

                    {{-- Vehicle Description & Responsible Driver --}}
                    <div class="flex flex-wrap items-center gap-x-2 text-xs text-slate-500 font-medium">
                        <span class="font-semibold text-slate-800">{{ trim($vehicle->brand . ' ' . $vehicle->model) }} {{ $vehicle->year ? "({$vehicle->year})" : '' }}</span>
                        <span class="text-slate-300">•</span>
                        <span>{{ __('fleet.show.driver_operational') }} <strong class="text-slate-800">{{ $vehicle->driver_name ?: __('fleet.show.driver_unassigned') }}</strong></span>
                    </div>
                </div>
            </div>

            {{-- Unified Action Controls (Primary P2H + Sleek Secondary Cluster) --}}
            <div class="flex flex-col sm:flex-row lg:flex-col sm:items-center lg:items-end gap-2.5 pt-2 lg:pt-0">
                {{-- Primary P2H Action Pill --}}
                @if (!$vehicle->is_sold)
                    @if ($isOut)
                        <a href="{{ route('vehicles.inspect', ['vehicle' => $vehicle, 'type' => 'check_in']) }}"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-600 px-4 py-2.5 text-xs font-bold text-white shadow-2xs transition active:scale-[0.98]">
                            <i class="bi bi-box-arrow-in-down text-sm"></i>
                            <span>{{ __('fleet.show.btn_checkin') }} →</span>
                        </a>
                    @else
                        <a href="{{ route('vehicles.inspect', ['vehicle' => $vehicle, 'type' => 'check_out']) }}"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 px-4 py-2.5 text-xs font-bold text-white shadow-2xs transition active:scale-[0.98]">
                            <i class="bi bi-box-arrow-up-right text-sm"></i>
                            <span>{{ __('fleet.show.btn_checkout') }} →</span>
                        </a>
                    @endif
                @endif

                {{-- Secondary Action Pills --}}
                <div class="flex items-center gap-1.5 w-full sm:w-auto justify-end"
                    x-data="{ qrMenuOpen: false, copied: false }">

                    {{-- Direct 1-Click Print Button with Dropdown Options --}}
                    <div class="relative inline-flex rounded-xl shadow-2xs">
                        <button type="button"
                            @click="printVehicleQrSticker('{{ $qrCodeBase64 }}', '{{ addslashes($vehicle->plate_number) }}', '{{ addslashes(trim($vehicle->brand . ' ' . $vehicle->model)) }}', '{{ $vehicle->year }}', '{{ addslashes($vehicle->id) }}')"
                            title="Cetak Langsung Stiker QR"
                            class="inline-flex items-center gap-1.5 rounded-l-xl bg-white border border-slate-200/80 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                            <i class="bi bi-printer text-xs text-slate-600"></i>
                            <span>{{ __('fleet.show.btn_print_qr') }}</span>
                        </button>
                        <button type="button" @click="qrMenuOpen = !qrMenuOpen" @click.outside="qrMenuOpen = false"
                            title="Opsi Tambahan Stiker"
                            class="inline-flex items-center px-1.5 rounded-r-xl bg-white border-y border-r border-slate-200/80 text-slate-400 hover:bg-slate-50 hover:text-slate-800 transition cursor-pointer">
                            <i class="bi bi-chevron-down text-[10px]"></i>
                        </button>

                        {{-- Dropdown Menu (Download PNG, Copy UUID, Preview) --}}
                        <div x-show="qrMenuOpen" x-cloak
                            class="absolute right-0 top-full mt-1.5 w-48 rounded-2xl bg-white border border-slate-200 shadow-xl p-1 z-50 space-y-0.5 text-xs">
                            @if ($qrCodeBase64)
                                <a href="data:image/png;base64,{{ $qrCodeBase64 }}" download="QR-Armada-{{ str_replace(' ', '-', $vehicle->plate_number) }}.png"
                                    @click="qrMenuOpen = false"
                                    class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 font-medium transition">
                                    <i class="bi bi-download text-slate-500"></i>
                                    <span>Unduh Gambar PNG</span>
                                </a>
                            @endif
                            <button type="button"
                                @click="navigator.clipboard.writeText('{{ $vehicle->id }}'); copied = true; setTimeout(() => { copied = false; qrMenuOpen = false; }, 1500)"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 font-medium transition cursor-pointer">
                                <i class="bi" :class="copied ? 'bi-check2 text-emerald-600' : 'bi-clipboard text-slate-500'"></i>
                                <span x-text="copied ? 'Tersalin!' : 'Salin UUID Armada'"></span>
                            </button>
                            <button type="button" wire:click="openQrModal" @click="qrMenuOpen = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-50 font-medium transition cursor-pointer">
                                <i class="bi bi-eye text-slate-500"></i>
                                <span>Pratinjau Stiker</span>
                            </button>
                        </div>
                    </div>

                    @if (!$vehicle->is_sold)
                        <a href="{{ route('services.create', $vehicle) }}"
                            class="inline-flex items-center gap-1 rounded-xl bg-white border border-slate-200/80 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                            <i class="bi bi-wrench text-xs text-slate-500"></i>
                            <span>{{ __('fleet.show.btn_add_service') }}</span>
                        </a>
                    @endif

                    @if ($canManage)
                        <a href="{{ route('vehicles.edit', $vehicle) }}"
                            class="inline-flex items-center gap-1 rounded-xl bg-white border border-slate-200/80 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                            <i class="bi bi-pencil text-xs text-slate-500"></i>
                            <span>{{ __('fleet.show.btn_edit_vehicle') }}</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Apple Live Activity Dynamic Banner (When Vehicle is On-Trip) --}}
        @if ($isOut && $vehicle->activeCheckOut)
            <div class="mt-4 rounded-2xl bg-amber-50/70 border border-amber-200/60 p-3 text-xs text-amber-950 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="h-2 w-2 rounded-full bg-amber-500 shrink-0"></span>
                    <div class="min-w-0">
                        <span class="font-bold text-amber-900">{{ __('fleet.show.active_trip_alert') }}:</span>
                        <span class="text-amber-800 text-[11px] ml-1">
                            Driver: <strong>{{ $vehicle->activeCheckOut->driver_name }}</strong>
                            • Berangkat {{ $vehicle->activeCheckOut->created_at->isoFormat('HH:mm') }} WIB
                            • {{ number_format($vehicle->activeCheckOut->odometer) }} km
                            @if ($vehicle->activeCheckOut->trip_purpose)
                                — <em>"{{ $vehicle->activeCheckOut->trip_purpose }}"</em>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- UNIFIED GLANCE STRIP (3 Essential Metrics in 1 sleek bar) --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-3.5 sm:p-4 shadow-xs grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-slate-100 gap-3 sm:gap-0">
        {{-- Metric 1: Odometer --}}
        <div class="sm:px-4 first:sm:pl-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">{{ __('fleet.show.gauge_odometer') }}</span>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="text-xl sm:text-2xl font-black font-mono text-slate-900">{{ number_format($vehicle->odometer) }}</span>
                <span class="text-xs font-semibold text-slate-400">KM</span>
            </div>
            <span class="text-[11px] text-slate-400 block mt-0.5">
                @if ($last)
                    Servis: {{ number_format($lastKm) }} km ({{ $last->service_date->isoFormat('DD/MM/YY') }})
                @else
                    Belum pernah servis tercatat
                @endif
            </span>
        </div>

        {{-- Metric 2: Periodic Service Progress --}}
        <div class="pt-3 sm:pt-0 sm:px-4">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">{{ __('fleet.tabs.services') }} (10.000 KM)</span>
                <span class="text-xs font-bold font-mono {{ $serviceProgressPercent >= 90 ? 'text-rose-600' : 'text-slate-800' }}">
                    {{ $serviceProgressPercent }}%
                </span>
            </div>
            <div class="mt-2 h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500 {{ $serviceProgressPercent >= 90 ? 'bg-rose-500' : ($serviceProgressPercent >= 70 ? 'bg-amber-500' : 'bg-slate-900') }}"
                    style="width: {{ $serviceProgressPercent }}%"></div>
            </div>
            <span class="text-[11px] text-slate-500 block mt-1">
                {{ number_format($kmSinceLastService) }} km sejak servis terakhir
            </span>
        </div>

        {{-- Metric 3: Legal Compliance Status --}}
        <div class="pt-3 sm:pt-0 sm:px-4 last:sm:pr-1 cursor-pointer hover:bg-slate-50/50 rounded-xl transition"
            wire:click="setTab('documents')">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">{{ __('fleet.tabs.documents') }}</span>
            <div class="mt-1 flex items-center gap-2">
                @if ($hasExpired > 0)
                    <span class="h-2 w-2 rounded-full bg-rose-500 shrink-0"></span>
                    <span class="text-xs sm:text-sm font-bold text-rose-700">{{ $hasExpired }} Dokumen Kadaluarsa</span>
                @elseif ($hasWarning > 0)
                    <span class="h-2 w-2 rounded-full bg-amber-500 shrink-0"></span>
                    <span class="text-xs sm:text-sm font-bold text-amber-700">{{ $hasWarning }} Segera Habis</span>
                @else
                    <span class="h-2 w-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <span class="text-xs sm:text-sm font-bold text-emerald-700">Semua Dokumen Valid</span>
                @endif
            </div>
            <span class="text-[11px] text-slate-400 block mt-0.5">
                {{ $vehicle->requires_kir ? 'KIR & STNK Tahunan' : 'Pajak STNK 1th & 5th' }} →
            </span>
        </div>
    </div>

    {{-- APPLE SEGMENTED TAB CONTROL --}}
    <div class="overflow-x-auto pb-1 sm:pb-0 -mx-3 px-3 sm:mx-0 sm:px-0">
        <div class="inline-flex items-center p-1 bg-slate-100/90 rounded-2xl border border-slate-200/60 min-w-full sm:min-w-0">
            <button type="button" wire:click="setTab('inspections')"
                class="flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer flex-1 sm:flex-initial {{ $tab === 'inspections' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                <i class="bi bi-clipboard-check text-xs"></i>
                <span>{{ __('fleet.tabs.inspections') }}</span>
                <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $tab === 'inspections' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-400 font-medium' }}">
                    {{ $inspections->total() }}
                </span>
            </button>

            <button type="button" wire:click="setTab('documents')"
                class="flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer flex-1 sm:flex-initial {{ $tab === 'documents' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                <i class="bi bi-file-earmark-text text-xs"></i>
                <span>{{ __('fleet.tabs.documents') }}</span>
                @if ($hasExpired + $hasWarning > 0)
                    <span class="rounded-full bg-rose-50 text-rose-700 border border-rose-200/60 px-1.5 py-0.2 text-[10px] font-bold">
                        {{ $hasExpired + $hasWarning }}
                    </span>
                @else
                    <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $tab === 'documents' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-400 font-medium' }}">
                        {{ $documents->count() }}
                    </span>
                @endif
            </button>

            <button type="button" wire:click="setTab('services')"
                class="flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer flex-1 sm:flex-initial {{ $tab === 'services' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                <i class="bi bi-wrench text-xs"></i>
                <span>{{ __('fleet.tabs.services') }}</span>
                <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $tab === 'services' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-400 font-medium' }}">
                    {{ $records->total() }}
                </span>
            </button>
        </div>
    </div>

    {{-- TAB 1: LOGBOOK PEMERIKSAAN HARIAN (P2H) --}}
    @if ($tab === 'inspections')
        <div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Catatan Pemeriksaan &amp; Perjalanan (P2H)</h2>
                    <p class="text-xs text-slate-400">Riwayat kelayakan pra-jalan dan kepulangan armada ke pool.</p>
                </div>

                <div class="flex items-center gap-2">
                    <select wire:model.live="inspectionType"
                        class="rounded-xl border border-slate-200 bg-slate-50/70 py-1.5 px-3 text-xs text-slate-700 focus:outline-none">
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
                        <div x-data="{ showDetails: false }" class="rounded-2xl border p-3.5 sm:p-4 transition
                            {{ $ins->severity === 'critical_grounded' ? 'border-rose-200 bg-rose-50/30' : ($ins->severity === 'minor' ? 'border-amber-200 bg-amber-50/20' : 'border-slate-200/80 bg-white hover:border-slate-300') }}">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                {{-- Left Info --}}
                                <div class="flex items-start gap-3">
                                    <span class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl flex items-center justify-center text-base shrink-0
                                        {{ $isOutTrip ? 'bg-slate-100 text-slate-700' : 'bg-amber-100 text-amber-700' }}">
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
                                        <span class="rounded-xl bg-slate-100 border border-slate-200/70 px-2.5 py-1 text-xs font-bold text-slate-800">
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
                                            class="inline-flex items-center gap-1 rounded-xl bg-slate-900 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-slate-800 transition shrink-0 shadow-2xs">
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
                                    class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-500 hover:text-slate-900 transition cursor-pointer">
                                    <i class="bi bi-list-check text-slate-700"></i>
                                    <span x-text="showDetails ? 'Tutup Rincian Checklist' : 'Lihat Rincian Checklist & Foto'"></span>
                                    @if ($totalPhotos > 0)
                                        <span class="rounded-full bg-slate-100 text-slate-700 px-1.5 py-0.2 text-[10px] font-bold border border-slate-200">
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
                                                                <img src="{{ asset('storage/' . $pPath) }}" class="h-10 w-10 object-cover rounded-md border border-slate-200 group-hover:opacity-80 group-hover:ring-2 group-hover:ring-slate-400 transition shadow-2xs">
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
                                                    <img src="{{ asset('storage/' . $dPhoto) }}" class="h-12 w-12 object-cover rounded-lg border border-slate-200 group-hover:opacity-80 group-hover:ring-2 group-hover:ring-slate-400 transition shadow-xs">
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

    {{-- TAB 2: LEGALITAS & DOKUMEN (Unified Interactive Cards - Eliminating duplicate table) --}}
    @if ($tab === 'documents')
        <div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Status Legalitas KIR &amp; Pajak STNK</h2>
                    <p class="text-xs text-slate-400">Jadwal jatuh tempo uji berkala dan pajak tahunan/5 tahunan.</p>
                </div>

                @if ($canManage)
                    <button type="button" wire:click="openDocModal"
                        class="rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-bold text-white shadow-2xs hover:bg-slate-800 transition cursor-pointer">
                        <i class="bi bi-plus-lg mr-1"></i> Tambah Dokumen Lain
                    </button>
                @endif
            </div>

            @php
                $kirDoc = $documents->where('document_type', 'kir')->sortByDesc('expired_date')->first();
                $stnk1 = $documents->where('document_type', 'stnk_annual')->sortByDesc('expired_date')->first();
                $stnk5 = $documents->where('document_type', 'stnk_five_year')->sortByDesc('expired_date')->first();
                $otherDocs = $documents->whereNotIn('document_type', ['kir', 'stnk_annual', 'stnk_five_year']);
            @endphp

            {{-- 3 Unified Interactive Legal Cards --}}
            <div class="grid gap-3 sm:grid-cols-3">
                {{-- Card 1: KIR --}}
                <div class="rounded-2xl border p-4 transition flex flex-col justify-between
                    {{ $vehicle->requires_kir ? ($kirDoc ? ($kirDoc->status === 'expired' ? 'border-rose-300 bg-rose-50/30' : 'border-slate-200 bg-white shadow-2xs') : 'border-amber-300 bg-amber-50/20') : 'border-slate-200 bg-slate-50/30 opacity-70' }}">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Uji Berkala (KIR)</span>
                            <span class="rounded-md {{ $vehicle->requires_kir ? 'bg-slate-100 text-slate-700' : 'bg-slate-100 text-slate-500' }} px-2 py-0.5 text-[10px] font-bold">
                                {{ $vehicle->requires_kir ? 'Wajib Niaga' : 'Opsional' }}
                            </span>
                        </div>

                        <div class="mt-3">
                            @if ($kirDoc)
                                <div class="text-base font-black text-slate-900">{{ $kirDoc->expired_date->isoFormat('DD MMMM YYYY') }}</div>
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $kirDoc->status_badge_classes }}">
                                        {{ $kirDoc->status_label }}
                                    </span>
                                </div>
                                <div class="font-mono text-[11px] text-slate-500 mt-2">No: {{ $kirDoc->document_number ?: '—' }}</div>
                            @else
                                <p class="text-xs text-slate-400 italic">Belum ada data dokumen KIR.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Actions: Preview & Renew --}}
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        @if ($kirDoc && $kirDoc->attachment_path)
                            @php $isImageDoc = (bool) preg_match('/\.(jpg|jpeg|png|webp)$/i', $kirDoc->attachment_path); @endphp
                            @if ($isImageDoc)
                                <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $kirDoc->attachment_path) }}', title: 'Lampiran Dokumen: {{ $kirDoc->document_type_label }}', subtitle: '{{ $vehicle->plate_number }} • No: {{ $kirDoc->document_number ?: '-' }}' })"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900 cursor-pointer">
                                    <i class="bi bi-eye"></i> Pratinjau
                                </button>
                            @else
                                <a href="{{ asset('storage/' . $kirDoc->attachment_path) }}" target="_blank"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900">
                                    <i class="bi bi-file-earmark-arrow-down"></i> Unduh
                                </a>
                            @endif
                        @else
                            <span class="text-[11px] text-slate-400">Tanpa berkas</span>
                        @endif

                        @if ($canManage)
                            <button type="button" wire:click="openDocModal('kir')"
                                class="rounded-lg bg-slate-100 hover:bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 transition cursor-pointer">
                                {{ $kirDoc ? 'Perbarui' : '+ Input KIR' }}
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Card 2: STNK 1 Tahun --}}
                <div class="rounded-2xl border p-4 transition flex flex-col justify-between
                    {{ $stnk1 ? ($stnk1->status === 'expired' ? 'border-rose-300 bg-rose-50/30' : 'border-slate-200 bg-white shadow-2xs') : 'border-amber-300 bg-amber-50/20' }}">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Pajak STNK 1 Tahun</span>
                            <span class="rounded-md bg-emerald-50 text-emerald-700 px-2 py-0.5 text-[10px] font-bold border border-emerald-200/60">
                                Tahunan
                            </span>
                        </div>

                        <div class="mt-3">
                            @if ($stnk1)
                                <div class="text-base font-black text-slate-900">{{ $stnk1->expired_date->isoFormat('DD MMMM YYYY') }}</div>
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $stnk1->status_badge_classes }}">
                                        {{ $stnk1->status_label }}
                                    </span>
                                </div>
                                <div class="font-mono text-[11px] text-slate-500 mt-2">No: {{ $stnk1->document_number ?: '—' }}</div>
                            @else
                                <p class="text-xs text-slate-400 italic">Belum ada data STNK 1th.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Actions: Preview & Renew --}}
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        @if ($stnk1 && $stnk1->attachment_path)
                            @php $isImageDoc = (bool) preg_match('/\.(jpg|jpeg|png|webp)$/i', $stnk1->attachment_path); @endphp
                            @if ($isImageDoc)
                                <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $stnk1->attachment_path) }}', title: 'Lampiran Dokumen: {{ $stnk1->document_type_label }}', subtitle: '{{ $vehicle->plate_number }} • No: {{ $stnk1->document_number ?: '-' }}' })"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900 cursor-pointer">
                                    <i class="bi bi-eye"></i> Pratinjau
                                </button>
                            @else
                                <a href="{{ asset('storage/' . $stnk1->attachment_path) }}" target="_blank"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900">
                                    <i class="bi bi-file-earmark-arrow-down"></i> Unduh
                                </a>
                            @endif
                        @else
                            <span class="text-[11px] text-slate-400">Tanpa berkas</span>
                        @endif

                        @if ($canManage)
                            <button type="button" wire:click="openDocModal('stnk_annual')"
                                class="rounded-lg bg-slate-100 hover:bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 transition cursor-pointer">
                                {{ $stnk1 ? 'Perbarui' : '+ Input STNK' }}
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Card 3: STNK 5 Tahun --}}
                <div class="rounded-2xl border p-4 transition flex flex-col justify-between
                    {{ $stnk5 ? ($stnk5->status === 'expired' ? 'border-rose-300 bg-rose-50/30' : 'border-slate-200 bg-white shadow-2xs') : 'border-slate-200 bg-white shadow-2xs' }}">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">STNK 5 Th (Ganti Plat)</span>
                            <span class="rounded-md bg-blue-50 text-blue-700 px-2 py-0.5 text-[10px] font-bold border border-blue-200/60">
                                5 Tahun
                            </span>
                        </div>

                        <div class="mt-3">
                            @if ($stnk5)
                                <div class="text-base font-black text-slate-900">{{ $stnk5->expired_date->isoFormat('DD MMMM YYYY') }}</div>
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $stnk5->status_badge_classes }}">
                                        {{ $stnk5->status_label }}
                                    </span>
                                </div>
                                <div class="font-mono text-[11px] text-slate-500 mt-2">No: {{ $stnk5->document_number ?: '—' }}</div>
                            @else
                                <p class="text-xs text-slate-400 italic">Belum ada data STNK 5th.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Actions: Preview & Renew --}}
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        @if ($stnk5 && $stnk5->attachment_path)
                            @php $isImageDoc = (bool) preg_match('/\.(jpg|jpeg|png|webp)$/i', $stnk5->attachment_path); @endphp
                            @if ($isImageDoc)
                                <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $stnk5->attachment_path) }}', title: 'Lampiran Dokumen: {{ $stnk5->document_type_label }}', subtitle: '{{ $vehicle->plate_number }} • No: {{ $stnk5->document_number ?: '-' }}' })"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900 cursor-pointer">
                                    <i class="bi bi-eye"></i> Pratinjau
                                </button>
                            @else
                                <a href="{{ asset('storage/' . $stnk5->attachment_path) }}" target="_blank"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900">
                                    <i class="bi bi-file-earmark-arrow-down"></i> Unduh
                                </a>
                            @endif
                        @else
                            <span class="text-[11px] text-slate-400">Tanpa berkas</span>
                        @endif

                        @if ($canManage)
                            <button type="button" wire:click="openDocModal('stnk_five_year')"
                                class="rounded-lg bg-slate-100 hover:bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 transition cursor-pointer">
                                {{ $stnk5 ? 'Perbarui' : '+ Input STNK 5th' }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Optional Secondary Section: Other Documents (Asuransi dll) --}}
            @if ($otherDocs->isNotEmpty())
                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Dokumen Lainnya (Asuransi / Polis)</span>
                    <div class="divide-y divide-slate-100 rounded-2xl border border-slate-200/80 bg-white overflow-hidden">
                        @foreach ($otherDocs as $oDoc)
                            <div class="p-3 sm:px-4 flex items-center justify-between gap-3 text-xs">
                                <div>
                                    <div class="font-bold text-slate-900">{{ $oDoc->type_label }}</div>
                                    <div class="text-[11px] text-slate-400">
                                        No: {{ $oDoc->document_number ?: '—' }} • Exp: {{ $oDoc->expired_date->isoFormat('DD MMM YYYY') }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($oDoc->attachment_path)
                                        <a href="{{ asset('storage/' . $oDoc->attachment_path) }}" target="_blank"
                                            class="text-xs text-slate-600 hover:text-slate-900 font-medium underline">
                                            Berkas
                                        </a>
                                    @endif
                                    @if ($canManage)
                                        <button type="button" wire:click="deleteDocument({{ $oDoc->id }})" wire:confirm="Hapus dokumen ini?"
                                            class="text-rose-600 hover:text-rose-800 text-xs">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 3: RIWAYAT SERVIS BENGKEL & BIAYA (Wallet-Style Cost Header & Clean Table) --}}
    @if ($tab === 'services')
        <div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Riwayat Pemeliharaan &amp; Biaya Servis</h2>
                    <p class="text-xs text-slate-400">Catatan perbaikan berkala dan penggantian suku cadang.</p>
                </div>

                @if (!$vehicle->is_sold)
                    <a href="{{ route('services.create', $vehicle) }}"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-bold text-white shadow-2xs hover:bg-slate-800 transition">
                        <i class="bi bi-plus-lg"></i>
                        <span>Catat Servis Baru</span>
                    </a>
                @endif
            </div>

            {{-- iOS Wallet-Style Compact Cost Summary Bar --}}
            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-3 sm:p-4 grid grid-cols-2 divide-x divide-slate-200/60">
                <div class="pr-3 sm:pr-4">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Biaya Tahun {{ now()->year }} (YTD)</span>
                    <div class="text-base sm:text-xl font-black text-slate-900 mt-1 font-mono">
                        Rp {{ number_format($ytdCost, 0, ',', '.') }}
                    </div>
                </div>
                <div class="pl-3 sm:pl-4">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Biaya Seumur Hidup</span>
                    <div class="text-base sm:text-xl font-black text-slate-900 mt-1 font-mono">
                        Rp {{ number_format($lifetimeCost, 0, ',', '.') }}
                    </div>
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
                                    <td class="px-4 py-3 text-right font-bold text-slate-900 whitespace-nowrap font-mono">
                                        Rp {{ number_format($rec->total_cost, 0, ',', '.') }}
                                    </td>
                                    @if ($canManage)
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('services.edit', $rec) }}" class="text-slate-600 hover:text-slate-900 mr-2">
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
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-md p-4 overflow-y-auto"
            x-data="{ copied: false }">
            <div class="relative w-full max-w-sm rounded-3xl bg-white shadow-2xl border border-slate-200/90 p-5 sm:p-6 space-y-4">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="h-7 w-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-900">
                            <i class="bi bi-qr-code text-sm"></i>
                        </span>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Stiker Fisik Armada</h3>
                            <p class="text-[10px] text-slate-400">Verifikasi unit &amp; inspeksi P2H</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeQrModal" class="h-7 w-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>

                {{-- Printable Sticker Body (Industrial Vinyl Equipment Tag) --}}
                <div id="printable-qr-sticker" class="rounded-2xl border-2 border-slate-950 p-4 sm:p-5 bg-white text-center space-y-2.5 shadow-xs">
                    {{-- Header Brand --}}
                    <div class="border-b border-slate-100 pb-2">
                        <div class="text-[11px] font-black tracking-widest text-slate-900 uppercase">
                            PT DAIJO INDUSTRIAL
                        </div>
                        <div class="text-[9px] font-bold text-slate-400 tracking-wider uppercase mt-0.5">
                            FLEET MANAGEMENT &bull; P2H SYSTEM
                        </div>
                    </div>

                    {{-- Plate Chassis with Rivet Accents --}}
                    <div class="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-4 py-1.5 text-white shadow-2xs">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400 opacity-60"></span>
                        <span class="font-mono text-xl sm:text-2xl font-black tracking-widest text-slate-50">
                            {{ $vehicle->plate_number }}
                        </span>
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400 opacity-60"></span>
                    </div>

                    {{-- Vehicle Model --}}
                    <div class="text-xs font-bold text-slate-800">
                        {{ trim($vehicle->brand . ' ' . $vehicle->model) }} {{ $vehicle->year ? "({$vehicle->year})" : '' }}
                    </div>

                    {{-- High-Resolution QR Code Container --}}
                    <div class="flex justify-center p-2 bg-white rounded-xl">
                        @if ($qrCodeBase64)
                            <div class="p-1.5 rounded-xl border border-slate-200/80 bg-white">
                                <img src="data:image/png;base64,{{ $qrCodeBase64 }}" alt="QR Code {{ $vehicle->plate_number }}"
                                    class="w-44 h-44 sm:w-48 sm:h-48 object-contain">
                            </div>
                        @else
                            <div class="w-44 h-44 sm:w-48 sm:h-48 flex items-center justify-center text-xs text-rose-500 border border-rose-200 rounded-lg">
                                Gagal menghasilkan QR Code
                            </div>
                        @endif
                    </div>

                    {{-- Scanning Prompt --}}
                    <div class="rounded-lg bg-slate-50 border border-slate-100 py-1.5 px-2">
                        <div class="text-[11px] font-bold text-slate-900 flex items-center justify-center gap-1.5">
                            <i class="bi bi-phone text-slate-600"></i>
                            <span>Pindai untuk P2H Check-out &amp; Check-in</span>
                        </div>
                    </div>

                    {{-- UUID Monospace Footer --}}
                    <div class="text-[9px] font-mono text-slate-400 truncate">
                        UUID: {{ $vehicle->id }}
                    </div>
                </div>

                {{-- Action Controls (Print, Download PNG, Copy ID) --}}
                <div class="space-y-2 pt-1">
                    {{-- Primary Print Button --}}
                    <button type="button"
                        @click="printVehicleQrSticker('{{ $qrCodeBase64 }}', '{{ addslashes($vehicle->plate_number) }}', '{{ addslashes(trim($vehicle->brand . ' ' . $vehicle->model)) }}', '{{ $vehicle->year }}', '{{ addslashes($vehicle->id) }}')"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-2xs hover:bg-slate-800 transition active:scale-[0.98] cursor-pointer">
                        <i class="bi bi-printer text-sm"></i>
                        <span>Cetak Stiker Fisik</span>
                    </button>

                    {{-- Secondary Action Buttons --}}
                    <div class="grid grid-cols-2 gap-2">
                        @if ($qrCodeBase64)
                            <a href="data:image/png;base64,{{ $qrCodeBase64 }}" download="QR-Armada-{{ str_replace(' ', '-', $vehicle->plate_number) }}.png"
                                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs cursor-pointer">
                                <i class="bi bi-download text-slate-500"></i>
                                <span>Unduh PNG</span>
                            </a>
                        @endif

                        <button type="button"
                            @click="navigator.clipboard.writeText('{{ $vehicle->id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs cursor-pointer">
                            <i class="bi" :class="copied ? 'bi-check2 text-emerald-600' : 'bi-clipboard text-slate-500'"></i>
                            <span x-text="copied ? 'Tersalin!' : 'Salin UUID'"></span>
                        </button>
                    </div>
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
                    <button type="button" wire:click="closeDocModal" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <form wire:submit.prevent="saveDocument" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Dokumen <span class="text-rose-500">*</span></label>
                        <select wire:model.defer="doc_type"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-500 focus:outline-none">
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
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jatuh Tempo <span class="text-rose-500">*</span></label>
                            <input type="date" wire:model.defer="expired_date"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-500 focus:outline-none">
                            @error('expired_date')
                                <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Diperbarui</label>
                            <input type="date" wire:model.defer="last_renewed_date"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Unggah Scan / Foto Fisik</label>
                        <input type="file" wire:model="attachment" accept="image/*,application/pdf"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan</label>
                        <textarea wire:model.defer="notes" rows="2" placeholder="Catatan instansi / keterangan perpanjangan..."
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-slate-500 focus:outline-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" wire:click="closeDocModal"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="rounded-xl bg-slate-900 px-5 py-2 text-xs font-bold text-white hover:bg-slate-800 disabled:opacity-60 cursor-pointer">
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
                        <i class="bi bi-camera text-slate-900 text-lg"></i>
                        <h3 class="text-sm font-bold text-slate-900">{{ __('fleet.show.photo_modal_title') }}</h3>
                    </div>
                    <button type="button" wire:click="closePhotoModal" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <form wire:submit.prevent="saveVehiclePhoto" class="space-y-4">
                    {{-- Current or New Preview --}}
                    <div class="flex flex-col items-center justify-center text-center p-3 rounded-2xl bg-slate-50 border border-slate-200">
                        @if ($new_photo)
                            <img src="{{ $new_photo->temporaryUrl() }}" class="h-32 w-32 object-cover rounded-2xl border border-slate-200 shadow-2xs mb-2">
                            <span class="text-xs font-semibold text-slate-800">Preview Baru</span>
                        @elseif ($vehicle->image_path)
                            <img src="{{ asset('storage/' . $vehicle->image_path) }}" class="h-32 w-32 object-cover rounded-2xl border border-slate-200 shadow-2xs mb-2">
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
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                        <div wire:loading wire:target="new_photo" class="text-xs text-slate-600 font-medium mt-1">
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
                                    class="text-xs font-bold text-rose-600 hover:text-rose-800 cursor-pointer">
                                    <i class="bi bi-trash mr-1"></i> {{ __('fleet.show.photo_delete') }}
                                </button>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="closePhotoModal"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                                {{ __('fleet.common.cancel') }}
                            </button>
                            <button type="submit" wire:loading.attr="disabled"
                                class="rounded-xl bg-slate-900 px-5 py-2 text-xs font-bold text-white hover:bg-slate-800 disabled:opacity-60 cursor-pointer">
                                {{ __('fleet.show.photo_save') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- PRINT STYLESHEET (Fallback for Native Browser Print) --}}
    <style>
        @media print {
            @page {
                size: 80mm 100mm;
                margin: 0;
            }
            html, body {
                height: 100% !important;
                max-height: 100% !important;
                overflow: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
            body * {
                visibility: hidden !important;
            }
            #printable-qr-sticker, #printable-qr-sticker * {
                visibility: visible !important;
            }
            #printable-qr-sticker {
                position: absolute !important;
                left: 50% !important;
                top: 50% !important;
                transform: translate(-50%, -50%) !important;
                width: 72mm !important;
                border: 2px solid #0f172a !important;
                border-radius: 12px !important;
                padding: 14px 12px !important;
                box-shadow: none !important;
                background: #ffffff !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>

    <script>
        function printVehicleQrSticker(qrBase64, plateNumber, model, year, id) {
            if (!qrBase64) {
                alert('Data QR Code belum tersedia.');
                return;
            }

            let frame = document.getElementById('qr-print-frame');
            if (!frame) {
                frame = document.createElement('iframe');
                frame.id = 'qr-print-frame';
                frame.style.position = 'fixed';
                frame.style.right = '0';
                frame.style.bottom = '0';
                frame.style.width = '0';
                frame.style.height = '0';
                frame.style.border = '0';
                document.body.appendChild(frame);
            }

            const modelDisplay = (model ? model : '') + (year ? ' (' + year + ')' : '');
            const frameDoc = frame.contentWindow.document;
            frameDoc.open();
            frameDoc.write([
                '\x3C!DOCTYPE html\x3E',
                '\x3Chtml\x3E',
                '\x3Chead\x3E',
                '  \x3Cmeta charset="utf-8"\x3E',
                '  \x3Ctitle\x3EStiker QR - ' + plateNumber + '\x3C/title\x3E',
                '  \x3Cstyle\x3E',
                '    @page { size: 80mm 100mm; margin: 0; }',
                '    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }',
                '    html, body { width: 80mm; height: 100mm; max-height: 100mm; overflow: hidden; background: #ffffff; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }',
                '    .sticker-container { width: 80mm; height: 100mm; display: flex; align-items: center; justify-content: center; padding: 3mm; page-break-inside: avoid; break-inside: avoid; }',
                '    .sticker-box { width: 72mm; border: 2px solid #020617; border-radius: 12px; padding: 8px 6px; text-align: center; background: #ffffff; page-break-inside: avoid; break-inside: avoid; }',
                '    .brand-title { font-size: 11px; font-weight: 900; letter-spacing: 0.1em; color: #020617; text-transform: uppercase; }',
                '    .brand-sub { font-size: 8px; font-weight: 700; color: #64748b; letter-spacing: 0.05em; text-transform: uppercase; margin-top: 1px; padding-bottom: 4px; border-bottom: 1px solid #e2e8f0; }',
                '    .plate-badge { display: inline-flex; align-items: center; justify-content: center; gap: 6px; background: #020617; color: #f8fafc; padding: 4px 12px; border-radius: 7px; margin: 4px 0 3px; }',
                '    .plate-text { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 16px; font-weight: 900; letter-spacing: 0.12em; }',
                '    .rivet { width: 4px; height: 4px; border-radius: 50%; background: #94a3b8; opacity: 0.7; }',
                '    .vehicle-model { font-size: 10px; font-weight: 700; color: #1e293b; margin-bottom: 3px; }',
                '    .qr-wrap { display: flex; justify-content: center; margin: 2px 0; }',
                '    .qr-img { width: 42mm; height: 42mm; object-fit: contain; }',
                '    .prompt-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 3px 5px; font-size: 8.5px; font-weight: 700; color: #0f172a; margin-top: 3px; }',
                '    .uuid-text { font-family: ui-monospace, monospace; font-size: 7.5px; color: #94a3b8; margin-top: 3px; word-break: break-all; }',
                '  \x3C/style\x3E',
                '\x3C/head\x3E',
                '\x3Cbody\x3E',
                '  \x3Cdiv class="sticker-container"\x3E',
                '    \x3Cdiv class="sticker-box"\x3E',
                '      \x3Cdiv class="brand-title"\x3EPT DAIJO INDUSTRIAL\x3C/div\x3E',
                '      \x3Cdiv class="brand-sub"\x3EOPERATIONAL FLEET &bull; P2H SYSTEM\x3C/div\x3E',
                '      \x3Cdiv class="plate-badge"\x3E',
                '        \x3Cspan class="rivet"\x3E\x3C/span\x3E',
                '        \x3Cspan class="plate-text"\x3E' + plateNumber + '\x3C/span\x3E',
                '        \x3Cspan class="rivet"\x3E\x3C/span\x3E',
                '      \x3C/div\x3E',
                '      \x3Cdiv class="vehicle-model"\x3E' + modelDisplay + '\x3C/div\x3E',
                '      \x3Cdiv class="qr-wrap"\x3E',
                '        \x3Cimg src="data:image/png;base64,' + qrBase64 + '" class="qr-img" alt="QR Code"\x3E',
                '      \x3C/div\x3E',
                '      \x3Cdiv class="prompt-box"\x3E📱 Pindai untuk P2H Check-out &amp; Check-in\x3C/div\x3E',
                '      \x3Cdiv class="uuid-text"\x3EUUID: ' + id + '\x3C/div\x3E',
                '    \x3C/div\x3E',
                '  \x3C/div\x3E',
                '\x3C/body\x3E',
                '\x3C/html\x3E'
            ].join('\n'));
            frameDoc.close();

            setTimeout(() => {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            }, 250);
        }

        window.addEventListener('print-qr-sticker', (event) => {
            const qr = event.detail?.qrBase64 || '{{ $qrCodeBase64 }}';
            printVehicleQrSticker(qr, '{{ addslashes($vehicle->plate_number) }}', '{{ addslashes(trim($vehicle->brand . ' ' . $vehicle->model)) }}', '{{ $vehicle->year }}', '{{ addslashes($vehicle->id) }}');
        });
    </script>
</div>
