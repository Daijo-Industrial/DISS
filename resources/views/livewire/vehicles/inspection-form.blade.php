<div class="max-w-5xl mx-auto px-3 sm:px-6 py-5 space-y-6">
    {{-- Top Navigation & Mode Switcher --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <nav class="text-xs text-slate-500" aria-label="Breadcrumb">
            <ol class="flex items-center gap-1.5">
                <li>
                    <a href="{{ route('vehicles.index') }}" class="hover:text-slate-800 hover:underline">
                        {{ __('fleet.common.fleet') }}
                    </a>
                </li>
                <li class="text-slate-300">/</li>
                <li>
                    <a href="{{ route('vehicles.show', $vehicle) }}" class="hover:text-slate-800 hover:underline">
                        {{ $vehicle->plate_number }}
                    </a>
                </li>
                <li class="text-slate-300">/</li>
                <li class="font-bold text-slate-800">
                    {{ $type === 'check_in' ? __('fleet.inspection.type_checkin') : __('fleet.inspection.type_checkout') }}
                </li>
            </ol>
        </nav>

        {{-- Mobile-first Mode Switcher --}}
        <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200 self-start sm:self-auto">
            <button type="button" wire:click="switchType('check_out')"
                class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $type === 'check_out' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                <i class="bi bi-box-arrow-up-right mr-1"></i> Check-out
            </button>
            <button type="button" wire:click="switchType('check_in')"
                class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $type === 'check_in' ? 'bg-white text-amber-700 shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                <i class="bi bi-box-arrow-in-down mr-1"></i> Check-in
            </button>
        </div>
    </div>

    {{-- Interactive Header Cockpit Card --}}
    @php
        $totalItems = count($checklist);
        $okCount = collect($checklist)->where('status', 'ok')->count();
        $isAllOk = $okCount === $totalItems;
        $readinessPercent = $totalItems > 0 ? round(($okCount / $totalItems) * 100) : 100;
    @endphp

    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm overflow-hidden relative">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            {{-- Vehicle & Inspection Info --}}
            <div class="flex items-start gap-4">
                @if ($vehicle->image_path)
                    <button type="button" @click="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $vehicle->image_path) }}', title: 'Foto Profil Armada {{ $vehicle->plate_number }}', subtitle: '{{ trim($vehicle->brand . ' ' . $vehicle->model) }}' })"
                        class="cursor-pointer shrink-0" title="Perbesar foto">
                        <img src="{{ asset('storage/' . $vehicle->image_path) }}" alt="{{ $vehicle->plate_number }}"
                            class="h-14 w-14 rounded-2xl object-cover border border-slate-200 shadow-xs hover:opacity-90 transition">
                    </button>
                @else
                    <div class="h-14 w-14 rounded-2xl flex items-center justify-center text-2xl shadow-xs shrink-0
                        {{ $vehicle->category === 'commercial_truck' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                        @if ($vehicle->category === 'commercial_truck')
                            <i class="bi bi-truck"></i>
                        @else
                            <i class="bi bi-car-front"></i>
                        @endif
                    </div>
                @endif
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-black tracking-tight text-slate-900 font-mono">
                            {{ $vehicle->plate_number }}
                        </h1>
                        <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">
                            {{ $vehicle->category_label }}
                        </span>
                        <span class="rounded-lg bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">
                            {{ $vehicle->fuel_type_label }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ trim($vehicle->brand . ' ' . $vehicle->model) }} • Odometer Saat Ini: <strong>{{ number_format($vehicle->odometer) }} km</strong>
                    </p>
                </div>
            </div>

            {{-- Readiness Indicator Gauge --}}
            <div class="flex items-center gap-3 bg-slate-50 border border-slate-200/70 rounded-2xl px-4 py-2.5">
                <div class="text-right">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">{{ __('fleet.inspection.readiness_label') }}</span>
                    <span class="text-sm font-extrabold {{ $isAllOk ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ __('fleet.inspection.points_ready', ['ready' => $okCount, 'total' => $totalItems]) }}
                    </span>
                </div>
                <div class="relative h-10 w-10 flex items-center justify-center">
                    <span class="h-10 w-10 rounded-full border-3 {{ $isAllOk ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-amber-500 bg-amber-50 text-amber-700' }} flex items-center justify-center text-xs font-black">
                        {{ $readinessPercent }}%
                    </span>
                </div>
            </div>
        </div>

        {{-- Check-in Context Alert if applicable --}}
        @if ($type === 'check_in' && $parentInspection)
            <div class="mt-4 rounded-xl bg-amber-50/70 border border-amber-200/70 p-3 text-xs text-amber-900 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <i class="bi bi-clock-history text-amber-600 text-sm"></i>
                    <span>
                        {{ __('fleet.inspection.type_checkout') }}: <strong>{{ $parentInspection->created_at->isoFormat('DD MMM YYYY, HH:mm') }}</strong>
                        oleh Driver <strong>{{ $parentInspection->driver_name }}</strong> (KM: <strong>{{ number_format($parentInspection->odometer) }} km</strong>)
                    </span>
                </div>
                @if ($odometer > $parentInspection->odometer)
                    <span class="rounded-lg bg-amber-200/80 px-2 py-0.5 font-bold text-amber-950 text-[11px]">
                        {{ __('fleet.inspection.trip_distance') }}: +{{ number_format($odometer - $parentInspection->odometer) }} km
                    </span>
                @endif
            </div>
        @endif
    </div>

    {{-- Main Form Container --}}
    <form wire:submit.prevent="save" class="space-y-6">
        {{-- Section 1: Trip & Driver Strip --}}
        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <i class="bi bi-person-badge text-indigo-600 text-sm"></i>
                1. {{ __('fleet.inspection.driver_name') }}, {{ __('fleet.common.odometer') }} &amp; {{ __('fleet.common.fuel') }}
            </h2>

            <div class="grid gap-4 sm:grid-cols-3">
                {{-- Driver Name --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        {{ __('fleet.inspection.driver_name') }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-100 transition">
                        <i class="bi bi-person text-slate-400 mr-2 text-base"></i>
                        <input type="text" wire:model.defer="driver_name" placeholder="{{ __('fleet.inspection.driver_name') }}"
                            class="w-full border-0 p-0 text-xs font-medium text-slate-900 focus:outline-none">
                    </div>
                    @error('driver_name')
                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Odometer Input --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        {{ $type === 'check_in' ? __('fleet.inspection.return_odometer') : __('fleet.inspection.start_odometer') }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-100 transition">
                        <i class="bi bi-speedometer2 text-slate-400 mr-2 text-base"></i>
                        <input type="number" wire:model.live.debounce.300ms="odometer" placeholder="{{ $vehicle->odometer }}"
                            class="w-full border-0 p-0 text-xs font-mono font-bold text-slate-900 focus:outline-none">
                        <span class="text-xs font-semibold text-slate-400">KM</span>
                    </div>
                    @error('odometer')
                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Fuel Level Quick Picker --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        {{ __('fleet.inspection.fuel_level') }}: <strong class="text-indigo-600 font-mono">{{ $fuel_percentage }}%</strong>
                    </label>
                    <div class="grid grid-cols-4 gap-1.5">
                        @foreach ([25 => '1/4', 50 => '1/2', 75 => '3/4', 100 => 'Full'] as $val => $lbl)
                            <button type="button" wire:click="$set('fuel_percentage', {{ $val }})"
                                class="rounded-xl py-1.5 text-xs font-bold transition active:scale-95
                                {{ $fuel_percentage == $val ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                {{ $lbl }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Trip Purpose / Notes --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                    {{ __('fleet.inspection.trip_purpose') }}
                </label>
                <input type="text" wire:model.defer="trip_purpose" placeholder="{{ __('fleet.inspection.trip_purpose_placeholder') }}"
                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 transition">
            </div>
        </div>

        {{-- Section 2: Interactive Visual Vehicle Walkthrough (7 Inspection Points) --}}
        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <i class="bi bi-shield-check text-indigo-600 text-sm"></i>
                        2. {{ __('fleet.inspection.checklist_heading') }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Tap tombol <strong class="text-emerald-700">OK</strong> jika normal atau <strong class="text-rose-700">Ada Isu</strong> jika ditemukan cacat/kerusakan.
                    </p>
                </div>

                {{-- Quick Pass-All Button --}}
                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-slate-400">Pintasan:</span>
                    <button type="button"
                        wire:click="$set('checklist.body.status', 'ok'); $set('checklist.tires.status', 'ok'); $set('checklist.interior.status', 'ok'); $set('checklist.battery_fuel.status', 'ok'); $set('checklist.headlights.status', 'ok'); $set('checklist.brake_lights.status', 'ok'); $set('checklist.turn_signals.status', 'ok'); recalculateSeverity();"
                        class="rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800 hover:bg-emerald-100 transition">
                        <i class="bi bi-check-all mr-1"></i> Semua Item OK (Aman)
                    </button>
                </div>
            </div>

            {{-- 4 Zones Visual Representation --}}
            <div class="grid gap-4 md:grid-cols-2">
                {{-- ZONE 1: DEPAN (Headlights & Front Body) --}}
                <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="h-6 w-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">1</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Bagian Depan &amp; Bodi</span>
                        </div>
                    </div>

                    {{-- Item: Headlights --}}
                    @php $hlOk = ($checklist['headlights']['status'] ?? 'ok') === 'ok'; @endphp
                    <div class="rounded-xl border p-3 bg-white transition {{ $hlOk ? 'border-slate-200' : 'border-rose-300 ring-2 ring-rose-200' }}">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <i class="bi bi-brightness-high text-lg {{ $hlOk ? 'text-slate-500' : 'text-rose-600' }}"></i>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800">Lampu Depan (Headlamp)</h4>
                                    <p class="text-[11px] text-slate-400">Lampu dekat &amp; jauh menyala normal</p>
                                </div>
                            </div>
                            <div class="inline-flex rounded-lg p-0.5 bg-slate-100 border border-slate-200 shrink-0">
                                <button type="button" wire:click="setChecklistStatus('headlights', 'ok')"
                                    class="px-2.5 py-1 rounded-md text-xs font-bold transition {{ $hlOk ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                                    OK
                                </button>
                                <button type="button" wire:click="setChecklistStatus('headlights', 'issue')"
                                    class="px-2.5 py-1 rounded-md text-xs font-bold transition {{ !$hlOk ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-500 hover:text-rose-600' }}">
                                    Ada Isu
                                </button>
                            </div>
                        </div>

                        {{-- Remark & Multiple Photos --}}
                        <div class="mt-2.5 pt-2 border-t {{ $hlOk ? 'border-slate-100' : 'border-rose-100' }} space-y-2">
                            <input type="text" wire:model.defer="checklist.headlights.notes"
                                placeholder="{{ $hlOk ? 'Catatan / remark kondisi (opsional)...' : 'Rincian kerusakan (misal: headlamp kiri redup/mati)...' }}"
                                class="w-full rounded-lg border {{ $hlOk ? 'border-slate-200 bg-slate-50/50 text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500' : 'border-rose-200 bg-rose-50/50 text-rose-950 placeholder:text-rose-400 focus:bg-white focus:border-rose-500' }} px-2.5 py-1 text-xs focus:outline-none transition">

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-600 shadow-xs transition">
                                    <i class="bi bi-camera text-indigo-600"></i>
                                    <span>Lampirkan Foto</span>
                                    <input type="file" wire:model="point_photos.headlights" multiple accept="image/*" class="sr-only">
                                </label>

                                <div wire:loading wire:target="point_photos.headlights" class="text-[11px] text-indigo-600 font-medium">
                                    <i class="bi bi-arrow-repeat animate-spin mr-1"></i> Mengunggah...
                                </div>

                                @if (!empty($point_photos['headlights']))
                                    <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md">
                                        {{ count($point_photos['headlights']) }} foto dipilih
                                    </span>
                                @endif
                            </div>

                            @if (!empty($point_photos['headlights']))
                                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                    @foreach ($point_photos['headlights'] as $idx => $p)
                                        <div class="relative group">
                                            @if (method_exists($p, 'temporaryUrl'))
                                                <img src="{{ $p->temporaryUrl() }}" @click="$dispatch('open-lightbox', { src: '{{ $p->temporaryUrl() }}', title: 'Pratinjau Foto Temuan Checklist' })" class="h-11 w-11 object-cover rounded-lg border border-slate-200 shadow-xs cursor-pointer hover:opacity-90 transition">
                                            @else
                                                <div class="h-11 w-11 bg-slate-100 rounded-lg flex items-center justify-center text-[9px] text-slate-500 font-semibold border border-slate-200">#{{ $idx+1 }}</div>
                                            @endif
                                            <button type="button" wire:click="removePointPhoto('headlights', {{ $idx }})" title="Hapus foto"
                                                class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full h-4 w-4 flex items-center justify-center text-[10px] hover:bg-rose-700 shadow-xs">
                                                ×
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Item: Body --}}
                    @php $bOk = ($checklist['body']['status'] ?? 'ok') === 'ok'; @endphp
                    <div class="rounded-xl border p-3 bg-white transition {{ $bOk ? 'border-slate-200' : 'border-rose-300 ring-2 ring-rose-200' }}">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <i class="bi bi-car-front text-lg {{ $bOk ? 'text-slate-500' : 'text-rose-600' }}"></i>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800">Bodi Eksterior</h4>
                                    <p class="text-[11px] text-slate-400">Bebas dari baret baru, penyok, kebersihan luar</p>
                                </div>
                            </div>
                            <div class="inline-flex rounded-lg p-0.5 bg-slate-100 border border-slate-200 shrink-0">
                                <button type="button" wire:click="setChecklistStatus('body', 'ok')"
                                    class="px-2.5 py-1 rounded-md text-xs font-bold transition {{ $bOk ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                                    OK
                                </button>
                                <button type="button" wire:click="setChecklistStatus('body', 'issue')"
                                    class="px-2.5 py-1 rounded-md text-xs font-bold transition {{ !$bOk ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-500 hover:text-rose-600' }}">
                                    Ada Isu
                                </button>
                            </div>
                        </div>

                        {{-- Remark & Multiple Photos --}}
                        <div class="mt-2.5 pt-2 border-t {{ $bOk ? 'border-slate-100' : 'border-rose-100' }} space-y-2">
                            <input type="text" wire:model.defer="checklist.body.notes"
                                placeholder="{{ $bOk ? 'Catatan / remark bodi (opsional)...' : 'Rincian kerusakan bodi luar...' }}"
                                class="w-full rounded-lg border {{ $bOk ? 'border-slate-200 bg-slate-50/50 text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500' : 'border-rose-200 bg-rose-50/50 text-rose-950 placeholder:text-rose-400 focus:bg-white focus:border-rose-500' }} px-2.5 py-1 text-xs focus:outline-none transition">

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-600 shadow-xs transition">
                                    <i class="bi bi-camera text-indigo-600"></i>
                                    <span>Lampirkan Foto</span>
                                    <input type="file" wire:model="point_photos.body" multiple accept="image/*" class="sr-only">
                                </label>

                                <div wire:loading wire:target="point_photos.body" class="text-[11px] text-indigo-600 font-medium">
                                    <i class="bi bi-arrow-repeat animate-spin mr-1"></i> Mengunggah...
                                </div>

                                @if (!empty($point_photos['body']))
                                    <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md">
                                        {{ count($point_photos['body']) }} foto dipilih
                                    </span>
                                @endif
                            </div>

                            @if (!empty($point_photos['body']))
                                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                    @foreach ($point_photos['body'] as $idx => $p)
                                        <div class="relative group">
                                            @if (method_exists($p, 'temporaryUrl'))
                                                <img src="{{ $p->temporaryUrl() }}" @click="$dispatch('open-lightbox', { src: '{{ $p->temporaryUrl() }}', title: 'Pratinjau Foto Temuan Checklist' })" class="h-11 w-11 object-cover rounded-lg border border-slate-200 shadow-xs cursor-pointer hover:opacity-90 transition">
                                            @else
                                                <div class="h-11 w-11 bg-slate-100 rounded-lg flex items-center justify-center text-[9px] text-slate-500 font-semibold border border-slate-200">#{{ $idx+1 }}</div>
                                            @endif
                                            <button type="button" wire:click="removePointPhoto('body', {{ $idx }})" title="Hapus foto"
                                                class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full h-4 w-4 flex items-center justify-center text-[10px] hover:bg-rose-700 shadow-xs">
                                                ×
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ZONE 2: BELAKANG (Brake & Turn Signals) --}}
                <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="h-6 w-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">2</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Lampu Sinyal &amp; Belakang</span>
                        </div>
                    </div>

                    {{-- Item: Brake Lights --}}
                    @php $blOk = ($checklist['brake_lights']['status'] ?? 'ok') === 'ok'; @endphp
                    <div class="rounded-xl border p-3 bg-white transition {{ $blOk ? 'border-slate-200' : 'border-rose-300 ring-2 ring-rose-200' }}">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <i class="bi bi-shield-exclamation text-lg {{ $blOk ? 'text-slate-500' : 'text-rose-600' }}"></i>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800">Lampu Rem Belakang</h4>
                                    <p class="text-[11px] text-slate-400">Menyala terang saat pedal rem diinjak</p>
                                </div>
                            </div>
                            <div class="inline-flex rounded-lg p-0.5 bg-slate-100 border border-slate-200 shrink-0">
                                <button type="button" wire:click="setChecklistStatus('brake_lights', 'ok')"
                                    class="px-2.5 py-1 rounded-md text-xs font-bold transition {{ $blOk ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                                    OK
                                </button>
                                <button type="button" wire:click="setChecklistStatus('brake_lights', 'issue')"
                                    class="px-2.5 py-1 rounded-md text-xs font-bold transition {{ !$blOk ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-500 hover:text-rose-600' }}">
                                    Ada Isu
                                </button>
                            </div>
                        </div>

                        {{-- Remark & Multiple Photos --}}
                        <div class="mt-2.5 pt-2 border-t {{ $blOk ? 'border-slate-100' : 'border-rose-100' }} space-y-2">
                            <input type="text" wire:model.defer="checklist.brake_lights.notes"
                                placeholder="{{ $blOk ? 'Catatan / remark lampu rem (opsional)...' : 'Rincian kendala lampu rem...' }}"
                                class="w-full rounded-lg border {{ $blOk ? 'border-slate-200 bg-slate-50/50 text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500' : 'border-rose-200 bg-rose-50/50 text-rose-950 placeholder:text-rose-400 focus:bg-white focus:border-rose-500' }} px-2.5 py-1 text-xs focus:outline-none transition">

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-600 shadow-xs transition">
                                    <i class="bi bi-camera text-indigo-600"></i>
                                    <span>Lampirkan Foto</span>
                                    <input type="file" wire:model="point_photos.brake_lights" multiple accept="image/*" class="sr-only">
                                </label>

                                <div wire:loading wire:target="point_photos.brake_lights" class="text-[11px] text-indigo-600 font-medium">
                                    <i class="bi bi-arrow-repeat animate-spin mr-1"></i> Mengunggah...
                                </div>

                                @if (!empty($point_photos['brake_lights']))
                                    <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md">
                                        {{ count($point_photos['brake_lights']) }} foto dipilih
                                    </span>
                                @endif
                            </div>

                            @if (!empty($point_photos['brake_lights']))
                                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                    @foreach ($point_photos['brake_lights'] as $idx => $p)
                                        <div class="relative group">
                                            @if (method_exists($p, 'temporaryUrl'))
                                                <img src="{{ $p->temporaryUrl() }}" @click="$dispatch('open-lightbox', { src: '{{ $p->temporaryUrl() }}', title: 'Pratinjau Foto Temuan Checklist' })" class="h-11 w-11 object-cover rounded-lg border border-slate-200 shadow-xs cursor-pointer hover:opacity-90 transition">
                                            @else
                                                <div class="h-11 w-11 bg-slate-100 rounded-lg flex items-center justify-center text-[9px] text-slate-500 font-semibold border border-slate-200">#{{ $idx+1 }}</div>
                                            @endif
                                            <button type="button" wire:click="removePointPhoto('brake_lights', {{ $idx }})" title="Hapus foto"
                                                class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full h-4 w-4 flex items-center justify-center text-[10px] hover:bg-rose-700 shadow-xs">
                                                ×
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Item: Turn Signals --}}
                    @php $tsOk = ($checklist['turn_signals']['status'] ?? 'ok') === 'ok'; @endphp
                    <div class="rounded-xl border p-3 bg-white transition {{ $tsOk ? 'border-slate-200' : 'border-rose-300 ring-2 ring-rose-200' }}">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <i class="bi bi-arrow-left-right text-lg {{ $tsOk ? 'text-slate-500' : 'text-rose-600' }}"></i>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800">Lampu Sein &amp; Hazard</h4>
                                    <p class="text-[11px] text-slate-400">Sein kiri, sein kanan, dan lampu hazard menyala</p>
                                </div>
                            </div>
                            <div class="inline-flex rounded-lg p-0.5 bg-slate-100 border border-slate-200 shrink-0">
                                <button type="button" wire:click="setChecklistStatus('turn_signals', 'ok')"
                                    class="px-2.5 py-1 rounded-md text-xs font-bold transition {{ $tsOk ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                                    OK
                                </button>
                                <button type="button" wire:click="setChecklistStatus('turn_signals', 'issue')"
                                    class="px-2.5 py-1 rounded-md text-xs font-bold transition {{ !$tsOk ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-500 hover:text-rose-600' }}">
                                    Ada Isu
                                </button>
                            </div>
                        </div>

                        {{-- Remark & Multiple Photos --}}
                        <div class="mt-2.5 pt-2 border-t {{ $tsOk ? 'border-slate-100' : 'border-rose-100' }} space-y-2">
                            <input type="text" wire:model.defer="checklist.turn_signals.notes"
                                placeholder="{{ $tsOk ? 'Catatan / remark sein & hazard (opsional)...' : 'Rincian lampu sein yang mati...' }}"
                                class="w-full rounded-lg border {{ $tsOk ? 'border-slate-200 bg-slate-50/50 text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500' : 'border-rose-200 bg-rose-50/50 text-rose-950 placeholder:text-rose-400 focus:bg-white focus:border-rose-500' }} px-2.5 py-1 text-xs focus:outline-none transition">

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-600 shadow-xs transition">
                                    <i class="bi bi-camera text-indigo-600"></i>
                                    <span>Lampirkan Foto</span>
                                    <input type="file" wire:model="point_photos.turn_signals" multiple accept="image/*" class="sr-only">
                                </label>

                                <div wire:loading wire:target="point_photos.turn_signals" class="text-[11px] text-indigo-600 font-medium">
                                    <i class="bi bi-arrow-repeat animate-spin mr-1"></i> Mengunggah...
                                </div>

                                @if (!empty($point_photos['turn_signals']))
                                    <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md">
                                        {{ count($point_photos['turn_signals']) }} foto dipilih
                                    </span>
                                @endif
                            </div>

                            @if (!empty($point_photos['turn_signals']))
                                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                    @foreach ($point_photos['turn_signals'] as $idx => $p)
                                        <div class="relative group">
                                            @if (method_exists($p, 'temporaryUrl'))
                                                <img src="{{ $p->temporaryUrl() }}" @click="$dispatch('open-lightbox', { src: '{{ $p->temporaryUrl() }}', title: 'Pratinjau Foto Temuan Checklist' })" class="h-11 w-11 object-cover rounded-lg border border-slate-200 shadow-xs cursor-pointer hover:opacity-90 transition">
                                            @else
                                                <div class="h-11 w-11 bg-slate-100 rounded-lg flex items-center justify-center text-[9px] text-slate-500 font-semibold border border-slate-200">#{{ $idx+1 }}</div>
                                            @endif
                                            <button type="button" wire:click="removePointPhoto('turn_signals', {{ $idx }})" title="Hapus foto"
                                                class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full h-4 w-4 flex items-center justify-center text-[10px] hover:bg-rose-700 shadow-xs">
                                                ×
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ZONE 3: RODA & BAN (Tires & Air Pressure) --}}
                <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="h-6 w-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">3</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Roda &amp; Ban Kendaraan</span>
                        </div>
                    </div>

                    {{-- Item: Tires --}}
                    @php $tOk = ($checklist['tires']['status'] ?? 'ok') === 'ok'; @endphp
                    <div class="rounded-xl border p-3 bg-white transition {{ $tOk ? 'border-slate-200' : 'border-rose-300 ring-2 ring-rose-200' }}">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <i class="bi bi-disc text-lg {{ $tOk ? 'text-slate-500' : 'text-rose-600' }}"></i>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800">Kondisi Ban &amp; Angin</h4>
                                    <p class="text-[11px] text-slate-400">Tekanan angin cukup, tidak gundul, ban serep ada</p>
                                </div>
                            </div>
                            <div class="inline-flex rounded-lg p-0.5 bg-slate-100 border border-slate-200 shrink-0">
                                <button type="button" wire:click="setChecklistStatus('tires', 'ok')"
                                    class="px-2.5 py-1 rounded-md text-xs font-bold transition {{ $tOk ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                                    OK
                                </button>
                                <button type="button" wire:click="setChecklistStatus('tires', 'issue')"
                                    class="px-2.5 py-1 rounded-md text-xs font-bold transition {{ !$tOk ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-500 hover:text-rose-600' }}">
                                    Ada Isu
                                </button>
                            </div>
                        </div>

                        {{-- Remark & Multiple Photos --}}
                        <div class="mt-2.5 pt-2 border-t {{ $tOk ? 'border-slate-100' : 'border-rose-100' }} space-y-2">
                            <input type="text" wire:model.defer="checklist.tires.notes"
                                placeholder="{{ $tOk ? 'Catatan / remark ban (opsional)...' : 'Rincian kondisi ban (misal: ban depan kanan bocor halus)...' }}"
                                class="w-full rounded-lg border {{ $tOk ? 'border-slate-200 bg-slate-50/50 text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500' : 'border-rose-200 bg-rose-50/50 text-rose-950 placeholder:text-rose-400 focus:bg-white focus:border-rose-500' }} px-2.5 py-1 text-xs focus:outline-none transition">

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-600 shadow-xs transition">
                                    <i class="bi bi-camera text-indigo-600"></i>
                                    <span>Lampirkan Foto</span>
                                    <input type="file" wire:model="point_photos.tires" multiple accept="image/*" class="sr-only">
                                </label>

                                <div wire:loading wire:target="point_photos.tires" class="text-[11px] text-indigo-600 font-medium">
                                    <i class="bi bi-arrow-repeat animate-spin mr-1"></i> Mengunggah...
                                </div>

                                @if (!empty($point_photos['tires']))
                                    <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md">
                                        {{ count($point_photos['tires']) }} foto dipilih
                                    </span>
                                @endif
                            </div>

                            @if (!empty($point_photos['tires']))
                                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                    @foreach ($point_photos['tires'] as $idx => $p)
                                        <div class="relative group">
                                            @if (method_exists($p, 'temporaryUrl'))
                                                <img src="{{ $p->temporaryUrl() }}" @click="$dispatch('open-lightbox', { src: '{{ $p->temporaryUrl() }}', title: 'Pratinjau Foto Temuan Checklist' })" class="h-11 w-11 object-cover rounded-lg border border-slate-200 shadow-xs cursor-pointer hover:opacity-90 transition">
                                            @else
                                                <div class="h-11 w-11 bg-slate-100 rounded-lg flex items-center justify-center text-[9px] text-slate-500 font-semibold border border-slate-200">#{{ $idx+1 }}</div>
                                            @endif
                                            <button type="button" wire:click="removePointPhoto('tires', {{ $idx }})" title="Hapus foto"
                                                class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full h-4 w-4 flex items-center justify-center text-[10px] hover:bg-rose-700 shadow-xs">
                                                ×
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Item: Battery / Fuel --}}
                    @php $bfOk = ($checklist['battery_fuel']['status'] ?? 'ok') === 'ok'; @endphp
                    <div class="rounded-xl border p-3 bg-white transition {{ $bfOk ? 'border-slate-200' : 'border-rose-300 ring-2 ring-rose-200' }}">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <i class="bi bi-fuel-pump text-lg {{ $bfOk ? 'text-slate-500' : 'text-rose-600' }}"></i>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800">Baterai (Aki) &amp; Bensin</h4>
                                    <p class="text-[11px] text-slate-400">Starter lancar, indikator aki normal, bensin cukup</p>
                                </div>
                            </div>
                            <div class="inline-flex rounded-lg p-0.5 bg-slate-100 border border-slate-200 shrink-0">
                                <button type="button" wire:click="setChecklistStatus('battery_fuel', 'ok')"
                                    class="px-2.5 py-1 rounded-md text-xs font-bold transition {{ $bfOk ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                                    OK
                                </button>
                                <button type="button" wire:click="setChecklistStatus('battery_fuel', 'issue')"
                                    class="px-2.5 py-1 rounded-md text-xs font-bold transition {{ !$bfOk ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-500 hover:text-rose-600' }}">
                                    Ada Isu
                                </button>
                            </div>
                        </div>

                        {{-- Remark & Multiple Photos --}}
                        <div class="mt-2.5 pt-2 border-t {{ $bfOk ? 'border-slate-100' : 'border-rose-100' }} space-y-2">
                            <input type="text" wire:model.defer="checklist.battery_fuel.notes"
                                placeholder="{{ $bfOk ? 'Catatan / remark aki & bensin (opsional)...' : 'Rincian masalah aki / kelistrikan...' }}"
                                class="w-full rounded-lg border {{ $bfOk ? 'border-slate-200 bg-slate-50/50 text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500' : 'border-rose-200 bg-rose-50/50 text-rose-950 placeholder:text-rose-400 focus:bg-white focus:border-rose-500' }} px-2.5 py-1 text-xs focus:outline-none transition">

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-600 shadow-xs transition">
                                    <i class="bi bi-camera text-indigo-600"></i>
                                    <span>Lampirkan Foto</span>
                                    <input type="file" wire:model="point_photos.battery_fuel" multiple accept="image/*" class="sr-only">
                                </label>

                                <div wire:loading wire:target="point_photos.battery_fuel" class="text-[11px] text-indigo-600 font-medium">
                                    <i class="bi bi-arrow-repeat animate-spin mr-1"></i> Mengunggah...
                                </div>

                                @if (!empty($point_photos['battery_fuel']))
                                    <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md">
                                        {{ count($point_photos['battery_fuel']) }} foto dipilih
                                    </span>
                                @endif
                            </div>

                            @if (!empty($point_photos['battery_fuel']))
                                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                    @foreach ($point_photos['battery_fuel'] as $idx => $p)
                                        <div class="relative group">
                                            @if (method_exists($p, 'temporaryUrl'))
                                                <img src="{{ $p->temporaryUrl() }}" @click="$dispatch('open-lightbox', { src: '{{ $p->temporaryUrl() }}', title: 'Pratinjau Foto Temuan Checklist' })" class="h-11 w-11 object-cover rounded-lg border border-slate-200 shadow-xs cursor-pointer hover:opacity-90 transition">
                                            @else
                                                <div class="h-11 w-11 bg-slate-100 rounded-lg flex items-center justify-center text-[9px] text-slate-500 font-semibold border border-slate-200">#{{ $idx+1 }}</div>
                                            @endif
                                            <button type="button" wire:click="removePointPhoto('battery_fuel', {{ $idx }})" title="Hapus foto"
                                                class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full h-4 w-4 flex items-center justify-center text-[10px] hover:bg-rose-700 shadow-xs">
                                                ×
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ZONE 4: INTERIOR & SAFETY TOOLKIT --}}
                <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="h-6 w-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">4</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Kabin &amp; Perlengkapan Darurat</span>
                        </div>
                    </div>

                    {{-- Item: Interior & Safety --}}
                    @php $inOk = ($checklist['interior']['status'] ?? 'ok') === 'ok'; @endphp
                    <div class="rounded-xl border p-3 bg-white transition {{ $inOk ? 'border-slate-200' : 'border-rose-300 ring-2 ring-rose-200' }}">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <i class="bi bi-box-seam text-lg {{ $inOk ? 'text-slate-500' : 'text-rose-600' }}"></i>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800">Isi Mobil &amp; Safety Toolkit</h4>
                                    <p class="text-[11px] text-slate-400">Kabin bersih, dongkrak, segitiga pengaman, APAR, P3K</p>
                                </div>
                            </div>
                            <div class="inline-flex rounded-lg p-0.5 bg-slate-100 border border-slate-200 shrink-0">
                                <button type="button" wire:click="setChecklistStatus('interior', 'ok')"
                                    class="px-2.5 py-1 rounded-md text-xs font-bold transition {{ $inOk ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800' }}">
                                    OK
                                </button>
                                <button type="button" wire:click="setChecklistStatus('interior', 'issue')"
                                    class="px-2.5 py-1 rounded-md text-xs font-bold transition {{ !$inOk ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-500 hover:text-rose-600' }}">
                                    Ada Isu
                                </button>
                            </div>
                        </div>

                        {{-- Remark & Multiple Photos --}}
                        <div class="mt-2.5 pt-2 border-t {{ $inOk ? 'border-slate-100' : 'border-rose-100' }} space-y-2">
                            <input type="text" wire:model.defer="checklist.interior.notes"
                                placeholder="{{ $inOk ? 'Catatan / remark kabin (opsional)...' : 'Rincian kelengkapan kabin yang kurang...' }}"
                                class="w-full rounded-lg border {{ $inOk ? 'border-slate-200 bg-slate-50/50 text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500' : 'border-rose-200 bg-rose-50/50 text-rose-950 placeholder:text-rose-400 focus:bg-white focus:border-rose-500' }} px-2.5 py-1 text-xs focus:outline-none transition">

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-600 shadow-xs transition">
                                    <i class="bi bi-camera text-indigo-600"></i>
                                    <span>Lampirkan Foto</span>
                                    <input type="file" wire:model="point_photos.interior" multiple accept="image/*" class="sr-only">
                                </label>

                                <div wire:loading wire:target="point_photos.interior" class="text-[11px] text-indigo-600 font-medium">
                                    <i class="bi bi-arrow-repeat animate-spin mr-1"></i> Mengunggah...
                                </div>

                                @if (!empty($point_photos['interior']))
                                    <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md">
                                        {{ count($point_photos['interior']) }} foto dipilih
                                    </span>
                                @endif
                            </div>

                            @if (!empty($point_photos['interior']))
                                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                    @foreach ($point_photos['interior'] as $idx => $p)
                                        <div class="relative group">
                                            @if (method_exists($p, 'temporaryUrl'))
                                                <img src="{{ $p->temporaryUrl() }}" @click="$dispatch('open-lightbox', { src: '{{ $p->temporaryUrl() }}', title: 'Pratinjau Foto Temuan Checklist' })" class="h-11 w-11 object-cover rounded-lg border border-slate-200 shadow-xs cursor-pointer hover:opacity-90 transition">
                                            @else
                                                <div class="h-11 w-11 bg-slate-100 rounded-lg flex items-center justify-center text-[9px] text-slate-500 font-semibold border border-slate-200">#{{ $idx+1 }}</div>
                                            @endif
                                            <button type="button" wire:click="removePointPhoto('interior', {{ $idx }})" title="Hapus foto"
                                                class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full h-4 w-4 flex items-center justify-center text-[10px] hover:bg-rose-700 shadow-xs">
                                                ×
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Defect & Grounding Drawer (Muncul jika ada kerusakan) --}}
        @if ($severity !== 'none')
            <div class="rounded-3xl border border-amber-300 bg-gradient-to-br from-amber-50/80 to-orange-50/60 p-5 shadow-sm space-y-4">
                <div class="flex items-center gap-2 text-amber-950 font-bold border-b border-amber-200 pb-2">
                    <i class="bi bi-exclamation-triangle-fill text-amber-600 text-base"></i>
                    <h3 class="text-xs uppercase tracking-wider">3. Penilaian Tingkat Kerusakan &amp; Unggah Foto Bukti</h3>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    {{-- Minor Severity --}}
                    <label class="flex cursor-pointer rounded-2xl border p-4 bg-white transition {{ $severity === 'minor' ? 'border-amber-500 ring-2 ring-amber-300' : 'border-slate-200 opacity-70' }}">
                        <input type="radio" wire:model.live="severity" value="minor" class="sr-only">
                        <div class="flex items-start gap-3">
                            <span class="h-8 w-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                <i class="bi bi-info-circle text-base"></i>
                            </span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900">Minor (Tetap Boleh Jalan)</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Kerusakan ringan/estetika (baret halus, sedikit kotor). Aman digunakan beroperasi.</p>
                            </div>
                        </div>
                    </label>

                    {{-- Critical Grounded --}}
                    <label class="flex cursor-pointer rounded-2xl border p-4 bg-white transition {{ $severity === 'critical_grounded' ? 'border-rose-500 ring-2 ring-rose-300' : 'border-slate-200 opacity-70' }}">
                        <input type="radio" wire:model.live="severity" value="critical_grounded" class="sr-only">
                        <div class="flex items-start gap-3">
                            <span class="h-8 w-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                                <i class="bi bi-slash-circle text-base"></i>
                            </span>
                            <div>
                                <h4 class="text-xs font-bold text-rose-950">{{ __('fleet.inspection.severity_critical') }}</h4>
                                <p class="text-[11px] text-rose-700 mt-0.5">Membahayakan keselamatan. Status armada otomatis diubah menjadi <strong>Maintenance</strong>.</p>
                            </div>
                        </div>
                    </label>
                </div>

                {{-- Notes & Photo Upload --}}
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            {{ __('fleet.inspection.defect_report') }} <span class="text-slate-400 font-normal">({{ __('fleet.common.na') }})</span>
                        </label>
                        <textarea wire:model.defer="defect_notes" rows="3" placeholder="{{ __('fleet.inspection.defect_notes_placeholder') }}"
                            class="w-full rounded-xl border border-slate-300 bg-white p-3 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none"></textarea>
                        @error('defect_notes')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            {{ __('fleet.inspection.defect_photos') }}
                        </label>
                        <div class="rounded-xl border-2 border-dashed border-slate-300 bg-white p-4 text-center">
                            <input type="file" wire:model="photos" multiple accept="image/*" class="text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700">
                            <div wire:loading wire:target="photos" class="text-[11px] text-indigo-600 mt-2 font-medium">
                                <i class="bi bi-arrow-repeat animate-spin mr-1"></i> {{ __('fleet.common.loading') }}
                            </div>

                            @if (!empty($photos))
                                <div class="mt-3 flex flex-wrap gap-2 justify-center">
                                    @foreach ($photos as $idx => $p)
                                        <div class="relative group">
                                            @if (method_exists($p, 'temporaryUrl'))
                                                <img src="{{ $p->temporaryUrl() }}" @click="$dispatch('open-lightbox', { src: '{{ $p->temporaryUrl() }}', title: 'Preview' })"
                                                    class="h-12 w-12 object-cover rounded-lg border border-slate-200 shadow-xs cursor-pointer hover:opacity-90 transition">
                                            @else
                                                <div class="h-12 w-12 bg-slate-100 rounded-lg flex items-center justify-center text-[9px] text-slate-500 font-semibold border border-slate-200">#{{ $idx+1 }}</div>
                                            @endif
                                            <button type="button" wire:click="removePhoto({{ $idx }})" title="{{ __('fleet.common.delete') }}"
                                                class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full h-4 w-4 flex items-center justify-center text-[10px] hover:bg-rose-700 shadow-xs">
                                                ×
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Sticky Floating Action Bar --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
            <a href="{{ route('vehicles.show', $vehicle) }}"
                class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                <i class="bi bi-arrow-left mr-1.5"></i> {{ __('fleet.common.cancel') }}
            </a>

            <button type="submit" wire:loading.attr="disabled"
                class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl px-6 py-2.5 text-xs font-extrabold text-white shadow-sm transition active:scale-[0.98]
                {{ $type === 'check_in' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700' }} disabled:opacity-60">
                <span wire:loading class="mr-2 inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                <i class="bi {{ $type === 'check_in' ? 'bi-check2-circle' : 'bi-send-check' }} mr-1.5" wire:loading.remove></i>
                <span>{{ $type === 'check_in' ? __('fleet.inspection.submit_checkin') : __('fleet.inspection.submit_checkout') }}</span>
            </button>
        </div>
    </form>

    {{-- Universal Photo Lightbox --}}
    <x-universal-lightbox />
</div>
