<div x-data="{
        open: @entangle('isOpen').live,
        toggle() {
            this.open = !this.open;
            if (this.open && !$wire.current_url) {
                $wire.set('current_url', window.location.href);
            }
        },
        close() {
            this.open = false;
        }
    }"
    x-show="typeof sidebarOpen === 'undefined' || !sidebarOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-90"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-90"
    @click.outside="close()"
    @keydown.escape.window="close()"
    @open-support-bubble.window="$nextTick(() => { open = true; if (!$wire.current_url) { $wire.set('current_url', window.location.href); } })"
    class="fixed bottom-6 right-6 z-[100]"
    x-cloak>
    {{-- Bubble Button --}}
    <button type="button"
        @click="toggle()"
        :aria-expanded="open.toString()"
        aria-label="Toggle IT Concierge"
        class="relative flex h-14 w-14 items-center justify-center rounded-full bg-slate-900 text-white shadow-2xl shadow-indigo-500/30 hover:bg-slate-800 transition-transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        <svg x-show="open" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>

        {{-- Notification Dot --}}
        @if ($hasUnreadUpdates)
            <span x-show="!open" class="absolute right-0 top-0 flex h-3.5 w-3.5">
                <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                <span
                    class="relative inline-flex rounded-full h-3.5 w-3.5 bg-rose-500 border-2 border-slate-900"></span>
            </span>
        @endif
    </button>

    {{-- Concierge Panel --}}
    <div x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-8 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-8 scale-95"
        class="absolute bottom-20 right-0 w-[calc(100vw-3rem)] sm:w-[400px] rounded-3xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[600px]"
        x-cloak>
        {{-- Header --}}
        <div class="bg-slate-900 px-6 py-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-white">{{ __('ticketing.concierge.title') }}</h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">{{ __('ticketing.concierge.subtitle') }}</p>
                </div>
            </div>
            <button type="button" @click="close()" class="text-slate-400 hover:text-white transition-colors" aria-label="Close IT Concierge">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Tabs --}}
        <div class="flex border-b border-slate-100 bg-slate-50/50">
            <button wire:click="$set('activeTab', 'new')"
                class="flex-1 py-3 text-xs font-bold uppercase tracking-widest {{ $activeTab === 'new' ? 'text-indigo-600 border-b-2 border-indigo-600 bg-white' : 'text-slate-400 hover:text-slate-600' }}">
                {{ __('ticketing.concierge.tabs.new') }}
            </button>
            <button wire:click="$set('activeTab', 'my_tickets')"
                class="flex-1 py-3 text-xs font-bold uppercase tracking-widest {{ $activeTab === 'my_tickets' ? 'text-indigo-600 border-b-2 border-indigo-600 bg-white' : 'text-slate-400 hover:text-slate-600' }}">
                {{ __('ticketing.concierge.tabs.my_tickets') }}
            </button>
        </div>

        {{-- Content Area --}}
        <div class="flex-1 overflow-y-auto p-6">
            @if (session()->has('success'))
                <div
                    class="p-3 mb-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-bold flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div class="p-3 mb-4 rounded-xl bg-rose-50 border border-rose-100 text-rose-700 text-xs font-bold">
                    {{ session('error') }}
                </div>
            @endif

            @if ($activeTab === 'new')
                {{-- Tab A: Simplified Ticket Form --}}
                <div class="space-y-4">
                    {{-- 1. Request Type Selector --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">{{ __('ticketing.concierge.form.request_type') }}</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" wire:click="$set('request_type', 'bug')"
                                class="flex flex-col items-center justify-center p-3 rounded-xl border text-center transition-all cursor-pointer {{ $request_type === 'bug' ? 'border-rose-300 bg-rose-50 text-rose-700 font-bold shadow-sm ring-1 ring-rose-400' : 'border-slate-200 bg-slate-50/70 text-slate-600 hover:bg-slate-100 font-semibold' }}">
                                <svg class="h-5 w-5 mb-1 {{ $request_type === 'bug' ? 'text-rose-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span class="text-xs leading-tight">{{ __('ticketing.concierge.form.type_bug') }}</span>
                            </button>

                            <button type="button" wire:click="$set('request_type', 'feature')"
                                class="flex flex-col items-center justify-center p-3 rounded-xl border text-center transition-all cursor-pointer {{ $request_type === 'feature' ? 'border-indigo-300 bg-indigo-50 text-indigo-700 font-bold shadow-sm ring-1 ring-indigo-400' : 'border-slate-200 bg-slate-50/70 text-slate-600 hover:bg-slate-100 font-semibold' }}">
                                <svg class="h-5 w-5 mb-1 {{ $request_type === 'feature' ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                <span class="text-xs leading-tight">{{ __('ticketing.concierge.form.type_feature') }}</span>
                            </button>

                            <button type="button" wire:click="$set('request_type', 'other')"
                                class="flex flex-col items-center justify-center p-3 rounded-xl border text-center transition-all cursor-pointer {{ $request_type === 'other' ? 'border-sky-300 bg-sky-50 text-sky-700 font-bold shadow-sm ring-1 ring-sky-400' : 'border-slate-200 bg-slate-50/70 text-slate-600 hover:bg-slate-100 font-semibold' }}">
                                <svg class="h-5 w-5 mb-1 {{ $request_type === 'other' ? 'text-sky-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-xs leading-tight">{{ __('ticketing.concierge.form.type_other') }}</span>
                            </button>
                        </div>
                    </div>

                    {{-- 2. Optional Manual Topic / Title Field --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">
                            {{ __('ticketing.concierge.form.custom_title') }}
                        </label>
                        <input type="text" wire:model="custom_title"
                            placeholder="{{ $request_type === 'other' ? __('ticketing.concierge.form.custom_title_other_placeholder') : __('ticketing.concierge.form.custom_title_placeholder') }}"
                            class="block w-full rounded-xl border-slate-200 py-2.5 px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50 shadow-sm">
                        @error('custom_title')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- 3. Description (Required) --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">
                            {{ __('ticketing.concierge.form.details') }} <span class="text-rose-500">*</span>
                        </label>
                        <textarea wire:model="description" rows="3"
                            placeholder="{{ __('ticketing.concierge.form.details_placeholder') }}"
                            class="block w-full rounded-xl border-slate-200 py-2.5 px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50 shadow-sm"></textarea>
                        @error('description')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- 4. Screenshot Upload (Optional) --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">{{ __('ticketing.concierge.form.screenshot') }}</label>
                        @if ($screenshot)
                            <div class="relative rounded-xl border border-indigo-200 bg-indigo-50/50 p-2.5 flex items-center justify-between">
                                <div class="flex items-center gap-2.5 overflow-hidden">
                                    <img src="{{ $screenshot->temporaryUrl() }}" alt="Preview" class="h-10 w-10 object-cover rounded-lg border border-slate-200 shrink-0">
                                    <span class="text-xs text-slate-700 truncate font-medium">{{ $screenshot->getClientOriginalName() }}</span>
                                </div>
                                <button type="button" wire:click="removeScreenshot" class="text-rose-500 hover:text-rose-700 p-1 shrink-0" title="{{ __('ticketing.concierge.form.remove_screenshot') }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @else
                            <label class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/60 p-3 hover:bg-slate-50 cursor-pointer transition-colors">
                                <div class="flex items-center gap-2 text-slate-500 text-xs font-medium">
                                    <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>{{ __('ticketing.concierge.form.attach_screenshot') }}</span>
                                </div>
                                <input type="file" wire:model="screenshot" accept="image/png,image/jpeg,image/webp" class="sr-only">
                            </label>
                        @endif
                        <div wire:loading wire:target="screenshot" class="text-xs text-indigo-600 mt-1 font-medium">
                            {{ __('ticketing.concierge.form.uploading') }}
                        </div>
                        @error('screenshot')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- 5. Submit Button --}}
                    <div class="pt-2">
                        <button wire:click="submitTicket"
                            class="w-full flex justify-center items-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-black text-white shadow-xl shadow-indigo-200 hover:bg-indigo-700 transition-colors">
                            <span>{{ __('ticketing.concierge.form.submit') }}</span>
                            <div wire:loading wire:target="submitTicket"
                                class="h-4 w-4 rounded-full border-2 border-white border-t-transparent animate-spin">
                            </div>
                        </button>
                    </div>
                </div>
            @else
                {{-- Tab B: My Tickets --}}
                <div class="space-y-3">
                    @forelse($this->myTickets as $ticket)
                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                            onclick="window.location.href='{{ route('ticketing.show', $ticket->id) ?? '#' }}'">
                            <div class="flex items-start justify-between mb-2">
                                <h4 class="text-sm font-bold text-slate-900 truncate pr-2">{{ $ticket->title }}</h4>
                                @php
                                    $statusColors = [
                                        'Open' => 'bg-indigo-50 text-indigo-700',
                                        'In Progress' => 'bg-emerald-50 text-emerald-700',
                                        'On Hold' => 'bg-amber-50 text-amber-700',
                                        'Resolved' => 'bg-slate-100 text-slate-600',
                                        'Closed' => 'bg-slate-100 text-slate-400',
                                    ];
                                    $color = $statusColors[$ticket->status->value] ?? 'bg-slate-100 text-slate-700';
                                    $statusKey = strtolower(str_replace(' ', '_', $ticket->status->value));
                                    $localizedStatus = __('ticketing.status.' . $statusKey);
                                @endphp
                                <span
                                    class="inline-flex shrink-0 items-center rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $color }}">
                                    {{ $localizedStatus !== 'ticketing.status.' . $statusKey ? $localizedStatus : $ticket->status->value }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs font-medium text-slate-500">
                                <span>{{ $ticket->ticket_number }}</span>
                                <span>{{ __('ticketing.concierge.my_tickets.updated', ['time' => $ticket->updated_at->diffForHumans()]) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-slate-400">
                            <svg class="mx-auto h-10 w-10 mb-2 opacity-50" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="text-sm font-semibold">{{ __('ticketing.concierge.my_tickets.empty') }}</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</div>
