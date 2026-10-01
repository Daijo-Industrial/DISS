{{-- ========================================================================= --}}
{{-- P2H WIZARD: STEPPER & VEHICLE HEADER                                      --}}
{{-- ========================================================================= --}}
<div class="space-y-4">
    {{-- Breadcrumb & Mode Switcher Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <nav class="text-xs text-slate-500" aria-label="Breadcrumb">
            <ol class="flex items-center gap-1.5">
                <li>
                    <a href="{{ route('vehicles.index') }}" class="hover:text-slate-800 transition">
                        {{ __('fleet.common.fleet') }}
                    </a>
                </li>
                <li class="text-slate-300">/</li>
                <li>
                    <a href="{{ route('vehicles.show', $vehicle) }}" class="hover:text-slate-800 transition">
                        {{ $vehicle->plate_number }}
                    </a>
                </li>
                <li class="text-slate-300">/</li>
                <li class="font-bold text-slate-800">
                    {{ $type === 'check_in' ? __('fleet.inspection.type_checkin') : __('fleet.inspection.type_checkout') }}
                </li>
            </ol>
        </nav>

        {{-- Mode Switcher Pill (Check-out vs Check-in) --}}
        <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200/80 self-start sm:self-auto shadow-2xs">
            <button type="button" wire:click="switchType('check_out')"
                class="rounded-lg px-3 py-1.5 text-xs font-bold transition cursor-pointer {{ $type === 'check_out' ? 'bg-white text-emerald-700 shadow-2xs' : 'text-slate-500 hover:text-slate-800' }}">
                <i class="bi bi-box-arrow-up-right mr-1"></i> Check-out
            </button>
            <button type="button" wire:click="switchType('check_in')"
                class="rounded-lg px-3 py-1.5 text-xs font-bold transition cursor-pointer {{ $type === 'check_in' ? 'bg-white text-amber-700 shadow-2xs' : 'text-slate-500 hover:text-slate-800' }}">
                <i class="bi bi-box-arrow-in-down mr-1"></i> Check-in
            </button>
        </div>
    </div>

    {{-- Vehicle Cockpit Banner & Stepper Container --}}
    <div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs space-y-4">
        {{-- Vehicle Identity & Quick Gauge --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                @if ($vehicle->image_path)
                    <button type="button" @click="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $vehicle->image_path) }}', title: 'Foto Profil Armada {{ $vehicle->plate_number }}', subtitle: '{{ trim($vehicle->brand . ' ' . $vehicle->model) }}' })"
                        class="cursor-pointer shrink-0" title="Perbesar foto">
                        <img src="{{ asset('storage/' . $vehicle->image_path) }}" alt="{{ $vehicle->plate_number }}"
                            class="h-12 w-12 rounded-2xl object-cover border border-slate-200 shadow-2xs hover:opacity-90 transition">
                    </button>
                @else
                    <div class="h-12 w-12 rounded-2xl flex items-center justify-center text-xl shadow-2xs shrink-0
                        {{ $vehicle->category === 'commercial_truck' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                        <i class="bi {{ $vehicle->category === 'commercial_truck' ? 'bi-truck' : 'bi-car-front' }}"></i>
                    </div>
                @endif

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-base sm:text-lg font-black tracking-tight text-slate-900 font-mono">
                            {{ $vehicle->plate_number }}
                        </span>
                        <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                            {{ $vehicle->category_label }}
                        </span>
                        <span class="rounded-lg bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
                            {{ $vehicle->fuel_type_label }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ trim($vehicle->brand . ' ' . $vehicle->model) }} • Odometer Saat Ini: <strong>{{ number_format($vehicle->odometer) }} km</strong>
                    </p>
                </div>
            </div>

            {{-- Readiness Indicator in Header --}}
            <div class="flex items-center gap-2.5 bg-slate-50 border border-slate-200/70 rounded-2xl px-3 py-1.5 self-start sm:self-auto">
                <div class="text-right">
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block">{{ __('fleet.inspection.readiness_label') }}</span>
                    <span class="text-xs font-black {{ $this->okCount === $this->totalItems ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $this->okCount }}/{{ $this->totalItems }} Poin OK
                    </span>
                </div>
                <span class="h-7 w-7 rounded-full border-2 {{ $this->okCount === $this->totalItems ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-amber-500 bg-amber-50 text-amber-700' }} flex items-center justify-center text-[10px] font-black">
                    {{ $this->readinessPercent }}%
                </span>
            </div>
        </div>

        {{-- Check-in Context Banner (if returning from trip) --}}
        @if ($type === 'check_in' && $parentInspection)
            <div class="rounded-xl bg-amber-50/70 border border-amber-200/70 p-2.5 text-xs text-amber-900 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <i class="bi bi-clock-history text-amber-600 text-sm"></i>
                    <span>
                        {{ __('fleet.inspection.type_checkout') }}: <strong>{{ $parentInspection->created_at->isoFormat('DD MMM YYYY, HH:mm') }}</strong>
                        oleh Driver <strong>{{ $parentInspection->driver_name }}</strong> (KM Awal: <strong>{{ number_format($parentInspection->odometer) }} km</strong>)
                    </span>
                </div>
                @if ($odometer > $parentInspection->odometer)
                    <span class="rounded-lg bg-amber-200/80 px-2 py-0.5 font-bold text-amber-950 text-[11px]">
                        {{ __('fleet.inspection.trip_distance') }}: +{{ number_format($odometer - $parentInspection->odometer) }} km
                    </span>
                @endif
            </div>
        @endif

        {{-- 3-STEP WIZARD PROGRESS BAR --}}
        <div class="pt-2 border-t border-slate-100">
            <div class="grid grid-cols-3 gap-2 sm:gap-4">
                {{-- Step 1 --}}
                <button type="button" wire:click="goToStep(1)"
                    class="flex items-center gap-2 p-2 rounded-xl text-left transition cursor-pointer
                    {{ $currentStep === 1 ? 'bg-slate-900 text-white shadow-xs' : ($currentStep > 1 ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-slate-50 text-slate-400') }}">
                    <span class="h-6 w-6 rounded-lg flex items-center justify-center text-xs font-bold shrink-0
                        {{ $currentStep === 1 ? 'bg-white text-slate-900' : ($currentStep > 1 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600') }}">
                        @if ($currentStep > 1)
                            <i class="bi bi-check-lg"></i>
                        @else
                            1
                        @endif
                    </span>
                    <span class="text-xs font-bold truncate">1. Telemetri</span>
                </button>

                {{-- Step 2 --}}
                <button type="button" wire:click="goToStep(2)"
                    class="flex items-center gap-2 p-2 rounded-xl text-left transition cursor-pointer
                    {{ $currentStep === 2 ? 'bg-slate-900 text-white shadow-xs' : ($currentStep > 2 ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-slate-50 text-slate-400') }}">
                    <span class="h-6 w-6 rounded-lg flex items-center justify-center text-xs font-bold shrink-0
                        {{ $currentStep === 2 ? 'bg-white text-slate-900' : ($currentStep > 2 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600') }}">
                        @if ($currentStep > 2)
                            <i class="bi bi-check-lg"></i>
                        @else
                            2
                        @endif
                    </span>
                    <span class="text-xs font-bold truncate">2. Fisik (7 Titik)</span>
                </button>

                {{-- Step 3 --}}
                <button type="button" wire:click="goToStep(3)"
                    class="flex items-center gap-2 p-2 rounded-xl text-left transition cursor-pointer
                    {{ $currentStep === 3 ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-400' }}">
                    <span class="h-6 w-6 rounded-lg flex items-center justify-center text-xs font-bold shrink-0
                        {{ $currentStep === 3 ? 'bg-white text-slate-900' : 'bg-slate-200 text-slate-600' }}">
                        3
                    </span>
                    <span class="text-xs font-bold truncate">3. Ringkasan</span>
                </button>
            </div>
        </div>
    </div>
</div>
