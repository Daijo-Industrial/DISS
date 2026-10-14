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
        <div class="inline-flex rounded-2xl bg-slate-100 p-1 border border-slate-200/80 self-start sm:self-auto shadow-2xs">
            <button type="button" wire:click="switchType('check_out')"
                class="min-h-[44px] rounded-xl px-4 py-2 text-xs sm:text-sm font-black transition cursor-pointer flex items-center gap-1.5 {{ $type === 'check_out' ? 'bg-white text-emerald-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="bi bi-box-arrow-up-right text-sm"></i> <span>Check-out</span>
            </button>
            <button type="button" wire:click="switchType('check_in')"
                class="min-h-[44px] rounded-xl px-4 py-2 text-xs sm:text-sm font-black transition cursor-pointer flex items-center gap-1.5 {{ $type === 'check_in' ? 'bg-white text-amber-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="bi bi-box-arrow-in-down text-sm"></i> <span>Check-in</span>
            </button>
        </div>
    </div>

    {{-- Vehicle Cockpit Banner & Stepper Container --}}
    <div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-6 shadow-xs space-y-4">
        {{-- Vehicle Identity & Quick Gauge --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3.5">
                @if ($vehicle->image_path)
                    <button type="button" @click="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $vehicle->image_path) }}', title: 'Foto Profil Armada {{ $vehicle->plate_number }}', subtitle: '{{ trim($vehicle->brand . ' ' . $vehicle->model) }}' })"
                        class="cursor-pointer shrink-0" title="Perbesar foto">
                        <img src="{{ asset('storage/' . $vehicle->image_path) }}" alt="{{ $vehicle->plate_number }}"
                            class="h-14 w-14 rounded-2xl object-cover border border-slate-200 shadow-2xs hover:opacity-90 transition">
                    </button>
                @else
                    <div class="h-14 w-14 rounded-2xl flex items-center justify-center text-2xl shadow-2xs shrink-0
                        {{ $vehicle->category === 'commercial_truck' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                        <i class="bi {{ $vehicle->category === 'commercial_truck' ? 'bi-truck' : 'bi-car-front' }}"></i>
                    </div>
                @endif

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-lg sm:text-xl font-black tracking-tight text-slate-900 font-mono">
                            {{ $vehicle->plate_number }}
                        </span>
                        <span class="rounded-lg bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700">
                            {{ $vehicle->category_label }}
                        </span>
                        <span class="rounded-lg bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700">
                            {{ $vehicle->fuel_type_label }}
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-600 mt-0.5 font-medium">
                        {{ trim($vehicle->brand . ' ' . $vehicle->model) }} • Odometer: <strong class="text-slate-900 font-mono">{{ number_format($vehicle->odometer) }} km</strong>
                    </p>
                </div>
            </div>

            {{-- Readiness Indicator in Header --}}
            <div class="flex items-center gap-3 bg-slate-50 border border-slate-200/80 rounded-2xl px-3.5 py-2 self-start sm:self-auto">
                <div class="text-right">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">{{ __('fleet.inspection.readiness_label') }}</span>
                    <span class="text-xs sm:text-sm font-black {{ $this->okCount === $this->totalItems ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ __('fleet.inspection.points_ok', ['ready' => $this->okCount, 'total' => $this->totalItems]) }}
                    </span>
                </div>
                <span class="h-9 w-9 rounded-full border-2 {{ $this->okCount === $this->totalItems ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-amber-500 bg-amber-50 text-amber-700' }} flex items-center justify-center text-xs font-black">
                    {{ $this->readinessPercent }}%
                </span>
            </div>
        </div>

        {{-- Check-in Context Banner (if returning from trip) --}}
        @if ($type === 'check_in' && $parentInspection)
            <div class="rounded-2xl bg-amber-50/80 border border-amber-200 p-3 text-xs sm:text-sm text-amber-950 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <i class="bi bi-clock-history text-amber-600 text-base"></i>
                    <span>
                        {{ __('fleet.inspection.type_checkout') }}: <strong>{{ ($parentInspection->checked_at ?? $parentInspection->created_at)->isoFormat('DD MMM YYYY, HH:mm') }}</strong>
                        {{ __('fleet.inspection.checkout_by_driver', ['driver' => $parentInspection->driver_name, 'km' => number_format($parentInspection->odometer)]) }}
                    </span>
                </div>
                @if ($odometer > $parentInspection->odometer)
                    <span class="rounded-xl bg-amber-200/90 px-2.5 py-1 font-black text-amber-950 text-xs">
                        {{ __('fleet.inspection.trip_distance') }}: +{{ number_format($odometer - $parentInspection->odometer) }} km
                    </span>
                @endif
            </div>
        @endif

        {{-- 3-STEP WIZARD PROGRESS BAR (Touch Target 48px Friendly) --}}
        <div class="pt-2 border-t border-slate-100">
            <div class="grid grid-cols-3 gap-2 sm:gap-4">
                {{-- Step 1 --}}
                <button type="button" wire:click="goToStep(1)"
                    class="min-h-[48px] flex items-center gap-2 sm:gap-2.5 p-2 sm:p-2.5 rounded-2xl text-left transition cursor-pointer
                    {{ $currentStep === 1 ? 'bg-slate-900 text-white shadow-xs' : ($currentStep > 1 ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-slate-50 text-slate-400') }}">
                    <span class="h-7 w-7 rounded-xl flex items-center justify-center text-xs font-black shrink-0
                        {{ $currentStep === 1 ? 'bg-white text-slate-900' : ($currentStep > 1 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600') }}">
                        @if ($currentStep > 1)
                            <i class="bi bi-check-lg"></i>
                        @else
                            1
                        @endif
                    </span>
                    <span class="text-xs sm:text-sm font-bold truncate">{{ __('fleet.inspection.step_1_title') }}</span>
                </button>

                {{-- Step 2 --}}
                <button type="button" wire:click="goToStep(2)"
                    class="min-h-[48px] flex items-center gap-2 sm:gap-2.5 p-2 sm:p-2.5 rounded-2xl text-left transition cursor-pointer
                    {{ $currentStep === 2 ? 'bg-slate-900 text-white shadow-xs' : ($currentStep > 2 ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-slate-50 text-slate-400') }}">
                    <span class="h-7 w-7 rounded-xl flex items-center justify-center text-xs font-black shrink-0
                        {{ $currentStep === 2 ? 'bg-white text-slate-900' : ($currentStep > 2 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600') }}">
                        @if ($currentStep > 2)
                            <i class="bi bi-check-lg"></i>
                        @else
                            2
                        @endif
                    </span>
                    <span class="text-xs sm:text-sm font-bold truncate">{{ __('fleet.inspection.step_2_title') }}</span>
                </button>

                {{-- Step 3 --}}
                <button type="button" wire:click="goToStep(3)"
                    class="min-h-[48px] flex items-center gap-2 sm:gap-2.5 p-2 sm:p-2.5 rounded-2xl text-left transition cursor-pointer
                    {{ $currentStep === 3 ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-400' }}">
                    <span class="h-7 w-7 rounded-xl flex items-center justify-center text-xs font-black shrink-0
                        {{ $currentStep === 3 ? 'bg-white text-slate-900' : 'bg-slate-200 text-slate-600' }}">
                        3
                    </span>
                    <span class="text-xs sm:text-sm font-bold truncate">{{ __('fleet.inspection.step_3_title') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
