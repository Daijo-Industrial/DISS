<div class="max-w-7xl mx-auto px-3 md:px-6 py-5 space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold ring-1 ring-inset ring-indigo-200/60 mb-1">
                <i class="bi bi-shield-check"></i>
                <span>Operations &amp; Fleet Management</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Fleet Command Center
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Monitoring armada real-time, kesiapan pemeriksaan harian (P2H), dan pengawasan legalitas dokumen (KIR &amp; STNK).
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('vehicles.create') }}"
                class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 transition active:scale-[0.98]">
                <i class="bi bi-plus-lg mr-1.5 text-sm"></i>
                Registrasi Armada Baru
            </a>
        </div>
    </div>

    {{-- Top KPI Metrics Grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 md:gap-4">
        {{-- Card 1: Total Armada --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Armada</span>
                <span class="h-9 w-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="bi bi-truck text-base"></i>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900">{{ $metrics['total'] }}</span>
                <span class="text-xs text-slate-400 font-medium">unit kendaraan</span>
            </div>
        </div>

        {{-- Card 2: On-Trip --}}
        <div class="rounded-2xl border border-amber-200/80 bg-gradient-to-br from-white to-amber-50/40 p-4 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-amber-900 uppercase tracking-wider">Sedang di Jalan</span>
                <span class="h-9 w-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                    <i class="bi bi-signpost-2 text-base"></i>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-amber-700">{{ $metrics['on_trip'] }}</span>
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-800">
                    <span class="h-2 w-2 rounded-full bg-amber-500 animate-ping"></span>
                    On-Trip
                </span>
            </div>
        </div>

        {{-- Card 3: Standby di Pool --}}
        <div class="rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-white to-emerald-50/40 p-4 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-emerald-900 uppercase tracking-wider">Standby di Pool</span>
                <span class="h-9 w-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                    <i class="bi bi-house-check text-base"></i>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-emerald-700">{{ $metrics['in_pool'] }}</span>
                <span class="text-xs text-emerald-600 font-medium">siap ditugaskan</span>
            </div>
        </div>

        {{-- Card 4: Dokumen & Servis Alert --}}
        <div class="rounded-2xl border {{ $metrics['alerts'] > 0 ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-white' }} p-4 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold {{ $metrics['alerts'] > 0 ? 'text-rose-900' : 'text-slate-500' }} uppercase tracking-wider">
                    Perhatian KIR/STNK
                </span>
                <span class="h-9 w-9 rounded-xl {{ $metrics['alerts'] > 0 ? 'bg-rose-100 text-rose-700 animate-bounce' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center">
                    <i class="bi bi-shield-exclamation text-base"></i>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold {{ $metrics['alerts'] > 0 ? 'text-rose-700' : 'text-slate-700' }}">{{ $metrics['alerts'] }}</span>
                <span class="text-xs {{ $metrics['alerts'] > 0 ? 'text-rose-600 font-semibold' : 'text-slate-400' }}">
                    {{ $metrics['alerts'] > 0 ? 'butuh perpanjangan' : 'semua aman' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Compliance Expiry Alert Banner --}}
    @if ($fullFeature && $complianceAlerts->isNotEmpty())
        <div class="rounded-2xl border border-amber-300 bg-gradient-to-r from-amber-50 via-orange-50/50 to-amber-50 p-4 shadow-xs">
            <div class="flex items-start gap-3.5">
                <div class="h-9 w-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                    <i class="bi bi-bell-fill text-base"></i>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-amber-950">
                            Peringatan Jatuh Tempo Uji Berkala (KIR) &amp; Pajak STNK
                        </h3>
                        <span class="text-[11px] font-bold text-amber-800 bg-amber-200/70 px-2 py-0.5 rounded-full">
                            {{ $complianceAlerts->count() }} Dokumen Mendesak
                        </span>
                    </div>
                    <p class="text-xs text-amber-900 mt-0.5">
                        Dokumen di bawah ini memerlukan tindak lanjut perpanjangan sebelum batas waktu jatuh tempo:
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($complianceAlerts as $alertDoc)
                            <a href="{{ route('vehicles.show', ['vehicle' => $alertDoc->vehicle_id, 'tab' => 'documents']) }}"
                                class="inline-flex items-center gap-2 rounded-xl border bg-white px-3 py-1.5 text-xs shadow-xs hover:bg-slate-50 transition {{ $alertDoc->status === 'expired' ? 'border-rose-300 text-rose-900' : 'border-amber-300 text-amber-900' }}">
                                <span class="font-mono font-bold">{{ $alertDoc->vehicle?->plate_number }}</span>
                                <span class="text-slate-300">|</span>
                                <span class="font-medium">{{ $alertDoc->type_label }}</span>
                                <span class="rounded-md px-1.5 py-0.5 text-[10px] font-bold {{ $alertDoc->status_badge_classes }}">
                                    {{ $alertDoc->status_label }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Filter & Control Bar --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        {{-- Search & Category Filter --}}
        <div class="flex flex-wrap items-center gap-2.5 flex-1">
            <div class="relative w-full sm:w-64">
                <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                <input type="text" placeholder="Cari plat, merk, driver..."
                    wire:model.live.debounce.300ms="q"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-1.5 pl-8 pr-8 text-xs text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                @if ($q !== '')
                    <button type="button" wire:click="$set('q','')" class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600">
                        <i class="bi bi-x-circle text-xs"></i>
                    </button>
                @endif
            </div>

            {{-- Category Filter --}}
            <div class="w-44">
                <select wire:model.live="category"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-1.5 px-2.5 text-xs text-slate-700 focus:bg-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                    <option value="all">Semua Kategori</option>
                    <option value="passenger">Mobil Penumpang / Kantor</option>
                    <option value="commercial_truck">Truk / Mobil Gede (Niaga)</option>
                    <option value="pickup">Pick-up / Mobil Bak</option>
                    <option value="other">Lainnya</option>
                </select>
            </div>

            {{-- Status Filter Pills --}}
            @php use App\Enums\VehicleStatus; @endphp
            @if ($fullFeature)
                <div class="hidden sm:flex items-center gap-1.5 overflow-x-auto">
                    <button type="button" wire:click="$set('status','all')"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition
                        {{ $status === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Semua
                    </button>
                    @foreach (VehicleStatus::cases() as $st)
                        <button type="button" wire:click="$set('status','{{ $st->value }}')"
                            class="rounded-xl px-2.5 py-1.5 text-xs font-medium transition
                            {{ $status === $st->value ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            {{ $st->label() }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Right Controls: Per Page & View Mode Switcher --}}
        <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-100">
            <div class="flex items-center gap-1 text-xs text-slate-500">
                <span>Per hal:</span>
                <select wire:model.live="perPage"
                    class="rounded-lg border border-slate-200 bg-slate-50 py-1 px-1.5 text-xs text-slate-700 focus:outline-none">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
            </div>

            {{-- View Mode Toggle --}}
            <div class="inline-flex rounded-xl bg-slate-100 p-0.5 border border-slate-200">
                <button type="button" wire:click="setViewMode('grid')"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold transition
                    {{ $viewMode === 'grid' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                    <i class="bi bi-grid-fill text-xs"></i>
                    <span class="hidden sm:inline">Galeri</span>
                </button>
                <button type="button" wire:click="setViewMode('table')"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold transition
                    {{ $viewMode === 'table' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                    <i class="bi bi-table text-xs"></i>
                    <span class="hidden sm:inline">Tabel</span>
                </button>
            </div>
        </div>
    </div>

    {{-- VIEW MODE 1: MODERN GALLERY CARDS --}}
    @if ($viewMode === 'grid')
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($vehicles as $v)
                @php
                    $isOut = $v->is_out_on_trip;
                    $lastService = $v->latestService;
                    $kirDoc = $v->documents->where('document_type', 'kir')->sortByDesc('expired_date')->first();
                    $stnkDoc = $v->documents->where('document_type', 'stnk_annual')->sortByDesc('expired_date')->first();
                @endphp
                <div wire:key="veh-card-{{ $v->id }}"
                    class="group relative flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-xs hover:-translate-y-0.5 hover:shadow-md hover:border-indigo-300 transition-all duration-200">
                    
                    {{-- Top Card Header --}}
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <span class="h-10 w-10 rounded-xl flex items-center justify-center text-lg {{ $v->category === 'commercial_truck' ? 'bg-amber-100 text-amber-700' : ($v->category === 'motorcycle' ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700') }}">
                                    @if ($v->category === 'commercial_truck')
                                        <i class="bi bi-truck"></i>
                                    @elseif ($v->category === 'motorcycle')
                                        <i class="bi bi-bicycle"></i>
                                    @else
                                        <i class="bi bi-car-front"></i>
                                    @endif
                                </span>
                                <div>
                                    <div class="font-mono text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition">
                                        {{ $v->plate_number }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-medium">
                                        {{ trim($v->brand . ' ' . $v->model) ?: 'Kendaraan' }} {{ $v->year ? "({$v->year})" : '' }}
                                    </div>
                                </div>
                            </div>

                            {{-- Operational Status Badge --}}
                            @if ($isOut)
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold text-amber-700 ring-1 ring-inset ring-amber-200 animate-pulse">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                    On-Trip
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    In Pool
                                </span>
                            @endif
                        </div>

                        {{-- Active Trip Detail if Out --}}
                        @if ($isOut && $v->activeCheckOut)
                            <div class="mt-3 rounded-xl bg-amber-50/80 border border-amber-200/80 p-2.5 text-xs text-amber-900">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-semibold text-amber-800">Pengemudi: {{ $v->activeCheckOut->driver_name }}</span>
                                    <span class="text-[10px] text-amber-600">{{ $v->activeCheckOut->created_at->format('H:i') }} WIB</span>
                                </div>
                                @if ($v->activeCheckOut->trip_purpose)
                                    <p class="text-[11px] text-amber-700 truncate mt-0.5">"{{ $v->activeCheckOut->trip_purpose }}"</p>
                                @endif
                            </div>
                        @else
                            {{-- Info Row: Driver & Odometer --}}
                            <div class="mt-3.5 grid grid-cols-2 gap-2 text-xs border-y border-slate-100 py-2.5">
                                <div>
                                    <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Driver Tetap</span>
                                    <span class="font-medium text-slate-700 truncate block mt-0.5">{{ $v->driver_name ?: '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Odometer</span>
                                    <span class="font-bold text-slate-900 block mt-0.5">{{ number_format($v->odometer) }} km</span>
                                </div>
                            </div>
                        @endif

                        {{-- Legal Document Compliance Dots --}}
                        <div class="mt-3 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-3">
                                {{-- STNK 1th --}}
                                <div class="flex items-center gap-1.5" title="Pajak STNK 1 Tahunan">
                                    <span class="h-2 w-2 rounded-full {{ $stnkDoc ? ($stnkDoc->status === 'expired' ? 'bg-rose-500' : ($stnkDoc->status === 'warning' || $stnkDoc->status === 'critical' ? 'bg-amber-500' : 'bg-emerald-500')) : 'bg-slate-300' }}"></span>
                                    <span class="text-[11px] text-slate-600 font-medium">STNK</span>
                                </div>

                                {{-- KIR (Wajib for Truck) --}}
                                @if ($v->requires_kir)
                                    <div class="flex items-center gap-1.5" title="Uji Berkala KIR (Mobil Gede/Niaga)">
                                        <span class="h-2 w-2 rounded-full {{ $kirDoc ? ($kirDoc->status === 'expired' ? 'bg-rose-500' : ($kirDoc->status === 'warning' || $kirDoc->status === 'critical' ? 'bg-amber-500' : 'bg-emerald-500')) : 'bg-rose-400' }}"></span>
                                        <span class="text-[11px] text-slate-600 font-medium">KIR</span>
                                    </div>
                                @endif
                            </div>

                            <span class="text-[11px] text-slate-400 font-medium">
                                {{ $v->fuel_type_label }}
                            </span>
                        </div>
                    </div>

                    {{-- Card Footer Actions --}}
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        @if (!$v->is_sold)
                            @if ($isOut)
                                <a href="{{ route('vehicles.inspect', ['vehicle' => $v, 'type' => 'check_in']) }}"
                                    class="inline-flex items-center justify-center rounded-xl bg-amber-500 px-3 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-amber-600 transition">
                                    <i class="bi bi-box-arrow-in-down mr-1"></i> Check-in
                                </a>
                            @else
                                <a href="{{ route('vehicles.inspect', ['vehicle' => $v, 'type' => 'check_out']) }}"
                                    class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-emerald-700 transition">
                                    <i class="bi bi-box-arrow-up-right mr-1"></i> P2H Check-out
                                </a>
                            @endif
                        @endif

                        <div class="flex items-center gap-1.5 ml-auto">
                            <a href="{{ route('vehicles.show', $v) }}"
                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                                title="Buka Detail Cockpit">
                                <i class="bi bi-eye mr-1"></i> Cockpit
                            </a>
                            <a href="{{ route('services.create', $v) }}"
                                class="inline-flex items-center rounded-xl bg-slate-100 px-2 py-1.5 text-xs font-semibold text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition"
                                title="Tambah Riwayat Servis">
                                <i class="bi bi-wrench"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-slate-300 p-12 text-center">
                    <i class="bi bi-truck text-4xl text-slate-300"></i>
                    <h3 class="mt-2 text-sm font-bold text-slate-700">Tidak ada armada ditemukan</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Coba ubah kata kunci pencarian atau filter kategori.</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- VIEW MODE 2: COMPACT DATA TABLE --}}
    @if ($viewMode === 'table')
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">Armada / Plat</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Driver</th>
                            <th class="px-4 py-3">Odometer</th>
                            <th class="px-4 py-3">Status Operasional</th>
                            <th class="px-4 py-3">Servis Terakhir</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @forelse ($vehicles as $v)
                            @php
                                $last = $v->latestService;
                                $isOut = $v->is_out_on_trip;
                            @endphp
                            <tr wire:key="veh-table-{{ $v->id }}" class="hover:bg-slate-50/70 transition">
                                <td class="px-4 py-3.5">
                                    <div class="font-mono font-bold text-slate-900">{{ $v->plate_number }}</div>
                                    <div class="text-[11px] text-slate-500">{{ trim($v->brand . ' ' . $v->model) }}</div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                        {{ $v->category_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="text-slate-800 font-medium">{{ $v->driver_name ?: '—' }}</span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap font-semibold">
                                    {{ number_format($v->odometer) }} km
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if ($isOut)
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-inset ring-amber-200 animate-pulse">
                                            On-Trip
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            In Pool
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if ($last)
                                        <div class="font-medium text-slate-800">{{ $last->service_date->isoFormat('DD/MM/YY') }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $last->workshop ?? 'Internal' }}</div>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        @if (!$v->is_sold)
                                            @if ($isOut)
                                                <a href="{{ route('vehicles.inspect', ['vehicle' => $v, 'type' => 'check_in']) }}"
                                                    class="rounded-lg bg-amber-500 px-2.5 py-1 text-xs font-bold text-white hover:bg-amber-600 shadow-xs">
                                                    Check-in
                                                </a>
                                            @else
                                                <a href="{{ route('vehicles.inspect', ['vehicle' => $v, 'type' => 'check_out']) }}"
                                                    class="rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-bold text-white hover:bg-emerald-700 shadow-xs">
                                                    P2H
                                                </a>
                                            @endif
                                        @endif
                                        <a href="{{ route('vehicles.show', $v) }}"
                                            class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                            Cockpit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-xs text-slate-400">
                                    Tidak ada data armada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Pagination --}}
    <div class="pt-2">
        {{ $vehicles->links() }}
    </div>
</div>
