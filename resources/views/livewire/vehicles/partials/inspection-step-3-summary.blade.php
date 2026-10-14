{{-- ========================================================================= --}}
{{-- P2H WIZARD: STEP 3 - RINGKASAN, PENILAIAN KERUSAKAN & KONFIRMASI          --}}
{{-- ========================================================================= --}}
@php
    $defects = $this->defectiveItems;
    $hasDefect = !empty($defects) || $severity !== 'none';
@endphp

<div class="space-y-6">
    {{-- Verdict Banner --}}
    @if (!$hasDefect)
        <div class="rounded-3xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50/50 p-5 sm:p-7 shadow-xs">
            <div class="flex items-start gap-4">
                <span class="h-14 w-14 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-3xl shadow-xs shrink-0">
                    <i class="bi bi-shield-fill-check"></i>
                </span>
                <div>
                    <span class="rounded-xl bg-emerald-100 text-emerald-800 text-xs font-black uppercase tracking-wider px-2.5 py-1">
                        {{ __('fleet.inspection.verdict_fit_badge') }}
                    </span>
                    <h2 class="text-lg sm:text-xl font-black text-emerald-950 mt-1.5">
                        {{ __('fleet.inspection.verdict_fit_title') }}
                    </h2>
                    <p class="text-xs sm:text-sm text-emerald-800/90 mt-1 font-medium">
                        {{ __('fleet.inspection.verdict_fit_desc') }}
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-3xl border border-amber-300 bg-gradient-to-r from-amber-50 to-orange-50/60 p-5 sm:p-7 shadow-xs">
            <div class="flex items-start gap-4">
                <span class="h-14 w-14 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-3xl shadow-xs shrink-0">
                    <i class="bi bi-info-circle-fill"></i>
                </span>
                <div>
                    <span class="rounded-xl bg-amber-100 text-amber-900 text-xs font-black uppercase tracking-wider px-2.5 py-1">
                        {{ __('fleet.inspection.verdict_issue_badge', ['count' => count($defects)]) }}
                    </span>
                    <h2 class="text-lg sm:text-xl font-black text-amber-950 mt-1.5">
                        {{ __('fleet.inspection.verdict_issue_title') }}
                    </h2>
                    <p class="text-xs sm:text-sm text-amber-900/90 mt-1 font-medium">
                        {{ __('fleet.inspection.verdict_issue_desc') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Defect Handling Section (Zero-Friction: Default Minor, Grounding Option Tucked Away) --}}
    @if ($hasDefect)
        <div x-data="{ showGroundingOption: {{ $severity === 'critical_grounded' ? 'true' : 'false' }} }"
            class="rounded-3xl border border-amber-200/90 bg-white p-5 sm:p-7 shadow-xs space-y-5">

            {{-- Summary of Flagged Checklist Issues --}}
            @if (!empty($defects))
                <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-4 sm:p-5 space-y-3">
                    <span class="text-xs sm:text-sm font-black text-amber-950 block">{{ __('fleet.inspection.flagged_issues_title') }}</span>
                    <div class="space-y-2.5">
                        @foreach ($defects as $dKey => $dItem)
                            <div class="rounded-xl border border-rose-200 bg-white p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs sm:text-sm shadow-2xs">
                                <div>
                                    <span class="font-black text-rose-950">{{ $dItem['label'] ?? ucfirst($dKey) }}:</span>
                                    <span class="text-slate-700 ml-1 font-medium">{{ $dItem['notes'] ?: __('fleet.inspection.no_written_notes') }}</span>
                                </div>

                                {{-- Photos attached to this point --}}
                                @if (!empty($point_photos[$dKey]))
                                    <div class="flex items-center gap-2 shrink-0">
                                        @foreach ($point_photos[$dKey] as $p)
                                            @if (method_exists($p, 'temporaryUrl'))
                                                <img src="{{ $p->temporaryUrl() }}"
                                                    @click="$dispatch('open-lightbox', { src: '{{ $p->temporaryUrl() }}', title: '{{ __('fleet.inspection.finding_photo_title', ['point' => addslashes($dItem['label'] ?? '')]) }}' })"
                                                    class="h-11 w-11 object-cover rounded-xl border border-slate-200 cursor-pointer shadow-2xs hover:opacity-80 transition">
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Critical Grounding Toggle (Only opened for serious breakdowns, default hidden) --}}
            <div class="pt-2 border-t border-slate-100 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full {{ $severity === 'critical_grounded' ? 'bg-rose-500 animate-pulse' : 'bg-amber-500' }}"></span>
                        <span class="text-xs sm:text-sm font-bold text-slate-800">
                            {{ __('fleet.inspection.current_classification') }} <strong class="{{ $severity === 'critical_grounded' ? 'text-rose-700 font-black' : 'text-amber-700 font-black' }}">{{ $severity === 'critical_grounded' ? __('fleet.inspection.classification_critical') : __('fleet.inspection.classification_minor') }}</strong>
                        </span>
                    </div>

                    <button type="button" @click="showGroundingOption = !showGroundingOption"
                        class="text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200/80 rounded-xl px-3 py-1.5 transition cursor-pointer">
                        <span x-text="showGroundingOption ? @js(__('fleet.inspection.toggle_close_classification')) : @js(__('fleet.inspection.toggle_open_classification'))"></span>
                    </button>
                </div>

                {{-- Collapsible Grounding Severity Switcher --}}
                <div x-show="showGroundingOption" x-cloak x-collapse class="pt-2">
                    <div class="rounded-2xl border border-rose-200 bg-rose-50/30 p-4 space-y-3">
                        <p class="text-xs text-rose-900 font-bold">
                            {{ __('fleet.inspection.classification_helper_text') }}
                        </p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            {{-- Minor --}}
                            <label class="min-h-[56px] flex cursor-pointer rounded-2xl border p-3.5 transition items-center
                                {{ $severity === 'minor' ? 'border-amber-500 bg-amber-50/70 ring-2 ring-amber-300 shadow-2xs' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                                <input type="radio" wire:model.live="severity" value="minor" class="sr-only">
                                <div class="flex items-center gap-3">
                                    <span class="h-8 w-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 text-base">
                                        <i class="bi bi-info-circle"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-xs sm:text-sm font-black text-slate-900">{{ __('fleet.inspection.severity_minor_title') }}</h4>
                                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">{{ __('fleet.inspection.severity_minor_desc') }}</p>
                                    </div>
                                </div>
                            </label>

                            {{-- Critical Grounded --}}
                            <label class="min-h-[56px] flex cursor-pointer rounded-2xl border p-3.5 transition items-center
                                {{ $severity === 'critical_grounded' ? 'border-rose-500 bg-rose-50 ring-2 ring-rose-300 shadow-2xs' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                                <input type="radio" wire:model.live="severity" value="critical_grounded" class="sr-only">
                                <div class="flex items-center gap-3">
                                    <span class="h-8 w-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 text-base">
                                        <i class="bi bi-slash-circle font-black"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-xs sm:text-sm font-black text-rose-950">{{ __('fleet.inspection.severity_critical_title') }}</h4>
                                        <p class="text-[11px] text-rose-700 font-medium mt-0.5">{{ __('fleet.inspection.severity_critical_desc') }}</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Optional Defect Notes & General Defect Photos --}}
            <div class="grid gap-4 sm:grid-cols-2 pt-2 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        {{ __('fleet.inspection.defect_report') }}
                        <span class="text-slate-400 font-normal">{{ __('fleet.inspection.optional_summary_notes') }}</span>
                    </label>
                    <textarea wire:model.defer="defect_notes" rows="3" placeholder="{{ __('fleet.inspection.defect_notes_placeholder') }}"
                        class="w-full rounded-2xl border border-slate-200 bg-white p-3 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none transition"></textarea>
                    @error('defect_notes')
                        <p class="mt-1 text-xs text-rose-600 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        {{ __('fleet.inspection.defect_photos') }}
                        <span class="text-slate-400 font-normal">{{ __('fleet.inspection.optional_summary_photos') }}</span>
                    </label>
                    <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-4 text-center">
                        <input type="file" wire:model="photos" multiple accept="image/*" class="text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 cursor-pointer">
                        <div wire:loading wire:target="photos" class="text-xs text-indigo-600 mt-2 font-bold">
                            <i class="bi bi-arrow-repeat animate-spin mr-1"></i> {{ __('fleet.inspection.uploading_photos') }}
                        </div>

                        @if (!empty($photos))
                            <div class="mt-3 flex flex-wrap gap-2 justify-center">
                                @foreach ($photos as $idx => $p)
                                    <div class="relative group">
                                        @if (method_exists($p, 'temporaryUrl'))
                                            <img src="{{ $p->temporaryUrl() }}" @click="$dispatch('open-lightbox', { src: '{{ $p->temporaryUrl() }}', title: @js(__('fleet.inspection.main_defect_photo_title')) })"
                                                class="h-12 w-12 object-cover rounded-xl border border-slate-200 shadow-2xs cursor-pointer hover:opacity-90 transition">
                                        @endif
                                        <button type="button" wire:click="removePhoto({{ $idx }})" title="{{ __('fleet.inspection.remove_photo') }}"
                                            class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full h-5 w-5 flex items-center justify-center text-xs font-black hover:bg-rose-700 shadow-2xs cursor-pointer">
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

    {{-- Telemetry Review Strip --}}
    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-xs sm:text-sm font-black uppercase tracking-wider text-slate-600 flex items-center gap-2">
                <i class="bi bi-card-checklist text-indigo-600 text-base"></i>
                {{ __('fleet.inspection.telemetry_review_title') }}
            </h3>
            <button type="button" wire:click="goToStep(1)" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition cursor-pointer">
                {{ __('fleet.inspection.edit_telemetry') }}
            </button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs sm:text-sm">
            <div class="rounded-2xl bg-slate-50/80 p-3.5 border border-slate-100">
                <span class="text-xs text-slate-500 block font-bold">{{ __('fleet.inspection.driver_col') }}</span>
                <span class="font-black text-slate-900 mt-1 block truncate">{{ $driver_name ?: '-' }}</span>
            </div>

            <div class="rounded-2xl bg-slate-50/80 p-3.5 border border-slate-100">
                <span class="text-xs text-slate-500 block font-bold">{{ __('fleet.inspection.inspector_col') }}</span>
                <span class="font-black text-slate-900 mt-1 block truncate">{{ $created_by ?: '-' }}</span>
            </div>

            <div class="rounded-2xl bg-slate-50/80 p-3.5 border border-slate-100">
                <span class="text-xs text-slate-500 block font-bold">{{ __('fleet.inspection.odometer_col') }}</span>
                <span class="font-black text-slate-900 font-mono mt-1 block">
                    {{ number_format((int) $odometer) }} km
                    @if ($type === 'check_in' && $parentInspection && (int) $odometer > (int) $parentInspection->odometer)
                        <span class="text-emerald-700 font-sans text-xs ml-1">(+{{ number_format((int) $odometer - (int) $parentInspection->odometer) }} km)</span>
                    @endif
                </span>
            </div>

            <div class="rounded-2xl bg-slate-50/80 p-3.5 border border-slate-100">
                <span class="text-xs text-slate-500 block font-bold">{{ __('fleet.inspection.fuel_level_col') }}</span>
                <span class="font-black text-slate-900 mt-1 block">{{ $fuel_percentage }}%</span>
            </div>

            <div class="rounded-2xl bg-slate-50/80 p-3.5 border border-slate-100 col-span-2 sm:col-span-1">
                <span class="text-xs text-slate-500 block font-bold">{{ __('fleet.inspection.physical_checklist_col') }}</span>
                <span class="font-black mt-1 block {{ $this->okCount === $this->totalItems ? 'text-emerald-700' : 'text-amber-700' }}">
                    {{ __('fleet.inspection.points_ok', ['ready' => $this->okCount, 'total' => $this->totalItems]) }}
                </span>
            </div>
        </div>

        @if ($trip_purpose)
            <div class="rounded-2xl bg-slate-50/80 p-3.5 border border-slate-100 text-xs sm:text-sm">
                <span class="text-xs text-slate-500 block font-bold">{{ __('fleet.inspection.trip_purpose_label') }}</span>
                <span class="text-slate-800 font-medium italic mt-1 block">"{{ $trip_purpose }}"</span>
            </div>
        @endif
    </div>

    {{-- Floating Submission Action Bar (Touch Target 52px Friendly) --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <button type="button" wire:click="previousStep"
            class="min-h-[48px] inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
            <i class="bi bi-arrow-left text-sm"></i>
            <span>{{ __('fleet.inspection.back_to_checklist') }}</span>
        </button>

        <button type="button"
            @click="if (navigator.vibrate) navigator.vibrate([40, 60, 40]); $wire.save()"
            wire:loading.attr="disabled"
            class="min-h-[52px] w-full sm:w-auto inline-flex items-center justify-center gap-2.5 rounded-2xl px-8 py-3.5 text-base font-black text-white shadow-xs transition cursor-pointer active:scale-[0.98]
            {{ $type === 'check_in' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700' }} disabled:opacity-60">
            <span wire:loading class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
            <i class="bi {{ $type === 'check_in' ? 'bi-check2-circle' : 'bi-send-check' }} text-lg" wire:loading.remove></i>
            <span>{{ $type === 'check_in' ? __('fleet.inspection.submit_checkin') : __('fleet.inspection.submit_checkout') }}</span>
        </button>
    </div>
</div>
