<div class="w-full max-w-5xl space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">
                    Edit User: {{ $name }}
                </h2>
                @if ($emailVerifiedAt)
                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200/80" title="Verified at {{ $emailVerifiedAt->format('Y-m-d H:i:s') }}">
                        <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Verified {{ $emailVerifiedAt->format('M d, Y') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200/80">
                        Unverified
                    </span>
                @endif
            </div>
            @if ($createdAt)
                <p class="text-xs text-slate-400 mt-1">
                    Account created on {{ $createdAt->format('F d, Y') }}
                </p>
            @endif
        </div>
        <a href="{{ route('admin.users.index') }}"
            class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Users
        </a>
    </div>

    {{-- Main Edit Form Card --}}
    <form wire:submit.prevent="save" class="space-y-6 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs">
        {{-- Employee Search --}}
        <div class="relative group">
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="employeeSearch" id="employeeSearch"
                    class="flex h-9 w-full rounded-xl border border-slate-200 bg-transparent px-3 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 placeholder-transparent peer"
                    placeholder="Search Employee (NIK or Name)" autocomplete="off">
                <label for="employeeSearch"
                    class="absolute left-3 -top-2.5 bg-white px-1 text-xs font-medium text-slate-500 transition-all peer-placeholder-shown:top-2 peer-placeholder-shown:text-sm peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-slate-900">
                    Search Employee (NIK or Name) <span class="text-xs text-slate-400 font-normal">(Optional)</span>
                </label>
            </div>
            @if ($selectedEmployeeLabel)
                <div class="mt-2 flex items-center justify-between gap-2 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 border border-emerald-100">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Linked: {{ $selectedEmployeeLabel }}</span>
                    </div>
                    <button type="button" wire:click="clearEmployee" class="text-xs text-rose-600 hover:text-rose-800 font-semibold underline">
                        Unlink
                    </button>
                </div>
            @endif
            @if (!empty($employeeOptions))
                <div class="absolute z-50 mt-1 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                    @foreach ($employeeOptions as $emp)
                        <button type="button"
                            class="w-full px-4 py-3 text-left transition-colors hover:bg-slate-50 border-b border-slate-50 last:border-0"
                            wire:click="selectEmployee({{ $emp['id'] }})">
                            <div class="font-medium text-slate-900">{{ $emp['name'] }}</div>
                            <div class="text-xs text-slate-500 flex items-center gap-2">
                                <span class="font-mono bg-slate-100 px-1 rounded">{{ $emp['nik'] }}</span>
                                <span>•</span><span>{{ $emp['branch'] }}</span>
                                <span>•</span><span>{{ $emp['dept_code'] }}</span>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
            @error('employeeId')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Name + Email --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="relative">
                <input type="text" wire:model.defer="name" id="name"
                    class="peer block w-full rounded-xl border border-slate-200 bg-transparent px-4 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950"
                    placeholder=" ">
                <label for="name" class="absolute left-4 top-2 z-10 origin-[0] -translate-y-6 scale-75 transform text-xs text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:-translate-y-6 peer-focus:scale-75 peer-focus:text-slate-900 bg-white px-1">
                    Full Name <span class="text-red-500">*</span>
                </label>
                @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="email" wire:model.defer="email" id="email"
                    class="peer block w-full rounded-xl border border-slate-200 bg-transparent px-4 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950"
                    placeholder=" ">
                <label for="email" class="absolute left-4 top-2 z-10 origin-[0] -translate-y-6 scale-75 transform text-xs text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:-translate-y-6 peer-focus:scale-75 peer-focus:text-slate-900 bg-white px-1">
                    Email Address <span class="text-red-500">*</span>
                </label>
                @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Password + Confirm Password (Optional) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="relative">
                <input type="password" wire:model.defer="password" id="password"
                    class="peer block w-full rounded-xl border border-slate-200 bg-transparent px-4 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950"
                    placeholder=" ">
                <label for="password" class="absolute left-4 top-2 z-10 origin-[0] -translate-y-6 scale-75 transform text-xs text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:-translate-y-6 peer-focus:scale-75 peer-focus:text-slate-900 bg-white px-1">
                    New Password <span class="text-[11px] text-slate-400 font-normal">(Leave blank to keep current)</span>
                </label>
                @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="password" wire:model.defer="password_confirmation" id="password_confirmation"
                    class="peer block w-full rounded-xl border border-slate-200 bg-transparent px-4 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950"
                    placeholder=" ">
                <label for="password_confirmation" class="absolute left-4 top-2 z-10 origin-[0] -translate-y-6 scale-75 transform text-xs text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:-translate-y-6 peer-focus:scale-75 peer-focus:text-slate-900 bg-white px-1">
                    Confirm New Password
                </label>
            </div>
        </div>

        {{-- Roles --}}
        <div class="rounded-2xl border border-slate-200 overflow-hidden">
            <div class="bg-slate-50 px-5 py-3.5 border-b border-slate-200">
                <h3 class="text-sm font-semibold text-slate-900">Role Assignments</h3>
                <p class="text-xs text-slate-500 mt-0.5">Select the roles this user should have in the system.</p>
            </div>
            <div class="p-5 space-y-4">
                @foreach ($this->groupedRoles as $groupName => $roles)
                    <div class="space-y-2">
                        <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-1">{{ $groupName }}</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($roles as $role)
                                <label class="cursor-pointer group/role relative" title="{{ $this->getRoleDescription($role) }}">
                                    <input type="checkbox" value="{{ $role }}" wire:model.defer="selectedRoles" class="peer sr-only">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-600 transition-all hover:border-slate-400 hover:bg-slate-50 peer-checked:!border-slate-900 peer-checked:!bg-slate-900 peer-checked:text-white peer-checked:shadow-sm select-none">
                                        <svg class="h-3 w-3 opacity-0 peer-checked/role:opacity-100 transition-opacity hidden peer-checked:block" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        {{ $role }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Legacy Direct Permissions --}}
        @if(count($originalDirectPermissions) > 0)
        <div class="rounded-2xl border border-amber-200 overflow-hidden bg-amber-50">
            <div class="px-5 py-3.5 border-b border-amber-200 flex items-start gap-3">
                <div class="mt-0.5">
                    <svg class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-amber-900">Legacy Direct Permissions</h3>
                    <p class="text-xs text-amber-700 mt-1 leading-relaxed">This user has direct permissions assigned. To enforce proper Role-Based Access Control, please migrate these to roles. You can uncheck them to revoke access, but you cannot add new direct permissions.</p>
                </div>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($originalDirectPermissions as $perm)
                    <label class="flex items-start gap-2 cursor-pointer p-2 rounded-xl hover:bg-amber-100/50 border border-transparent transition-colors">
                        <input type="checkbox" value="{{ $perm }}" wire:model.defer="selectedDirectPermissions" class="mt-0.5 h-4 w-4 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                        <div>
                            <div class="text-[11px] text-amber-900 font-mono break-all">{{ $perm }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Status + Actions --}}
        <div class="flex items-center justify-between border-t border-slate-100 pt-5">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.defer="active" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-950">
                <span class="text-sm font-medium text-slate-700">Active Status</span>
            </label>
            <div class="flex gap-3">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2 text-sm font-medium text-white hover:bg-black transition-colors disabled:opacity-50 shadow-xs">
                    <x-bx-loader-alt class="animate-spin" wire:loading wire:target="save" />
                    <span wire:loading.remove wire:target="save">Save Changes</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </div>
    </form>

    {{-- Activity & Usage Profile Card --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-xs">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900 tracking-tight">Navigation & Usage Profile</h3>
                <p class="text-xs text-slate-500 mt-0.5">Aggregated access frequency and recent activity for this user.</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200/60 self-start sm:self-auto">
                {{ $visitStats['total_visits'] }} Total Page Visits
            </span>
        </div>

        {{-- Activity Metrics --}}
        <div class="p-6 border-b border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-4 bg-slate-50/50">
            <div class="bg-white p-4 rounded-xl border border-slate-200/70 shadow-2xs">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Last Active</div>
                <div class="text-sm font-bold text-slate-900 mt-1">
                    {{ $visitStats['last_visited_at'] ? \Illuminate\Support\Carbon::parse($visitStats['last_visited_at'])->diffForHumans() : 'Never' }}
                </div>
                <div class="text-[10px] text-slate-400 mt-0.5">
                    {{ $visitStats['last_visited_at'] ? \Illuminate\Support\Carbon::parse($visitStats['last_visited_at'])->format('M d, Y H:i') : 'No recorded visits' }}
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200/70 shadow-2xs">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Visits</div>
                <div class="text-sm font-bold text-slate-900 mt-1">
                    {{ $visitStats['total_visits'] }} hits
                </div>
                <div class="text-[10px] text-slate-400 mt-0.5">Across all named routes</div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200/70 shadow-2xs">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Distinct Modules Visited</div>
                <div class="text-sm font-bold text-slate-900 mt-1">
                    {{ $visitStats['distinct_routes'] }} features
                </div>
                <div class="text-[10px] text-slate-400 mt-0.5">Unique section visits</div>
            </div>
        </div>

        {{-- Top Visited List --}}
        <div class="p-6">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Top Visited Routes</div>
            @if(count($topVisitedPages) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($topVisitedPages as $visit)
                        <div class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/40 hover:bg-slate-50 flex items-center justify-between transition-colors">
                            <div class="min-w-0 pr-3">
                                <div class="text-xs font-semibold text-slate-900 truncate">
                                    {{ $visit['module'] }}
                                </div>
                                <div class="text-[11px] text-slate-500 font-mono truncate">
                                    {{ $visit['route_name'] }}
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                    {{ $visit['visit_count'] }}x
                                </span>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    {{ \Illuminate\Support\Carbon::parse($visit['last_visited_at'])->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center text-slate-400 text-xs">
                    <x-bx-line-chart class="w-8 h-8 mx-auto text-slate-300 mb-1.5" />
                    No recorded page visits yet.
                </div>
            @endif
        </div>
    </div>
</div>
