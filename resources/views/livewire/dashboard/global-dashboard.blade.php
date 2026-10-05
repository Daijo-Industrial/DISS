<div class="space-y-6 pb-12">
    @php
        $hour = date('H');
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
        $pendingApprovalsCount = $kpis['pending_approvals'] ?? 0;
        $user = auth()->user();
        $departmentName = $user->employee?->department?->name ?? ($user->employee?->position ?? ($user->roles->first()?->name ?? 'Team Member'));
        $initials = collect(explode(' ', $user->name ?? 'User'))->filter()->map(fn($part) => mb_substr($part, 0, 1))->take(2)->implode('');
    @endphp

    {{-- Header / Greeting Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 py-3.5 px-5 rounded-2xl bg-white border border-slate-200/90 shadow-sm backdrop-blur-md">
        <div class="flex items-center gap-3.5">
            <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-extrabold text-xs flex items-center justify-center shadow-sm shrink-0">
                {{ strtoupper($initials) }}
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-base font-extrabold text-slate-900 tracking-tight">
                    {{ $greeting }}, <span class="text-blue-600">{{ $user->name }}</span>!
                </h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-600 text-xs font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    {{ $departmentName }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-3 text-xs text-slate-500 font-medium sm:self-center self-start">
            <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                </svg>
                <span>{{ \Carbon\Carbon::now()->format('l, j F Y') }}</span>
            </div>
            @if(isset($kpis['last_fetched_at']))
                <span class="text-slate-300">|</span>
                <span class="text-[11px] text-slate-400">Refreshed {{ \Carbon\Carbon::parse($kpis['last_fetched_at'])->diffForHumans() }}</span>
            @endif
        </div>
    </div>

    {{-- Quick Accessed Menus --}}
    @if (!empty($quickAccessItems))
        <div class="rounded-3xl bg-white border border-slate-200/90 shadow-sm p-4 sm:p-5 space-y-3.5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-7 w-7 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-xs shadow-amber-200">
                        @include('new.layouts.partials.nav-icon', ['name' => 'star'])
                    </div>
                    <div>
                        <h2 class="text-xs sm:text-sm font-extrabold uppercase tracking-wider text-slate-800">
                            Quick Access
                        </h2>
                    </div>
                </div>
                <span class="text-[11px] text-slate-400 font-medium hidden sm:inline">Frequently visited & pinned shortcuts</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5 sm:gap-3">
                @foreach ($quickAccessItems as $quickItem)
                    <a href="{{ route($quickItem['route'], $quickItem['params'] ?? []) }}"
                        class="group relative flex flex-col justify-between p-3 sm:p-3.5 rounded-2xl bg-slate-50/60 hover:bg-white border border-slate-200/80 hover:border-amber-300 shadow-2xs hover:shadow-md transition-all duration-200 active:scale-[0.98]">
                        <div class="flex items-center justify-between mb-2">
                            <span class="flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-xl bg-white border border-slate-200/70 text-slate-600 group-hover:bg-amber-50 group-hover:text-amber-600 group-hover:border-amber-200 transition-colors shadow-2xs">
                                @include('new.layouts.partials.nav-icon', ['name' => $quickItem['icon'] ?? 'circle'])
                            </span>
                            @if (!empty($quickItem['pinned']))
                                <span title="Pinned shortcut" class="text-amber-400">
                                    <svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 24 24">
                                        <path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                    </svg>
                                </span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <span class="block text-xs sm:text-[13px] font-bold text-slate-800 group-hover:text-slate-950 truncate">
                                {{ $quickItem['label'] }}
                            </span>
                            <span class="block text-[10px] text-slate-400 font-medium truncate mt-0.5">
                                {{ $quickItem['section'] ?? 'Shortcut' }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Smart Workspace Grid (Pending Approvals & Recent Requests) --}}
    <div class="grid grid-cols-12 gap-6">
        @if ($pendingApprovalsCount > 0)
            <div class="col-span-12 lg:col-span-7">
                @livewire('dashboard.widgets.approval-queue')
            </div>
            <div class="col-span-12 lg:col-span-5">
                @livewire('dashboard.widgets.my-submissions')
            </div>
        @else
            <div class="col-span-12">
                @livewire('dashboard.widgets.my-submissions')
            </div>
        @endif
    </div>
</div>
