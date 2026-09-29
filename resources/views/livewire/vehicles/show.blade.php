<div class="max-w-6xl mx-auto px-3 md:px-6 py-5 space-y-6">
    {{-- Top Navigation & Breadcrumb --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('vehicles.index') }}"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
            <i class="bi bi-arrow-left text-sm"></i>
            Kembali ke Fleet Command Center
        </a>

        <div class="flex items-center gap-2">
            <span class="font-mono text-xs text-slate-400">VIN: {{ $vehicle->vin ?: 'N/A' }}</span>
            <span class="text-slate-300">•</span>
            <a href="{{ route('vehicles.edit', $vehicle) }}"
                class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                <i class="bi bi-pencil mr-1"></i> Edit Armada
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
    @endphp

    {{-- HERO COCKPIT HEADER CARD --}}
    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm overflow-hidden relative">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            {{-- Vehicle Brand & Category --}}
            <div class="flex items-start gap-4">
                <div class="h-16 w-16 rounded-2xl flex items-center justify-center text-3xl shadow-xs shrink-0
                    {{ $vehicle->category === 'commercial_truck' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                    @if ($vehicle->category === 'commercial_truck')
                        <i class="bi bi-truck"></i>
                    @else
                        <i class="bi bi-car-front"></i>
                    @endif
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-black font-mono tracking-tight text-slate-900">
                            {{ $vehicle->plate_number }}
                        </h1>
                        <span class="rounded-lg bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700">
                            {{ $vehicle->category_label }}
                        </span>
                        <span class="rounded-lg bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700">
                            <i class="bi bi-fuel-pump mr-1"></i>{{ $vehicle->fuel_type_label }}
                        </span>
                    </div>

                    <div class="mt-1 flex flex-wrap items-center gap-x-3 text-xs text-slate-500 font-medium">
                        <span>{{ trim($vehicle->brand . ' ' . $vehicle->model) }} {{ $vehicle->year ? "({$vehicle->year})" : '' }}</span>
                        <span>•</span>
                        <span>Driver Penanggung Jawab: <strong class="text-slate-800">{{ $vehicle->driver_name ?: 'Belum ditentukan' }}</strong></span>
                    </div>
                </div>
            </div>

            {{-- Operational Action Cockpit --}}
            <div class="flex flex-wrap items-center gap-3">
                @if (!$vehicle->is_sold)
                    @if ($isOut)
                        <a href="{{ route('vehicles.inspect', ['vehicle' => $vehicle, 'type' => 'check_in']) }}"
                            class="inline-flex items-center gap-2 rounded-2xl bg-amber-500 px-5 py-3 text-xs font-black text-white shadow-md hover:bg-amber-600 transition active:scale-95 animate-pulse">
                            <i class="bi bi-box-arrow-in-down text-base"></i>
                            Check-in Armada (Kembali ke Pool)
                        </a>
                    @else
                        <a href="{{ route('vehicles.inspect', ['vehicle' => $vehicle, 'type' => 'check_out']) }}"
                            class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-xs font-black text-white shadow-md hover:bg-emerald-700 transition active:scale-95">
                            <i class="bi bi-box-arrow-up-right text-base"></i>
                            P2H Check-out (Izin Jalan)
                        </a>
                    @endif

                    <a href="{{ route('services.create', $vehicle) }}"
                        class="inline-flex items-center gap-1.5 rounded-2xl bg-indigo-600 px-4 py-3 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 transition active:scale-95">
                        <i class="bi bi-wrench"></i> Tambah Servis
                    </a>
                @endif
            </div>
        </div>

        {{-- Live Operational Radar Banner (If Out on Trip) --}}
        @if ($isOut && $vehicle->activeCheckOut)
            <div class="mt-5 rounded-2xl bg-gradient-to-r from-amber-50 via-orange-50 to-amber-50 border border-amber-200 p-4 text-xs text-amber-950 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="h-3 w-3 rounded-full bg-amber-500 animate-ping shrink-0"></span>
                    <div>
                        <div class="font-bold text-amber-900">
                            Armada Sedang Aktif di Jalan (Trip Sedang Berjalan)
                        </div>
                        <div class="text-[11px] text-amber-800 mt-0.5">
                            Berangkat sejak <strong>{{ $vehicle->activeCheckOut->created_at->isoFormat('DD MMM YYYY, HH:mm') }} WIB</strong> 
                            • Driver: <strong>{{ $vehicle->activeCheckOut->driver_name }}</strong> 
                            • KM Awal: <strong>{{ number_format($vehicle->activeCheckOut->odometer) }} km</strong>
                            @if ($vehicle->activeCheckOut->trip_purpose)
                                — <em>"{{ $vehicle->activeCheckOut->trip_purpose }}"</em>
                            @endif
                        </div>
                    </div>
                </div>

                <a href="{{ route('vehicles.inspect', ['vehicle' => $vehicle, 'type' => 'check_in']) }}"
                    class="rounded-xl bg-amber-600 px-3.5 py-1.5 font-bold text-white hover:bg-amber-700 shrink-0 text-center">
                    Catat Kepulangan →
                </a>
            </div>
        @endif
    </div>

    {{-- 3 Cockpit Gauges Cards --}}
    <div class="grid gap-4 sm:grid-cols-3">
        {{-- Gauge 1: Odometer --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Jarak Tempuh (Odometer)</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black font-mono text-slate-900">{{ number_format($vehicle->odometer) }}</span>
                <span class="text-xs font-semibold text-slate-400">KM</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Terakhir diperbarui dari log pemeriksaan</p>
        </div>

        {{-- Gauge 2: Proyeksi Servis Berikutnya --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Proyeksi Servis Berkala</span>
                <span class="text-xs font-bold text-indigo-600">{{ $serviceProgressPercent }}%</span>
            </div>
            <div class="mt-2.5 h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full {{ $serviceProgressPercent >= 90 ? 'bg-rose-500' : ($serviceProgressPercent >= 70 ? 'bg-amber-500' : 'bg-indigo-600') }}"
                    style="width: {{ $serviceProgressPercent }}%"></div>
            </div>
            <p class="text-[11px] text-slate-500 mt-2">
                {{ number_format($kmSinceLastService) }} km sejak servis terakhir (Target: 10.000 km)
            </p>
        </div>

        {{-- Gauge 3: Legalitas Ringkasan --}}
        @php
            $hasExpired = $documents->where('status', 'expired')->count();
            $hasWarning = $documents->whereIn('status', ['critical', 'warning'])->count();
        @endphp
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Status KIR &amp; STNK</span>
            <div class="mt-2 flex items-center gap-2">
                @if ($hasExpired > 0)
                    <span class="h-3 w-3 rounded-full bg-rose-500"></span>
                    <span class="text-sm font-bold text-rose-700">{{ $hasExpired }} Dokumen Expired!</span>
                @elseif ($hasWarning > 0)
                    <span class="h-3 w-3 rounded-full bg-amber-500 animate-pulse"></span>
                    <span class="text-sm font-bold text-amber-700">{{ $hasWarning }} Dokumen Segera Habis</span>
                @else
                    <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                    <span class="text-sm font-bold text-emerald-700">Semua Dokumen Legal Aman</span>
                @endif
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Uji berkala &amp; pajak tahunan terpantau</p>
        </div>
    </div>

    {{-- MODERN GLASSMORPHIC PILL TABS --}}
    <div class="rounded-2xl bg-slate-100 p-1 border border-slate-200/80 inline-flex flex-wrap gap-1">
        <button type="button" wire:click="setTab('inspections')"
            class="rounded-xl px-4 py-2 text-xs font-bold transition
            {{ $tab === 'inspections' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
            <i class="bi bi-clipboard-check mr-1.5"></i>
            Logbook Pemeriksaan (P2H)
            <span class="ml-1 rounded-full bg-indigo-50 text-indigo-700 px-1.5 py-0.2 text-[10px]">
                {{ $inspections->total() }}
            </span>
        </button>

        <button type="button" wire:click="setTab('documents')"
            class="rounded-xl px-4 py-2 text-xs font-bold transition
            {{ $tab === 'documents' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
            <i class="bi bi-file-earmark-text mr-1.5"></i>
            Legalitas Dokumen (KIR &amp; STNK)
            @if ($hasExpired + $hasWarning > 0)
                <span class="ml-1 rounded-full bg-rose-100 text-rose-700 px-1.5 py-0.2 text-[10px] font-bold">
                    {{ $hasExpired + $hasWarning }}
                </span>
            @endif
        </button>

        <button type="button" wire:click="setTab('services')"
            class="rounded-xl px-4 py-2 text-xs font-bold transition
            {{ $tab === 'services' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
            <i class="bi bi-wrench mr-1.5"></i>
            Riwayat Servis &amp; Biaya
            <span class="ml-1 rounded-full bg-slate-200 text-slate-700 px-1.5 py-0.2 text-[10px]">
                {{ $records->total() }}
            </span>
        </button>
    </div>

    {{-- TAB 1: LOGBOOK PEMERIKSAAN HARIAN (P2H TIMELINE) --}}
    @if ($tab === 'inspections')
        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Riwayat Kelayakan &amp; Perjalanan Armada (P2H)</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Catatan check-out keberangkatan dan check-in kepulangan.</p>
                </div>

                <div class="flex items-center gap-2">
                    <select wire:model.live="inspectionType"
                        class="rounded-xl border border-slate-200 bg-slate-50 py-1.5 px-3 text-xs text-slate-700 focus:outline-none">
                        <option value="all">Semua Inspeksi</option>
                        <option value="check_out">Check-out (Berangkat)</option>
                        <option value="check_in">Check-in (Kembali)</option>
                    </select>
                </div>
            </div>

            @if ($inspections->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-200 p-10 text-center">
                    <i class="bi bi-clipboard-x text-3xl text-slate-300"></i>
                    <h3 class="text-xs font-bold text-slate-700 mt-2">Belum ada catatan inspeksi P2H</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Pemeriksaan akan tercatat otomatis saat supir/satpam melakukan check-out armada.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($inspections as $ins)
                        @php $isOutTrip = $ins->inspection_type === 'check_out'; @endphp
                        <div class="rounded-2xl border p-4 transition
                            {{ $ins->severity === 'critical_grounded' ? 'border-rose-200 bg-rose-50/40' : ($ins->severity === 'minor' ? 'border-amber-200 bg-amber-50/30' : 'border-slate-200/80 bg-white hover:border-slate-300') }}">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                {{-- Left Info --}}
                                <div class="flex items-start gap-3">
                                    <span class="h-10 w-10 rounded-xl flex items-center justify-center text-lg shrink-0
                                        {{ $isOutTrip ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                                        <i class="bi {{ $isOutTrip ? 'bi-box-arrow-up-right' : 'bi-box-arrow-in-down' }}"></i>
                                    </span>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-900">
                                                {{ $isOutTrip ? 'Check-out (Berangkat)' : 'Check-in (Kembali)' }}
                                            </span>
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold {{ $ins->severity_badge_classes }}">
                                                {{ $ins->severity_label }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            <span>Driver: <strong class="text-slate-800">{{ $ins->driver_name }}</strong></span>
                                            <span class="mx-1">•</span>
                                            <span>Pemeriksa: {{ $ins->inspector?->name ?? 'Admin' }}</span>
                                            <span class="mx-1">•</span>
                                            <span>{{ $ins->created_at->isoFormat('DD MMM YYYY, HH:mm') }} WIB</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Right Metrics --}}
                                <div class="flex items-center gap-3">
                                    <div class="text-right">
                                        <div class="font-mono text-sm font-bold text-slate-900">{{ number_format($ins->odometer) }} km</div>
                                        <div class="text-[10px] text-slate-400">BBM: {{ $ins->fuel_percentage }}%</div>
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
                                            <span class="text-slate-400 mr-1">Keperluan:</span> <em>"{{ $ins->trip_purpose }}"</em>
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
                        </div>
                    @endforeach
                </div>

                <div>{{ $inspections->links() }}</div>
            @endif
        </div>
    @endif

    {{-- TAB 2: LEGALITAS & DOKUMEN (KIR & STNK CARDS) --}}
    @if ($tab === 'documents')
        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Kepatuhan Uji Berkala &amp; Pajak Armada</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Pantau siklus jatuh tempo Uji KIR 6 bulanan dan Pajak STNK 1th / 5th.</p>
                </div>

                @if ($canManage)
                    <button type="button" wire:click="openDocModal"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition">
                        <i class="bi bi-plus-lg mr-1.5"></i> Perbarui / Tambah Dokumen
                    </button>
                @endif
            </div>

            {{-- 3 Key Document Cards --}}
            @php
                $kirDoc = $documents->where('document_type', 'kir')->sortByDesc('expired_date')->first();
                $stnk1 = $documents->where('document_type', 'stnk_annual')->sortByDesc('expired_date')->first();
                $stnk5 = $documents->where('document_type', 'stnk_five_year')->sortByDesc('expired_date')->first();
            @endphp
            <div class="grid gap-4 sm:grid-cols-3">
                {{-- KIR Card --}}
                <div class="rounded-2xl border p-4 transition
                    {{ $vehicle->requires_kir ? ($kirDoc ? ($kirDoc->status === 'expired' ? 'border-rose-300 bg-rose-50/50' : 'border-slate-200 bg-slate-50/60') : 'border-amber-300 bg-amber-50/40') : 'border-slate-200 bg-slate-50/30 opacity-70' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Uji Berkala (KIR)</span>
                        <span class="rounded-md {{ $vehicle->requires_kir ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600' }} px-2 py-0.5 text-[10px] font-bold">
                            {{ $vehicle->requires_kir ? 'Wajib Niaga' : 'Opsional' }}
                        </span>
                    </div>

                    <div class="mt-3">
                        @if ($kirDoc)
                            <div class="text-lg font-black text-slate-900">{{ $kirDoc->expired_date->isoFormat('DD MMMM YYYY') }}</div>
                            <div class="mt-1.5">
                                <span class="rounded-lg px-2.5 py-1 text-xs font-bold {{ $kirDoc->status_badge_classes }}">
                                    {{ $kirDoc->status_label }}
                                </span>
                            </div>
                            <div class="font-mono text-[11px] text-slate-400 mt-2">No: {{ $kirDoc->document_number ?: '—' }}</div>
                        @else
                            <p class="text-xs text-slate-400 italic">Belum ada data KIR</p>
                            @if ($vehicle->requires_kir && $canManage)
                                <button type="button" wire:click="openDocModal('kir')" class="mt-2 text-xs font-bold text-indigo-600 hover:underline">
                                    + Input Dokumen KIR
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- STNK 1th Card --}}
                <div class="rounded-2xl border p-4 transition
                    {{ $stnk1 ? ($stnk1->status === 'expired' ? 'border-rose-300 bg-rose-50/50' : 'border-slate-200 bg-slate-50/60') : 'border-amber-300 bg-amber-50/40' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Pajak STNK 1 Tahunan</span>
                        <span class="rounded-md bg-emerald-100 text-emerald-700 px-2 py-0.5 text-[10px] font-bold">Wajib Tahunan</span>
                    </div>

                    <div class="mt-3">
                        @if ($stnk1)
                            <div class="text-lg font-black text-slate-900">{{ $stnk1->expired_date->isoFormat('DD MMMM YYYY') }}</div>
                            <div class="mt-1.5">
                                <span class="rounded-lg px-2.5 py-1 text-xs font-bold {{ $stnk1->status_badge_classes }}">
                                    {{ $stnk1->status_label }}
                                </span>
                            </div>
                            <div class="font-mono text-[11px] text-slate-400 mt-2">No: {{ $stnk1->document_number ?: '—' }}</div>
                        @else
                            <p class="text-xs text-slate-400 italic">Belum ada data STNK 1th</p>
                            @if ($canManage)
                                <button type="button" wire:click="openDocModal('stnk_annual')" class="mt-2 text-xs font-bold text-indigo-600 hover:underline">
                                    + Input Pajak 1 Th
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- STNK 5th Card --}}
                <div class="rounded-2xl border p-4 transition
                    {{ $stnk5 ? ($stnk5->status === 'expired' ? 'border-rose-300 bg-rose-50/50' : 'border-slate-200 bg-slate-50/60') : 'border-slate-200 bg-slate-50/40' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">STNK 5 Th &amp; Ganti Plat</span>
                        <span class="rounded-md bg-blue-100 text-blue-700 px-2 py-0.5 text-[10px] font-bold">5 Tahun</span>
                    </div>

                    <div class="mt-3">
                        @if ($stnk5)
                            <div class="text-lg font-black text-slate-900">{{ $stnk5->expired_date->isoFormat('DD MMMM YYYY') }}</div>
                            <div class="mt-1.5">
                                <span class="rounded-lg px-2.5 py-1 text-xs font-bold {{ $stnk5->status_badge_classes }}">
                                    {{ $stnk5->status_label }}
                                </span>
                            </div>
                            <div class="font-mono text-[11px] text-slate-400 mt-2">No: {{ $stnk5->document_number ?: '—' }}</div>
                        @else
                            <p class="text-xs text-slate-400 italic">Belum ada data STNK 5th</p>
                            @if ($canManage)
                                <button type="button" wire:click="openDocModal('stnk_five_year')" class="mt-2 text-xs font-bold text-indigo-600 hover:underline">
                                    + Input STNK 5 Th
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- Table All Historical Documents --}}
            <div class="space-y-2 pt-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Berkas Legalitas Terunggah</h3>

                @if ($documents->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-xs text-slate-400">
                        Belum ada arsip dokumen legalitas yang disimpan.
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
                                                <a href="{{ asset('storage/' . $doc->attachment_path) }}" target="_blank"
                                                    class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-medium hover:underline">
                                                    <i class="bi bi-file-earmark-arrow-down mr-1"></i> Buka File
                                                </a>
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

    {{-- TAB 3: RIWAYAT SERVIS BENGKEL & ANALITIK BIAYA --}}
    @if ($tab === 'services')
        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Riwayat Pemeliharaan &amp; Biaya Bengkel</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Catatan invoice servis berkala dan perbaikan kerusakan.</p>
                </div>

                @if (!$vehicle->is_sold)
                    <a href="{{ route('services.create', $vehicle) }}"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition">
                        <i class="bi bi-plus-lg mr-1.5"></i> Catat Servis Baru
                    </a>
                @endif
            </div>

            {{-- 2 Cost Summary Chips --}}
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Biaya YTD ({{ now()->year }})</span>
                    <div class="text-xl font-black text-slate-900 mt-1">Rp {{ number_format($ytdCost, 0, ',', '.') }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Biaya Seumur Hidup (Lifetime)</span>
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
                                    <td class="px-4 py-3.5 font-bold text-slate-900 whitespace-nowrap">{{ $rec->service_date->isoFormat('DD MMM YYYY') }}</td>
                                    <td class="px-4 py-3.5">{{ $rec->workshop ?: 'Bengkel Internal' }}</td>
                                    <td class="px-4 py-3.5 font-semibold whitespace-nowrap">{{ number_format($rec->odometer) }} km</td>
                                    <td class="px-4 py-3.5">
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
                                    <td class="px-4 py-3.5 text-right font-bold text-slate-900 whitespace-nowrap">
                                        Rp {{ number_format($rec->total_cost, 0, ',', '.') }}
                                    </td>
                                    @if ($canManage)
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
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

    {{-- MODAL TAMBAH / PERPANJANG DOKUMEN --}}
    @if ($showDocModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4 overflow-y-auto">
            <div class="relative w-full max-w-lg rounded-3xl bg-white shadow-2xl border border-slate-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900">Input / Perpanjang Dokumen Legalitas</h3>
                    <button type="button" wire:click="closeDocModal" class="text-slate-400 hover:text-slate-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <form wire:submit.prevent="saveDocument" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Dokumen <span class="text-rose-500">*</span></label>
                        <select wire:model.defer="doc_type"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                            <option value="kir">Uji Berkala (KIR) — Khusus Mobil Gede/Niaga</option>
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
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Jatuh Tempo <span class="text-rose-500">*</span></label>
                            <input type="date" wire:model.defer="expired_date"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                            @error('expired_date')
                                <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Diperpanjang</label>
                            <input type="date" wire:model.defer="last_renewed_date"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Scan / Foto Berkas Fisik</label>
                        <input type="file" wire:model="attachment" accept="image/*,application/pdf"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan</label>
                        <textarea wire:model.defer="notes" rows="2" placeholder="Catatan instansi perpanjangan..."
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
</div>
