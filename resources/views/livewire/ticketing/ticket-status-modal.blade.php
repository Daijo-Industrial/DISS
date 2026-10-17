<div x-data="{ isOpen: @entangle('isOpen') }" x-show="isOpen" @keydown.escape.window="isOpen = false" class="relative z-[100]"
    aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
    <!-- Background backdrop -->
    <div x-show="isOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <!-- Modal panel -->
            <div x-show="isOpen" @click.away="isOpen = false" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-100">

                <div class="bg-white px-5 pb-5 pt-6 sm:p-6 sm:pb-5">
                    <div class="text-left w-full space-y-5">
                        <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 tracking-tight" id="modal-title">
                                    {{ __('ticketing.modal.title') }}
                                </h3>
                                <p class="text-xs text-slate-500 font-medium mt-0.5">
                                    {{ __('ticketing.modal.subtitle') }}
                                </p>
                            </div>
                            <button type="button" @click="isOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {{-- Status Selection with Visual Cards --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                {{ __('ticketing.modal.new_status') }}
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach ($statuses as $status)
                                    @php
                                        $sKey = strtolower(str_replace(' ', '_', $status->value));
                                        $sLabel = __('ticketing.status.' . $sKey);
                                        $displayLabel = $sLabel !== 'ticketing.status.' . $sKey ? $sLabel : $status->value;
                                    @endphp
                                    <button type="button" wire:click="$set('newStatus', '{{ $status->value }}')"
                                        class="flex items-center gap-2 p-2.5 rounded-xl border text-xs font-bold text-left transition-all cursor-pointer {{ $newStatus === $status->value ? 'border-indigo-600 bg-indigo-50/70 text-indigo-900 ring-2 ring-indigo-500/20 shadow-xs' : 'border-slate-200 bg-slate-50/60 text-slate-600 hover:bg-slate-100' }}">
                                        @if ($status->value === 'Open')
                                            <span class="h-2 w-2 rounded-full bg-blue-500 shrink-0"></span>
                                        @elseif($status->value === 'In Progress')
                                            <span class="h-2 w-2 rounded-full bg-emerald-500 shrink-0"></span>
                                        @elseif($status->value === 'On Hold')
                                            <span class="h-2 w-2 rounded-full bg-amber-500 shrink-0"></span>
                                        @elseif($status->value === 'Resolved')
                                            <span class="h-2 w-2 rounded-full bg-indigo-500 shrink-0"></span>
                                        @else
                                            <span class="h-2 w-2 rounded-full bg-slate-400 shrink-0"></span>
                                        @endif
                                        <span class="truncate">{{ $displayLabel }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Contextual Reason / Resolution Box --}}
                        <div x-show="$wire.newStatus === 'On Hold'" x-collapse>
                            <div class="rounded-2xl bg-amber-50/70 border border-amber-200 p-4 space-y-2">
                                <div class="flex items-center gap-1.5 text-xs font-bold text-amber-800">
                                    <svg class="h-4 w-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span>{{ __('ticketing.modal.reason_hold_label') }} <span class="text-rose-500">*</span></span>
                                </div>
                                <p class="text-[11px] text-amber-700 font-medium">{{ __('ticketing.modal.sla_paused_notice') }}</p>
                                <textarea wire:model="reason" rows="3"
                                    placeholder="{{ __('ticketing.modal.reason_hold_placeholder') }}"
                                    class="block w-full rounded-xl border border-amber-200 py-2 px-3 text-xs text-slate-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs"></textarea>
                                @error('reason')
                                    <span class="text-xs font-bold text-rose-500 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div x-show="$wire.newStatus === 'Resolved'" x-collapse>
                            <div class="rounded-2xl bg-emerald-50/70 border border-emerald-200 p-4 space-y-2">
                                <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-800">
                                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ __('ticketing.modal.resolution_label') }}</span>
                                </div>
                                <p class="text-[11px] text-emerald-700 font-medium">{{ __('ticketing.modal.resolution_hint') }}</p>
                                <textarea wire:model="reason" rows="3"
                                    placeholder="{{ __('ticketing.modal.resolution_placeholder') }}"
                                    class="block w-full rounded-xl border border-emerald-200 py-2 px-3 text-xs text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 bg-white shadow-xs"></textarea>
                                @error('reason')
                                    <span class="text-xs font-bold text-rose-500 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>

                <div class="bg-slate-50 border-t border-slate-100 px-5 py-3.5 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                    <button type="button" wire:click="save"
                        class="inline-flex w-full justify-center rounded-xl bg-indigo-600 px-4 py-2 text-xs sm:text-sm font-bold text-white shadow-md shadow-indigo-200 hover:bg-indigo-700 sm:w-auto transition-colors cursor-pointer">
                        {{ __('ticketing.modal.confirm') }}
                    </button>
                    <button type="button" @click="isOpen = false"
                        class="mt-2 sm:mt-0 inline-flex w-full justify-center rounded-xl bg-white px-4 py-2 text-xs sm:text-sm font-bold text-slate-600 shadow-xs ring-1 ring-inset ring-slate-200 hover:bg-slate-50 sm:w-auto transition-colors cursor-pointer">
                        {{ __('ticketing.modal.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
