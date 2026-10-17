<div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Flash Notifications --}}
    @if (session()->has('success'))
        <div class="px-4 py-3 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Header & Hero --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            {{-- Breadcrumb Navigation --}}
            <div class="flex items-center gap-2 text-xs font-medium text-slate-400 mb-1">
                <a href="{{ route('ticketing.list') ?? '#' }}" class="hover:text-slate-700 transition-colors inline-flex items-center gap-1">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>{{ __('ticketing.nav.tickets') }}</span>
                </a>
                <span>/</span>
                <span class="font-mono text-slate-600 font-semibold">{{ $ticketData->ticket_number }}</span>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                    {{ $ticketData->title }}
                </h1>

                @php
                    $statusKey = strtolower(str_replace(' ', '_', $ticketData->status->value));
                    $localizedStatus = __('ticketing.status.' . $statusKey);
                    $sla = $ticketData->getSlaStatus();
                @endphp

                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border {{ $ticketData->status->getBadgeColor() }}">
                    {{ $localizedStatus !== 'ticketing.status.' . $statusKey ? $localizedStatus : $ticketData->status->value }}
                </span>

                @if (!empty($sla['badge']) && in_array($sla['state'] ?? '', ['breached', 'due_soon']))
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium border {{ $sla['badge'] }}">
                        <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                        <span>{{ $sla['remaining'] }}</span>
                    </span>
                @endif
            </div>

            <p class="text-xs text-slate-400 mt-1">
                {{ __('ticketing.detail.reported_by', [
                    'name' => optional($ticketData->reporter)->name ?? optional($ticketData->reporter)->nama ?? ($ticketData->reporter_id ?? 'User'),
                    'time' => $ticketData->created_at->translatedFormat('d M Y, H:i')
                ]) }}
            </p>
        </div>

        {{-- Single Restrained Primary Action --}}
        <div class="flex items-center gap-2 shrink-0">
            @if ($canManage && $ticketData->status->value !== 'Closed')
                @if (!$ticketData->assigned_to)
                    <button type="button" wire:click="assignToMe" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 px-3.5 py-2 text-xs font-semibold transition-colors cursor-pointer">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>{{ __('ticketing.list.claim') }}</span>
                    </button>
                @endif

                <button wire:click="$dispatch('openStatusModal', { ticket: {{ $ticketData->id }} })"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 text-xs sm:text-sm font-semibold shadow-xs transition-colors cursor-pointer">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>{{ __('ticketing.detail.update_status') }}</span>
                </button>
            @endif
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Description & Discussion --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Description Card --}}
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs space-y-4">
                <div class="prose prose-sm prose-slate max-w-none text-slate-800 leading-relaxed text-sm whitespace-pre-line">
                    {{ $ticketData->getCleanDescription() }}
                </div>

                @if ($ticketData->current_url && (str_starts_with($ticketData->current_url, 'http://') || str_starts_with($ticketData->current_url, 'https://') || str_starts_with($ticketData->current_url, '/')))
                    <div class="pt-3 border-t border-slate-100 flex items-center gap-2 text-xs">
                        <span class="text-slate-400 font-medium shrink-0">{{ __('ticketing.detail.page_url') }}</span>
                        <a href="{{ $ticketData->current_url }}" target="_blank" rel="noopener noreferrer"
                            class="text-indigo-600 hover:underline truncate inline-flex items-center gap-1 font-medium">
                            <span>{{ $ticketData->current_url }}</span>
                            <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </div>
                @endif

                @if ($ticketData->screenshot_path)
                    <div class="pt-3 border-t border-slate-100">
                        <span class="text-xs text-slate-400 font-medium block mb-2">{{ __('ticketing.detail.attached_screenshot') }}</span>
                        <a href="{{ asset('storage/' . $ticketData->screenshot_path) }}" target="_blank" class="inline-block group">
                            <img src="{{ asset('storage/' . $ticketData->screenshot_path) }}" alt="Ticket Screenshot"
                                class="max-h-72 rounded-xl border border-slate-200 shadow-xs object-contain group-hover:opacity-90 transition-opacity">
                        </a>
                    </div>
                @endif
            </div>

            {{-- Activity Feed & Discussion (Chat Style) --}}
            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden flex flex-col">
                {{-- Header --}}
                <div class="px-6 py-3.5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">
                            {{ __('ticketing.detail.activity_log') }}
                        </h3>
                    </div>
                    <span class="text-xs text-slate-400 font-medium">
                        {{ $ticketData->activities->count() }} {{ __('ticketing.detail.activities') }}
                    </span>
                </div>

                {{-- Chat Feed (Chronological: Earliest at top, Latest at bottom) --}}
                <div x-data="{
                        scrollToBottom() {
                            $nextTick(() => { $el.scrollTop = $el.scrollHeight });
                        }
                    }"
                    x-init="scrollToBottom()"
                    @chat-scrolled.window="scrollToBottom()"
                    @ticket-updated.window="scrollToBottom()"
                    @ticket-updated.camel.window="scrollToBottom()"
                    class="p-6 space-y-4 max-h-[560px] overflow-y-auto"
                    role="log"
                    tabindex="0"
                    aria-label="{{ __('ticketing.detail.activity_log') }}">

                    {{-- Initial Ticket Submission Marker --}}
                    <div class="relative flex items-center justify-center my-2 w-full">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-slate-100"></div>
                        </div>
                        <span class="relative text-[11px] font-medium text-slate-500 bg-slate-50 border border-slate-200/70 px-3.5 py-1 rounded-full shadow-2xs">
                            {{ __('ticketing.detail.ticket_created_on', ['time' => $ticketData->created_at->translatedFormat('d M Y, H:i')]) }}
                        </span>
                    </div>

                    @forelse ($ticketData->activities as $activity)
                        @if ($activity->type->value === 'comment')
                            @php
                                $isMe = auth()->check() && (int) auth()->id() === (int) $activity->user_id;
                            @endphp

                            @if ($isMe)
                                {{-- Outgoing Message (Current User: Aligned Right) --}}
                                <div class="flex flex-col items-end gap-1">
                                    <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                        <span class="font-medium text-slate-600">{{ __('ticketing.detail.me') }}</span>
                                        <span>•</span>
                                        <span>{{ $activity->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="bg-slate-900 text-white rounded-2xl rounded-br-xs px-4 py-2.5 text-xs leading-relaxed max-w-xl shadow-xs">
                                        {{ $activity->reason }}
                                    </div>
                                </div>
                            @else
                                {{-- Incoming Message (Other Participant: Aligned Left) --}}
                                <div class="flex items-start gap-3 max-w-xl">
                                    <div class="h-8 w-8 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs uppercase shrink-0 border border-slate-200/60">
                                        {{ substr(optional($activity->user)->name ?? 'U', 0, 2) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-400 mb-1">
                                            <span class="font-semibold text-slate-900">{{ optional($activity->user)->name ?? 'User' }}</span>
                                            <span>•</span>
                                            <span>{{ $activity->created_at->diffForHumans() }}</span>
                                        </div>
                                        <div class="bg-slate-100/90 text-slate-800 rounded-2xl rounded-tl-xs px-4 py-2.5 text-xs leading-relaxed inline-block shadow-2xs">
                                            {{ $activity->reason }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @else
                            {{-- Quick System Event (Centered Across the Activity Card) --}}
                            <div class="relative flex flex-col items-center justify-center my-3 w-full gap-1">
                                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                    <div class="w-full border-t border-slate-100"></div>
                                </div>
                                <div class="relative inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-slate-50 border border-slate-200/70 text-[11px] text-slate-500 font-medium shadow-2xs">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400 shrink-0"></span>
                                    <span>
                                        <strong class="text-slate-700 font-semibold">{{ optional($activity->user)->name ?? 'System' }}</strong>:
                                        @if ($activity->type->value === 'status_change')
                                            {{ __('ticketing.detail.changed_status_to') }} <strong class="text-slate-900 font-semibold">{{ $activity->new_state }}</strong>
                                        @elseif($activity->type->value === 'assignment')
                                            {{ $activity->new_state }}
                                        @else
                                            {{ $activity->new_state }}
                                        @endif
                                    </span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-slate-400 shrink-0">{{ $activity->created_at->diffForHumans() }}</span>
                                </div>
                                @if ($activity->reason && !in_array($activity->reason, ['Ticket created via Support Concierge.', 'Category triaged by IT Support.', 'Priority triaged by IT Support.', 'SLA target adjusted by IT Support.']))
                                    <p class="relative text-[11px] text-slate-500 italic max-w-md text-center bg-slate-50 px-3 py-1 rounded-lg border border-slate-200/60 shadow-2xs ">
                                        "{{ $activity->reason }}"
                                    </p>
                                @endif
                            </div>
                        @endif
                    @empty
                        <div class="text-center py-8 text-xs text-slate-400">
                            {{ __('ticketing.track.no_activities') }}
                        </div>
                    @endforelse
                </div>

                {{-- Clean Comment Box (Chat Input Bar at Bottom) --}}
                <div class="p-4 border-t border-slate-100 bg-slate-50/40">
                    <form wire:submit.prevent="postComment" class="space-y-2">
                        <textarea wire:model="newComment" wire:keydown.ctrl.enter="postComment" rows="2"
                            aria-label="{{ __('ticketing.detail.reply_placeholder') }}"
                            placeholder="{{ __('ticketing.detail.reply_placeholder') }}"
                            class="block w-full rounded-xl bg-white border border-slate-200/80 p-3 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-slate-400 focus:ring-1 focus:ring-slate-300 resize-none transition-all"></textarea>
                        @error('newComment')
                            <span class="text-xs text-rose-500 block">{{ $message }}</span>
                        @enderror
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-slate-400">
                                {{ __('ticketing.detail.ctrl_enter_hint') }}
                            </span>
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-1.5 text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                                <span>{{ __('ticketing.detail.post_reply') }}</span>
                                <div wire:loading wire:target="postComment"
                                    class="h-2.5 w-2.5 rounded-full border border-white border-t-transparent animate-spin">
                                </div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        {{-- Right: Clean Metadata Sidebar --}}
        <div class="space-y-5">

            {{-- Reporter Contact Card (Apple Contact Style) --}}
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-slate-100 text-slate-700 font-semibold text-xs flex items-center justify-center shrink-0">
                        {{ substr(optional($ticketData->reporter)->name ?? optional($ticketData->reporter)->nama ?? 'U', 0, 2) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-slate-900 truncate">
                            {{ optional($ticketData->reporter)->name ?? optional($ticketData->reporter)->nama ?? ($ticketData->reporter_id ?? 'Unknown') }}
                        </div>
                        <div class="text-xs text-slate-400 truncate">
                            @if ($ticketData->reporter_id)
                                <span>NIK: {{ $ticketData->reporter_id }}</span>
                            @endif
                            @php
                                $deptLabel = optional(optional($ticketData->reporter)->department)->name
                                    ?? optional($ticketData->reporter)->dept_code
                                    ?? optional($ticketData->reporter)->departemen;
                            @endphp
                            @if ($deptLabel)
                                <span>• {{ $deptLabel }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Direct Contact Icon Actions (Apple Style) --}}
                <div class="pt-2 border-t border-slate-100 flex items-center justify-around">
                    @if ($ticketData->reporter_email)
                        <a href="mailto:{{ $ticketData->reporter_email }}"
                            title="Email: {{ $ticketData->reporter_email }}"
                            class="h-9 w-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </a>
                    @endif

                    @if ($ticketData->whatsapp_url)
                        <a href="{{ $ticketData->whatsapp_url }}" target="_blank" rel="noopener noreferrer"
                            title="{{ __('ticketing.detail.chat_whatsapp') }}"
                            class="h-9 w-9 rounded-full bg-slate-100 hover:bg-emerald-50 hover:text-emerald-600 text-slate-600 flex items-center justify-center transition-colors">
                            <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766 0-3.18-2.587-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.861.174.086.275.072.376-.044.102-.115.434-.506.549-.68.116-.173.232-.144.39-.086.159.058 1.011.477 1.184.564.173.087.289.13.332.203.043.072.043.419-.101.824z" />
                            </svg>
                        </a>
                    @endif

                    @if ($ticketData->contact_phone)
                        <a href="tel:{{ $ticketData->contact_phone }}"
                            title="{{ __('ticketing.detail.call') }}: {{ $ticketData->contact_phone }}"
                            class="h-9 w-9 rounded-full bg-slate-100 hover:bg-blue-50 hover:text-blue-600 text-slate-600 flex items-center justify-center transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </a>
                    @endif

                    {{-- Copy ID Action --}}
                    <div x-data="{ copied: false }">
                        <button type="button"
                            @click="navigator.clipboard.writeText('{{ $ticketData->ticket_number }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            title="{{ __('ticketing.detail.copy_id') }}"
                            class="h-9 w-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                            <template x-if="!copied">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </template>
                            <template x-if="copied">
                                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                        </button>
                    </div>
                </div>

                @if ($ticketData->device_info)
                    <div class="pt-2 border-t border-slate-100 text-[11px] text-slate-400 font-mono break-all leading-tight">
                        {{ $ticketData->device_info }}
                    </div>
                @endif
            </div>

            {{-- Properties Card (Apple Key-Value Style) --}}
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                    {{ __('ticketing.detail.properties') }}
                </h3>

                {{-- Category --}}
                <div class="space-y-1">
                    <label class="text-xs text-slate-400 font-medium">{{ __('ticketing.detail.change_category') }}</label>
                    @if ($canManage)
                        <select wire:change="updateCategory($event.target.value)"
                            class="block w-full rounded-xl bg-slate-50 border border-slate-200/80 py-1.5 px-3 text-xs font-medium text-slate-900 focus:bg-white focus:outline-none cursor-pointer">
                            @foreach ($allCategories as $cat)
                                <option value="{{ $cat->id }}" {{ $ticketData->category_id == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <div class="text-xs font-semibold text-slate-900">{{ $ticketData->category->name }}</div>
                    @endif
                </div>

                {{-- Priority --}}
                <div class="space-y-1">
                    <label class="text-xs text-slate-400 font-medium">{{ __('ticketing.detail.change_priority') }}</label>
                    @if ($canManage)
                        <select wire:change="updatePriority($event.target.value)"
                            class="block w-full rounded-xl bg-slate-50 border border-slate-200/80 py-1.5 px-3 text-xs font-medium text-slate-900 focus:bg-white focus:outline-none cursor-pointer">
                            @foreach (\App\Domains\Ticketing\Enums\TicketPriority::cases() as $pCase)
                                <option value="{{ $pCase->value }}" {{ $ticketData->priority->value === $pCase->value ? 'selected' : '' }}>
                                    {{ __('ticketing.concierge.priorities.' . strtolower($pCase->value)) }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <div class="text-xs font-semibold text-slate-900">{{ __('ticketing.concierge.priorities.' . strtolower($ticketData->priority->value)) }}</div>
                    @endif
                </div>

                {{-- Assignee --}}
                <div class="space-y-1">
                    <label class="text-xs text-slate-400 font-medium">{{ __('ticketing.detail.assignee') }}</label>
                    @if ($canManage)
                        <select wire:change="reassignToUser($event.target.value)"
                            class="block w-full rounded-xl bg-slate-50 border border-slate-200/80 py-1.5 px-3 text-xs font-medium text-slate-900 focus:bg-white focus:outline-none cursor-pointer">
                            <option value="" disabled {{ !$ticketData->assigned_to ? 'selected' : '' }}>-- {{ __('ticketing.detail.unassigned') }} --</option>
                            @foreach ($itStaff as $staff)
                                <option value="{{ $staff->id }}" {{ $ticketData->assigned_to === $staff->id ? 'selected' : '' }}>
                                    {{ $staff->name }} {{ $staff->id === auth()->id() ? '(' . __('ticketing.detail.me') . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <div class="text-xs font-semibold text-slate-900">
                            {{ $ticketData->assignee->name ?? __('ticketing.detail.unassigned') }}
                        </div>
                    @endif
                </div>

                {{-- SLA Target Dropdown (Replaces button clutter) --}}
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs text-slate-400 font-medium">
                        <span>{{ __('ticketing.detail.sla_target') }}</span>
                        <span class="text-slate-600 font-semibold">{{ $ticketData->getFormattedSlaTarget() }}</span>
                    </div>

                    @if ($canManage)
                        <select wire:change="setSlaPreset($event.target.value)"
                            class="block w-full rounded-xl bg-slate-50 border border-slate-200/80 py-1.5 px-3 text-xs font-medium text-slate-900 focus:bg-white focus:outline-none cursor-pointer">
                            <option value="60" {{ $ticketData->getEffectiveSlaMinutes() === 60 ? 'selected' : '' }}>{{ __('ticketing.detail.sla_presets_options.1_hour') }}</option>
                            <option value="120" {{ $ticketData->getEffectiveSlaMinutes() === 120 ? 'selected' : '' }}>{{ __('ticketing.detail.sla_presets_options.2_hours') }}</option>
                            <option value="240" {{ $ticketData->getEffectiveSlaMinutes() === 240 ? 'selected' : '' }}>{{ __('ticketing.detail.sla_presets_options.4_hours') }}</option>
                            <option value="480" {{ $ticketData->getEffectiveSlaMinutes() === 480 ? 'selected' : '' }}>{{ __('ticketing.detail.sla_presets_options.8_hours') }}</option>
                            <option value="1440" {{ $ticketData->getEffectiveSlaMinutes() === 1440 ? 'selected' : '' }}>{{ __('ticketing.detail.sla_presets_options.24_hours') }}</option>
                            <option value="2880" {{ $ticketData->getEffectiveSlaMinutes() === 2880 ? 'selected' : '' }}>{{ __('ticketing.detail.sla_presets_options.48_hours') }}</option>
                        </select>
                    @endif

                    @if ($ticketData->getSlaDueAt())
                        <div class="text-[11px] text-slate-400 pt-1">
                            {{ __('ticketing.detail.target_due') }}: {{ $ticketData->getSlaDueAt()->translatedFormat('d M, H:i') }}
                        </div>
                    @endif
                </div>

                {{-- Time on Hold Tracker --}}
                @if ($ticketData->total_hold_time_minutes > 0 || $ticketData->status->value === 'On Hold')
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-400 font-medium">{{ __('ticketing.detail.time_on_hold') }}</span>
                        <span class="font-semibold text-amber-700 flex items-center gap-1.5">
                            @if ($ticketData->status->value === 'On Hold')
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            @endif
                            {{ $ticketData->getFormattedHoldTime() }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- GitHub Issue (Quiet Link - IT Staff Only) --}}
            @if ($canManage && config('services.github.repo_url'))
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-3 text-xs">
                    <div class="flex items-center justify-between text-slate-400 font-medium">
                        <span class="font-bold uppercase tracking-wider text-[11px]">{{ __('ticketing.detail.github_integration') }}</span>
                        @if ($ticketData->github_issue_url)
                            <span class="text-purple-600 font-semibold">{{ __('ticketing.detail.escalated') }}</span>
                        @endif
                    </div>

                    @if ($ticketData->github_issue_url)
                        <div>
                            <span class="text-slate-400 block mb-1">{{ __('ticketing.detail.linked_issue') }}</span>
                            <a href="{{ $ticketData->github_issue_url }}" target="_blank" rel="noopener noreferrer"
                                class="text-indigo-600 hover:underline break-all block font-medium">
                                {{ $ticketData->github_issue_url }}
                            </a>
                        </div>
                    @endif

                    <div>
                        <a href="{{ $this->getGitHubIssueUrl() }}" target="_blank" rel="noopener noreferrer"
                            class="text-slate-700 hover:text-slate-900 font-medium inline-flex items-center gap-1">
                            <span>{{ $ticketData->github_issue_url ? __('ticketing.detail.open_github_form') : __('ticketing.detail.escalate_to_github') }} &rarr;</span>
                        </a>
                    </div>

                    <div class="pt-2 border-t border-slate-100 space-y-1.5">
                        <label class="text-[11px] text-slate-400 font-medium block">{{ __('ticketing.detail.record_github_url') }}</label>
                        <div class="flex items-center gap-1.5">
                            <input type="url" wire:model="githubIssueUrlInput" placeholder="https://github.com/..."
                                class="flex-1 rounded-xl bg-slate-50 border border-slate-200/80 py-1 px-2.5 text-xs text-slate-800 focus:bg-white focus:outline-none">
                            <button type="button" wire:click="saveGithubIssueUrl"
                                class="rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 text-xs font-semibold transition-colors cursor-pointer">
                                {{ __('ticketing.detail.save') }}
                            </button>
                        </div>
                        @error('githubIssueUrlInput')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            @endif

        </div>

    </div>

    {{-- Teleport Status Modal --}}
    @push('modals')
        @livewire('ticketing.ticket-status-modal')
    @endpush
</div>
