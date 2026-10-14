{{-- ========================================================================= --}}
{{-- P2H WIZARD: STEP 2 - CHECKLIST FISIK 4 ZONA (DIRECT & ALWAYS VISIBLE)     --}}
{{-- ========================================================================= --}}
@php
    $pointIcons = [
        'headlights' => 'bi-brightness-high',
        'body' => 'bi-car-front',
        'brake_lights' => 'bi-shield-exclamation',
        'turn_signals' => 'bi-arrow-left-right',
        'tires' => 'bi-disc',
        'battery_fuel' => 'bi-fuel-pump',
        'interior' => 'bi-box-seam',
    ];
@endphp

<div class="space-y-4">
    {{-- Header with Quick-Pass Action & Readiness Score --}}
    <div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs flex items-center justify-between gap-3">
        <div>
            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                <i class="bi bi-shield-check text-indigo-600 text-lg"></i>
                {{ __('fleet.inspection.physical_checklist_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5 font-medium">
                {{ __('fleet.inspection.physical_checklist_desc') }}
            </p>
        </div>

        {{-- Quick Pass Shortcut with Haptic Feedback --}}
        <button type="button"
            @click="if (navigator.vibrate) navigator.vibrate(30); $wire.passAllChecklist()"
            class="min-h-[44px] inline-flex items-center gap-2 rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-2 text-xs sm:text-sm font-black text-emerald-800 hover:bg-emerald-100 transition cursor-pointer shadow-2xs shrink-0 active:scale-95">
            <i class="bi bi-check-all text-lg"></i>
            <span>{{ __('fleet.inspection.all_ok') }}</span>
        </button>
    </div>

    {{-- 4 Zones Checklist Single Column Feed --}}
    <div class="space-y-4">
        @foreach ($this->zones as $zKey => $zone)
            <div class="rounded-3xl border border-slate-200/80 bg-slate-50/60 p-4 sm:p-5 shadow-xs space-y-3.5">
                {{-- Zone Header --}}
                <div class="flex items-center gap-2.5 border-b border-slate-200/80 pb-2.5">
                    <span class="h-7 w-7 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold shrink-0">
                        <i class="bi {{ $zone['icon'] }} text-xs"></i>
                    </span>
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-700">
                        {{ $zone['title'] }}
                    </h3>
                </div>

                {{-- Checklist Items within Zone --}}
                <div class="space-y-3">
                    @foreach ($zone['keys'] as $itemKey)
                        @php
                            $item = $checklist[$itemKey] ?? [];
                            $isOk = ($item['status'] ?? 'ok') === 'ok';
                            $hasPhotos = !empty($point_photos[$itemKey]);
                            $hasNotes = !empty($item['notes']);
                            $icon = $pointIcons[$itemKey] ?? 'bi-check2-circle';
                        @endphp

                        <div wire:key="checklist-item-{{ $itemKey }}"
                            x-data="{ showOptional: {{ ($hasNotes || $hasPhotos) ? 'true' : 'false' }} }"
                            class="rounded-2xl border transition p-4 sm:p-4.5
                            {{ !$isOk ? 'border-rose-300 bg-rose-50/30 ring-2 ring-rose-200 shadow-xs' : 'border-slate-200/90 bg-white hover:border-slate-300 shadow-2xs' }}">

                            {{-- Line 1: Item Identification & Status Touch-Friendly Switches (min 44px - 48px) --}}
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="h-9 w-9 rounded-xl flex items-center justify-center text-base shrink-0
                                        {{ $isOk ? 'bg-slate-100 text-slate-600' : 'bg-rose-100 text-rose-700' }}">
                                        <i class="bi {{ $icon }}"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-bold truncate {{ $isOk ? 'text-slate-900' : 'text-rose-950 font-black' }}">
                                            {{ $item['label'] ?? ucfirst($itemKey) }}
                                        </h4>
                                        @if (!$isOk)
                                            <span class="text-[11px] font-bold text-rose-600 flex items-center gap-1 mt-0.5">
                                                <i class="bi bi-exclamation-triangle-fill"></i> {{ __('fleet.inspection.issue_reported') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Status Switch (Fat-Finger Friendly min 44px - 48px buttons) --}}
                                <div class="inline-flex rounded-2xl p-1 bg-slate-100 border border-slate-200 shrink-0 shadow-2xs">
                                    <button type="button"
                                        @click="if (navigator.vibrate) navigator.vibrate(25); $wire.setChecklistStatus('{{ $itemKey }}', 'ok')"
                                        class="min-h-[44px] min-w-[60px] sm:min-w-[70px] px-3.5 py-2 rounded-xl text-xs sm:text-sm font-black transition cursor-pointer flex items-center justify-center gap-1
                                        {{ $isOk ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                                        <i class="bi bi-check-lg" x-show="{{ $isOk ? 'true' : 'false' }}"></i>
                                        <span>{{ __('fleet.inspection.btn_ok') }}</span>
                                    </button>
                                    <button type="button"
                                        @click="if (navigator.vibrate) navigator.vibrate(30); $wire.setChecklistStatus('{{ $itemKey }}', 'issue'); showOptional = true"
                                        class="min-h-[44px] min-w-[75px] sm:min-w-[85px] px-3.5 py-2 rounded-xl text-xs sm:text-sm font-black transition cursor-pointer flex items-center justify-center gap-1
                                        {{ !$isOk ? 'bg-rose-600 text-white shadow-2xs' : 'text-slate-600 hover:text-rose-700' }}">
                                        <i class="bi bi-exclamation-circle" x-show="{{ !$isOk ? 'true' : 'false' }}"></i>
                                        <span>{{ __('fleet.inspection.btn_issue') }}</span>
                                    </button>
                                </div>
                            </div>

                            {{-- Optional Notes Trigger for OK items (Progressive Disclosure) --}}
                            @if ($isOk)
                                <div x-show="!showOptional" class="mt-2 text-right">
                                    <button type="button" @click="showOptional = true"
                                        class="text-xs font-bold text-slate-500 hover:text-indigo-600 transition py-1 px-2 inline-flex items-center gap-1.5 cursor-pointer">
                                        <i class="bi bi-chat-left-text"></i>
                                        <span>{{ __('fleet.inspection.add_optional_notes_photo') }}</span>
                                    </button>
                                </div>
                            @endif

                            {{-- Progressive Disclosure: Notes & Photo Attachment (Reveals automatically on 'issue' or when expanded) --}}
                            <div x-show="{{ !$isOk ? 'true' : 'false' }} || showOptional" x-cloak x-collapse
                                class="mt-3 pt-3 border-t {{ $isOk ? 'border-slate-100' : 'border-rose-200' }} space-y-2.5">
                                <div>
                                    <label class="block text-xs font-bold {{ $isOk ? 'text-slate-700' : 'text-rose-900' }} mb-1">
                                        {{ $isOk ? __('fleet.inspection.optional_notes_label') : __('fleet.inspection.issue_details_label') }}
                                    </label>
                                    <input type="text" wire:model.defer="checklist.{{ $itemKey }}.notes"
                                        placeholder="{{ $isOk ? __('fleet.inspection.optional_notes_placeholder') : __('fleet.inspection.issue_details_placeholder') }}"
                                        class="w-full min-h-[44px] rounded-xl border {{ $isOk ? 'border-slate-200 bg-slate-50/70 text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-slate-400' : 'border-rose-300 bg-white text-rose-950 placeholder:text-rose-400 focus:border-rose-500 ring-1 ring-rose-200' }} px-3.5 py-2 text-sm focus:outline-none transition">
                                </div>

                                {{-- Photo Attachment Controls (Touch-Friendly) --}}
                                <div class="flex items-center justify-between gap-2 pt-0.5">
                                    <label class="min-h-[40px] cursor-pointer inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 shadow-2xs transition active:scale-95">
                                        <i class="bi bi-camera text-indigo-600 text-sm"></i>
                                        <span>{{ __('fleet.inspection.upload_evidence_photo') }}</span>
                                        <input type="file" wire:model="point_photos.{{ $itemKey }}" multiple accept="image/*" class="sr-only">
                                    </label>

                                    <div wire:loading wire:target="point_photos.{{ $itemKey }}" class="text-xs text-indigo-600 font-bold">
                                        <i class="bi bi-arrow-repeat animate-spin mr-1"></i> {{ __('fleet.common.uploading') }}
                                    </div>

                                    @if (!empty($point_photos[$itemKey]))
                                        <span class="text-xs font-black text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-lg">
                                            {{ __('fleet.inspection.photos_attached', ['count' => count($point_photos[$itemKey])]) }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Photo Previews with Lightbox Trigger & Remove Button --}}
                                @if (!empty($point_photos[$itemKey]))
                                    <div class="flex flex-wrap items-center gap-2 pt-1.5">
                                        @foreach ($point_photos[$itemKey] as $idx => $p)
                                            <div class="relative group">
                                                @if (method_exists($p, 'temporaryUrl'))
                                                    <img src="{{ $p->temporaryUrl() }}"
                                                        @click="$dispatch('open-lightbox', { src: '{{ $p->temporaryUrl() }}', title: '{{ __('fleet.inspection.finding_photo_title', ['point' => addslashes($item['label'] ?? '')]) }}' })"
                                                        class="h-14 w-14 object-cover rounded-2xl border border-slate-200 shadow-2xs cursor-pointer hover:opacity-90 transition">
                                                @else
                                                    <div class="h-14 w-14 bg-slate-100 rounded-2xl flex items-center justify-center text-xs text-slate-500 font-bold border border-slate-200">#{{ $idx+1 }}</div>
                                                @endif
                                                <button type="button" wire:click="removePointPhoto('{{ $itemKey }}', {{ $idx }})" title="{{ __('fleet.inspection.remove_photo') }}"
                                                    class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full h-5 w-5 flex items-center justify-center text-xs font-black hover:bg-rose-700 shadow-xs cursor-pointer">
                                                    ×
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Bottom Navigation Bar for Step 2 (Touch Target 48px Friendly) --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs flex items-center justify-between gap-3">
        <button type="button" wire:click="previousStep"
            class="min-h-[48px] inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
            <i class="bi bi-arrow-left text-sm"></i>
            <span>{{ __('fleet.common.back') }}</span>
        </button>

        <button type="button" wire:click="nextStep"
            class="min-h-[48px] inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-7 py-3 text-sm font-black text-white hover:bg-slate-800 transition cursor-pointer shadow-xs active:scale-[0.98]">
            <span>{{ __('fleet.inspection.next_to_summary') }}</span>
            <i class="bi bi-arrow-right text-base"></i>
        </button>
    </div>
</div>
