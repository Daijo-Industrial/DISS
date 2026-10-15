<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('ticketing.list') ?? '#' }}"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 shadow-sm hover:bg-slate-50 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                    {{ $ticketData->ticket_number }}
                    @php
                        $statusKey = strtolower(str_replace(' ', '_', $ticketData->status->value));
                        $localizedStatus = __('ticketing.status.' . $statusKey);
                    @endphp
                    <span
                        class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold bg-slate-100 text-slate-600 border-slate-200">
                        {{ $localizedStatus !== 'ticketing.status.' . $statusKey ? $localizedStatus : $ticketData->status->value }}
                    </span>
                </h1>
                <p class="text-sm font-medium text-slate-500 mt-1">
                    {{ __('ticketing.detail.reported_by', ['name' => optional($ticketData->reporter)->name ?? 'Unknown', 'time' => $ticketData->created_at->format('M d, Y H:i')]) }}
                </p>
            </div>
        </div>
        <div>
            @if ($canManage && $ticketData->status->value !== 'Closed')
                <button wire:click="$dispatch('openStatusModal', { ticket: {{ $ticketData->id }} })"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white shadow-md hover:bg-slate-800 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>{{ __('ticketing.detail.update_status') }}</span>
                </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Main Content & Timeline --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Ticket Description --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-lg font-black text-slate-900">{{ $ticketData->title }}</h2>
                    <div class="text-xs font-bold text-slate-400 mt-1 tracking-wide uppercase">
                        {{ $ticketData->category->name }}</div>
                </div>
                <div class="p-6">
                    <div class="prose prose-sm prose-slate max-w-none">
                        {!! nl2br(e($ticketData->description)) !!}
                    </div>

                    @if ($ticketData->current_url && (str_starts_with($ticketData->current_url, 'http://') || str_starts_with($ticketData->current_url, 'https://') || str_starts_with($ticketData->current_url, '/')))
                        <div class="mt-4 pt-4 border-t border-slate-100 flex items-center gap-2 text-xs">
                            <span class="font-bold text-slate-500 uppercase tracking-wider shrink-0">{{ __('ticketing.detail.page_url') }}</span>
                            <a href="{{ $ticketData->current_url }}" target="_blank" rel="noopener noreferrer"
                                class="text-indigo-600 hover:text-indigo-800 underline truncate inline-flex items-center gap-1">
                                <span>{{ $ticketData->current_url }}</span>
                                <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    @endif

                    @if ($ticketData->screenshot_path)
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">{{ __('ticketing.detail.attached_screenshot') }}</span>
                            <a href="{{ asset('storage/' . $ticketData->screenshot_path) }}" target="_blank" class="inline-block group">
                                <img src="{{ asset('storage/' . $ticketData->screenshot_path) }}" alt="Ticket Screenshot"
                                    class="max-h-72 rounded-xl border border-slate-200 shadow-sm object-contain group-hover:opacity-90 transition-opacity">
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Activity Timeline --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-sm font-black text-slate-800 tracking-wide flex items-center gap-2">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ __('ticketing.detail.activity_log') }}
                    </h3>
                </div>
                <div class="p-6">
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            @foreach ($ticketData->activities as $index => $activity)
                                <li>
                                    <div class="relative pb-4">
                                        @if (!$loop->last)
                                            <span class="absolute left-5 top-5 -ml-px h-full w-[2px] bg-slate-100"
                                                aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex items-start space-x-4">

                                            {{-- Icon Column --}}
                                            <div class="relative">
                                                @if ($activity->type->value === 'comment')
                                                    {{-- Human Comment Avatar/Icon --}}
                                                    <span
                                                        class="h-10 w-10 rounded-full flex items-center justify-center ring-4 ring-white bg-indigo-100 text-indigo-600 font-bold text-xs uppercase z-10 shadow-sm">
                                                        {{ substr(optional($activity->user)->name ?? 'U', 0, 2) }}
                                                    </span>
                                                @else
                                                    {{-- System Action Icon (Smaller, Gray) --}}
                                                    <span
                                                        class="h-8 w-8 ml-1 rounded-full flex items-center justify-center ring-4 ring-white bg-slate-50 text-slate-400 z-10 border border-slate-100">
                                                        @if ($activity->type->value === 'status_change')
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                            </svg>
                                                        @elseif($activity->type->value === 'assignment')
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                            </svg>
                                                        @else
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                                            </svg>
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Content Column --}}
                                            <div class="min-w-0 flex-1">
                                                @if ($activity->type->value === 'comment')
                                                    {{-- Chat Bubble Style for Comments --}}
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span
                                                            class="text-sm font-bold text-slate-900">{{ optional($activity->user)->name ?? 'User' }}</span>
                                                        <span
                                                            class="text-[11px] font-medium text-slate-400">{{ $activity->created_at->diffForHumans() }}</span>
                                                    </div>
                                                    <div
                                                        class="rounded-2xl rounded-tl-none bg-indigo-50 border border-indigo-100 p-3 text-sm text-indigo-900 shadow-sm inline-block max-w-full">
                                                        {!! nl2br(e($activity->reason)) !!}
                                                    </div>
                                                @else
                                                    {{-- Muted System Log Style --}}
                                                    <div class="py-1">
                                                        <p
                                                            class="text-sm text-slate-500 flex items-center justify-between">
                                                            <span>
                                                                <span
                                                                    class="font-medium text-slate-700">{{ optional($activity->user)->name ?? 'System' }}</span>
                                                                @if ($activity->type->value === 'status_change')
                                                                    changed status to <span
                                                                        class="font-bold text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded-md">{{ $activity->new_state }}</span>
                                                                @elseif($activity->type->value === 'assignment')
                                                                    {{ $activity->new_state }}
                                                                @endif
                                                            </span>
                                                            <span
                                                                class="text-[11px] text-slate-400 font-medium">{{ $activity->created_at->diffForHumans() }}</span>
                                                        </p>
                                                        @if ($activity->reason)
                                                            <p
                                                                class="mt-2 text-sm text-slate-600 bg-slate-50 rounded-xl p-3 border border-slate-100">
                                                                "{{ $activity->reason }}"
                                                            </p>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>

                                        </div>
                                    </div>
                                </li>
                            @endforeach

                            {{-- Thread Start --}}
                            <li>
                                <div class="relative pb-4">
                                    <div class="relative flex items-start space-x-4 pt-2">
                                        <div class="relative">
                                            <span
                                                class="h-8 w-8 ml-1 rounded-full flex items-center justify-center ring-4 ring-white bg-slate-100 text-slate-400 z-10 border border-slate-200">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1 py-1">
                                            <div class="flex items-center justify-between text-sm text-slate-500">
                                                <span>{{ __('ticketing.detail.ticket_reported_by', ['name' => optional($ticketData->reporter)->name ?? 'Unknown']) }}</span>
                                                <span
                                                    class="text-[11px] font-medium text-slate-400">{{ $ticketData->created_at->format('M d, H:i') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Post Comment Input --}}
                <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/50">
                    <form wire:submit.prevent="postComment" class="flex items-start gap-4">
                        <div
                            class="h-10 w-10 shrink-0 rounded-full flex items-center justify-center bg-indigo-600 text-white font-bold text-xs uppercase shadow-sm">
                            {{ substr(auth()->user()->name ?? 'U', 0, 2) }}
                        </div>
                        <div class="flex-1">
                            <textarea wire:model="newComment" rows="2" placeholder="{{ __('ticketing.detail.reply_placeholder') }}"
                                class="block w-full rounded-2xl border-slate-200 py-3 px-4 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white shadow-sm resize-none"></textarea>
                            @error('newComment')
                                <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                            @enderror
                            <div class="mt-3 flex justify-end">
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-md hover:bg-slate-800 transition-colors">
                                    <span>{{ __('ticketing.detail.post_reply') }}</span>
                                    <div wire:loading wire:target="postComment"
                                        class="h-3 w-3 rounded-full border border-white border-t-transparent animate-spin">
                                    </div>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        {{-- Right: Sidebar Details --}}
        <div class="space-y-6">

            {{-- Properties --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-sm font-black text-slate-800 tracking-wide">{{ __('ticketing.detail.properties') }}</h3>
                </div>
                <div class="p-6 space-y-4">

                    {{-- Category (Editable by IT Support / Admins) --}}
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">{{ __('ticketing.detail.change_category') }}</div>
                        @if ($canManage)
                            <select wire:change="updateCategory($event.target.value)"
                                class="text-xs font-bold text-slate-800 rounded-xl border-slate-200 py-2 px-3 bg-slate-50 w-full focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-colors hover:bg-white cursor-pointer">
                                @foreach ($allCategories as $cat)
                                    <option value="{{ $cat->id }}" {{ $ticketData->category_id == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <div class="font-bold text-slate-900">{{ $ticketData->category->name }}</div>
                        @endif
                    </div>

                    {{-- Priority (Editable by IT Support / Admins) --}}
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">{{ __('ticketing.detail.change_priority') }}</div>
                        @if ($canManage)
                            <select wire:change="updatePriority($event.target.value)"
                                class="text-xs font-bold text-slate-800 rounded-xl border-slate-200 py-2 px-3 bg-slate-50 w-full focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-colors hover:bg-white cursor-pointer">
                                @foreach (\App\Domains\Ticketing\Enums\TicketPriority::cases() as $pCase)
                                    <option value="{{ $pCase->value }}" {{ $ticketData->priority->value === $pCase->value ? 'selected' : '' }}>
                                        {{ $pCase->value }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <div class="font-bold text-slate-900">{{ $ticketData->priority->value }}</div>
                        @endif
                    </div>

                    {{-- Assignee --}}
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">{{ __('ticketing.detail.assignee') }}</div>
                        @if ($ticketData->assignee)
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-6 w-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-[10px] font-bold">
                                    {{ substr($ticketData->assignee->name, 0, 2) }}
                                </div>
                                <span class="font-bold text-slate-900">{{ $ticketData->assignee->name }}</span>
                            </div>
                        @else
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-400">{{ __('ticketing.detail.unassigned') }}</span>
                                <button wire:click="assignToMe"
                                    class="text-xs font-bold text-indigo-600 hover:text-indigo-800">{{ __('ticketing.detail.assign_to_me') }}</button>
                            </div>
                        @endif
                    </div>

                    {{-- SLA Timer (Editable by IT Support / Admins in Minutes) --}}
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">{{ __('ticketing.detail.sla_target') }}</div>
                        @if ($canManage)
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1">
                                        <input type="number" min="1" max="43200"
                                            wire:model="customSlaMinutes"
                                            wire:keydown.enter.prevent="saveSlaMinutes"
                                            placeholder="{{ $ticketData->getEffectiveSlaMinutes() ?? 60 }}"
                                            class="text-xs font-bold text-slate-800 rounded-xl border-slate-200 py-2 pl-3 pr-14 bg-slate-50 w-full focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-colors hover:bg-white">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-400">
                                            {{ __('ticketing.detail.minutes') }}
                                        </span>
                                    </div>
                                    <button type="button" wire:click="saveSlaMinutes"
                                        class="shrink-0 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-3 py-2 shadow-sm transition-colors">
                                        {{ __('ticketing.detail.save') }}
                                    </button>
                                </div>
                                <div class="text-[11px] text-slate-400 font-medium">
                                    {{ __('ticketing.detail.sla_target') }}: <span class="font-bold text-slate-700">{{ $ticketData->getFormattedSlaTarget() }}</span>
                                </div>
                                @error('customSlaMinutes')
                                    <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                                @enderror
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="font-bold text-slate-700">{{ $ticketData->getFormattedSlaTarget() }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Time on Hold --}}
                    @if ($ticketData->total_hold_time_minutes > 0 || $ticketData->status->value === 'On Hold')
                        <div>
                            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">{{ __('ticketing.detail.time_on_hold') }}</div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-amber-600">
                                    {{ $ticketData->getFormattedHoldTime() }}
                                </span>
                                @if ($ticketData->status->value === 'On Hold')
                                    <span class="flex h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                                @endif
                            </div>
                        </div>
                    @endif

                </div>
            </div>

            {{-- GitHub Escalation Card --}}
            @if (config('services.github.repo_url'))
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-slate-800" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" />
                        </svg>
                        <h3 class="text-sm font-black text-slate-800 tracking-wide">{{ __('ticketing.detail.github_integration') }}</h3>
                    </div>
                    @if ($ticketData->github_issue_url)
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700">
                            {{ __('ticketing.detail.escalated') }}
                        </span>
                    @endif
                </div>
                <div class="p-6 space-y-4">
                    @if (session()->has('success'))
                        <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-bold">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($ticketData->github_issue_url)
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">{{ __('ticketing.detail.linked_issue') }}</span>
                            <a href="{{ $ticketData->github_issue_url }}" target="_blank" rel="noopener noreferrer"
                                class="text-xs font-bold text-indigo-600 hover:text-indigo-800 break-all inline-flex items-center gap-1">
                                <span>{{ $ticketData->github_issue_url }}</span>
                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    @endif

                    {{-- Escalate Button --}}
                    <a href="{{ $this->getGitHubIssueUrl() }}" target="_blank" rel="noopener noreferrer"
                        class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 transition-colors">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" />
                        </svg>
                        <span>{{ $ticketData->github_issue_url ? __('ticketing.detail.open_github_form') : __('ticketing.detail.escalate_to_github') }}</span>
                    </a>

                    {{-- Save / Update Linked GitHub URL --}}
                    <div class="pt-2 border-t border-slate-100">
                        <label class="text-[11px] font-bold text-slate-500 block mb-1">{{ __('ticketing.detail.record_github_url') }}</label>
                        <div class="flex items-center gap-2">
                            <input type="url" wire:model="githubIssueUrlInput" placeholder="https://github.com/..."
                                class="flex-1 rounded-xl border border-slate-200 py-1.5 px-2.5 text-xs focus:border-indigo-500 bg-slate-50">
                            <button type="button" wire:click="saveGithubIssueUrl"
                                class="rounded-xl bg-slate-100 hover:bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 transition-colors">
                                {{ __('ticketing.detail.save') }}
                            </button>
                        </div>
                        @error('githubIssueUrlInput')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
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
