<div class="space-y-6 pb-12">
    @php
        $hour = date('H');
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
        $pendingApprovalsCount = $kpis['pending_approvals'] ?? 0;
        $user = auth()->user();
        $departmentName = $user->employee?->department?->name ?? ($user->employee?->position ?? ($user->roles->first()?->name ?? 'Team Member'));
        $initials = collect(explode(' ', $user->name ?? 'User'))->filter()->map(fn($part) => mb_substr($part, 0, 1))->take(2)->implode('');
    @endphp

    {{-- Compact Header Bar (~55px) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 py-3 px-5 rounded-2xl bg-white border border-slate-200/90 shadow-sm backdrop-blur-md">
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

    {{-- Smart Workspace Grid --}}
    <div class="grid grid-cols-12 gap-6">
        @if ($pendingApprovalsCount > 0)
            {{-- Pending Approvals: Shown on the left when documents require signature --}}
            <div class="col-span-12 lg:col-span-7">
                @livewire('dashboard.widgets.approval-queue')
            </div>

            {{-- My Recent Submissions on the right --}}
            <div class="col-span-12 lg:col-span-5">
                @livewire('dashboard.widgets.my-submissions')
            </div>
        @else
            {{-- No documents to sign: My Recent Submissions takes the primary stage --}}
            <div class="col-span-12">
                @livewire('dashboard.widgets.my-submissions')
            </div>
        @endif
    </div>
</div>
