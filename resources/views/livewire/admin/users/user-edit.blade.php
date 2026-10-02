<div class="w-full max-w-6xl mx-auto space-y-6">
    {{-- Breadcrumb Navigation --}}
    <nav class="flex items-center gap-2 text-xs font-medium text-slate-400">
        <a href="{{ route('admin.users.index') }}" class="hover:text-slate-700 transition-colors">Users</a>
        <svg class="w-3.5 h-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
        </svg>
        <span class="text-slate-800 font-semibold truncate">{{ $name }}</span>
    </nav>

    {{-- User Header Card --}}
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-5">
        <div class="flex items-start sm:items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-bold text-xl shadow-xs flex-shrink-0 select-none">
                {{ strtoupper(substr($name ?: 'U', 0, 1)) }}
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                        Edit User: {{ $name }}
                    </h1>
                    @if ($emailVerifiedAt)
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200/80" title="Verified at {{ $emailVerifiedAt->format('Y-m-d H:i:s') }}">
                            <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Verified {{ $emailVerifiedAt->format('M d, Y') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200/80">
                            Unverified
                        </span>
                    @endif

                    @if ($active)
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50/60 px-2.5 py-0.5 rounded-full border border-emerald-200/60">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            Suspended
                        </span>
                    @endif

                    @if ($isDormant)
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-800 bg-amber-100/70 px-2.5 py-0.5 rounded-full border border-amber-300">
                            ⚠️ Inactive >30d
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-y-1 gap-x-3 text-xs text-slate-500 mt-1.5">
                    <span class="font-mono text-slate-600">{{ $email }}</span>
                    @if ($selectedEmployeeLabel)
                        <span>•</span>
                        <span class="text-blue-700 font-medium">Linked: {{ $selectedEmployeeLabel }}</span>
                    @endif
                    @if ($createdAt)
                        <span>•</span>
                        <span class="text-slate-400">Joined {{ $createdAt->format('M d, Y') }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 self-start md:self-center flex-shrink-0">
            <a href="{{ route('admin.users.index') }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors shadow-2xs">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Users
            </a>
        </div>
    </div>

    {{-- Status Flash Alert --}}
    @if (session('status') || session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-800 text-xs font-medium flex items-center justify-between shadow-2xs transition-all">
            <div class="flex items-center gap-2.5">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span>{{ session('status') ?? session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 text-xs">✕</button>
        </div>
    @endif

    {{-- Apple-Style Segmented Tab Navigation --}}
    <div class="flex items-center gap-1.5 p-1 bg-slate-100/90 rounded-2xl border border-slate-200/60 max-w-fit overflow-x-auto shadow-2xs">
        <button type="button" wire:click="setTab('profile')"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold transition-all select-none {{ $tab === 'profile' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900' }}">
            <x-bx-user class="w-4 h-4" />
            <span>Profile & HR</span>
        </button>

        <button type="button" wire:click="setTab('roles')"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold transition-all select-none {{ $tab === 'roles' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900' }}">
            <x-bx-shield-quarter class="w-4 h-4" />
            <span>Roles & Access</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $tab === 'roles' ? 'bg-slate-100 text-slate-800' : 'bg-slate-200 text-slate-600' }}">
                {{ count($selectedRoles) }}
            </span>
        </button>

        <button type="button" wire:click="setTab('activity')"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold transition-all select-none {{ $tab === 'activity' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900' }}">
            <x-bx-line-chart class="w-4 h-4" />
            <span>Activity & Usage</span>
            @if ($isDormant)
                <span class="w-2 h-2 rounded-full bg-amber-500" title="Account dormant (>30d)"></span>
            @endif
        </button>

        <button type="button" wire:click="setTab('security')"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold transition-all select-none {{ $tab === 'security' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900' }}">
            <x-bx-lock-alt class="w-4 h-4" />
            <span>Security</span>
        </button>
    </div>

    {{-- TAB 1: Profile & HR Linkage --}}
    @if ($tab === 'profile')
        <form wire:submit.prevent="saveProfile" class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-base font-bold text-slate-900 tracking-tight">Profile & Corporate Linkage</h3>
                <p class="text-xs text-slate-500 mt-0.5">Manage user credentials, personal details, employee assignment, and active status.</p>
            </div>

            {{-- Employee Search & Link --}}
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Linked Employee Record
                </label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="employeeSearch" id="employeeSearch"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 transition-colors"
                        placeholder="Type NIK or employee name to search..." autocomplete="off">

                    @if (!empty($employeeOptions))
                        <div class="absolute z-50 mt-1.5 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-xl py-1">
                            @foreach ($employeeOptions as $emp)
                                <button type="button"
                                    class="w-full px-4 py-2.5 text-left transition-colors hover:bg-slate-50 border-b border-slate-100 last:border-0"
                                    wire:click="selectEmployee({{ $emp['id'] }})">
                                    <div class="font-semibold text-sm text-slate-900">{{ $emp['name'] }}</div>
                                    <div class="text-xs text-slate-500 flex items-center gap-2 mt-0.5">
                                        <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-[11px] font-semibold text-slate-700">{{ $emp['nik'] }}</span>
                                        <span>•</span><span>{{ $emp['branch'] }}</span>
                                        <span>•</span><span>{{ $emp['dept_code'] }}</span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($selectedEmployeeLabel)
                    <div class="mt-2.5 flex items-center justify-between gap-3 rounded-xl bg-emerald-50/80 px-3.5 py-2.5 text-xs font-medium text-emerald-800 border border-emerald-200/70">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span><strong>Linked:</strong> {{ $selectedEmployeeLabel }}</span>
                        </div>
                        <button type="button" wire:click="clearEmployee" class="text-xs text-rose-600 hover:text-rose-800 font-semibold underline transition-colors">
                            Unlink
                        </button>
                    </div>
                @endif
                @error('employeeId')
                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Full Name + Email --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Full Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model.defer="name" id="name"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 transition-colors">
                    @error('name')<p class="text-xs text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Email Address <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" wire:model.defer="email" id="email"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 transition-colors">
                    @error('email')<p class="text-xs text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Active Status Toggle --}}
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Account Access Status</div>
                    <div class="text-xs text-slate-500">Enable or disable this user's ability to log in and access DISS.</div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" wire:model.defer="active" class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
                </label>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveProfile" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2 text-xs font-semibold text-white hover:bg-black transition-colors disabled:opacity-50 shadow-xs">
                    <x-bx-loader-alt class="w-3.5 h-3.5 animate-spin" wire:loading wire:target="saveProfile" />
                    <span wire:loading.remove wire:target="saveProfile">Save Profile</span>
                    <span wire:loading wire:target="saveProfile">Saving...</span>
                </button>
            </div>
        </form>
    @endif

    {{-- TAB 2: Roles & Access --}}
    @if ($tab === 'roles')
        <form wire:submit.prevent="saveRoles" class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-base font-bold text-slate-900 tracking-tight">Role-Based Access Control</h3>
                <p class="text-xs text-slate-500 mt-0.5">Assign administrative and operational roles. Permissions are inherited automatically from selected roles.</p>
            </div>

            {{-- Grouped Roles List --}}
            <div class="space-y-6">
                @foreach ($this->groupedRoles as $groupName => $roles)
                    <div class="space-y-2.5">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $groupName }}</span>
                            <div class="flex-1 h-px bg-slate-100"></div>
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @foreach ($roles as $role)
                                <label class="cursor-pointer group/role relative" title="{{ $this->getRoleDescription($role) }}">
                                    <input type="checkbox" value="{{ $role }}" wire:model.defer="selectedRoles" class="peer sr-only">
                                    <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-600 transition-all hover:border-slate-300 hover:bg-slate-50 peer-checked:!border-slate-900 peer-checked:!bg-slate-900 peer-checked:text-white peer-checked:shadow-xs select-none">
                                        <svg class="h-3 w-3 opacity-0 peer-checked/role:opacity-100 transition-opacity hidden peer-checked:block" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $role }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Legacy Direct Permissions Callout --}}
            @if (count($originalDirectPermissions) > 0)
                <div class="rounded-2xl border border-amber-200 overflow-hidden bg-amber-50/70 p-5 space-y-3">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <h4 class="text-xs font-bold text-amber-900 uppercase tracking-wide">Legacy Direct Permissions Assigned</h4>
                            <p class="text-xs text-amber-800 mt-0.5 leading-relaxed">
                                This account has direct permission grants. To maintain clean RBAC standards, uncheck these permissions to revoke them. Direct permission additions are locked in favor of roles.
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 pt-2">
                        @foreach ($originalDirectPermissions as $perm)
                            <label class="flex items-center gap-2 p-2 rounded-xl bg-white/60 border border-amber-200/60 hover:bg-white text-xs cursor-pointer transition-colors">
                                <input type="checkbox" value="{{ $perm }}" wire:model.defer="selectedDirectPermissions" class="h-4 w-4 rounded border-amber-300 text-slate-900 focus:ring-slate-950">
                                <span class="font-mono text-[11px] text-amber-950 truncate">{{ $perm }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Form Actions --}}
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveRoles" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2 text-xs font-semibold text-white hover:bg-black transition-colors disabled:opacity-50 shadow-xs">
                    <x-bx-loader-alt class="w-3.5 h-3.5 animate-spin" wire:loading wire:target="saveRoles" />
                    <span wire:loading.remove wire:target="saveRoles">Save Roles & Permissions</span>
                    <span wire:loading wire:target="saveRoles">Saving...</span>
                </button>
            </div>
        </form>
    @endif

    {{-- TAB 3: Activity & Usage --}}
    @if ($tab === 'activity')
        <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-xs space-y-6 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">Navigation & Usage Profile</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Aggregated access telemetry and route usage patterns for this account.</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200/60 self-start sm:self-auto">
                    {{ $visitStats['total_visits'] }} Total Page Visits
                </span>
            </div>

            {{-- Dormancy Alert Banner --}}
            @if ($isDormant)
                <div class="p-4 rounded-xl bg-amber-50/80 border border-amber-200/90 text-amber-900 text-xs flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <div class="font-bold text-amber-950">Inactive Account Alert (>30 Days)</div>
                        <div class="mt-0.5 text-amber-800 leading-relaxed">
                            This user is active but has not accessed any recorded system routes in the last 30 days. Consider verifying whether access is still required or deactivating the account.
                        </div>
                    </div>
                </div>
            @endif

            {{-- Activity Metrics --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200/70">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Last Active</div>
                    <div class="text-sm font-bold text-slate-900 mt-1">
                        {{ $visitStats['last_visited_at'] ? \Illuminate\Support\Carbon::parse($visitStats['last_visited_at'])->diffForHumans() : 'Never' }}
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">
                        {{ $visitStats['last_visited_at'] ? \Illuminate\Support\Carbon::parse($visitStats['last_visited_at'])->format('M d, Y H:i') : 'No recorded visits' }}
                    </div>
                </div>
                <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200/70">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Visits</div>
                    <div class="text-sm font-bold text-slate-900 mt-1">
                        {{ $visitStats['total_visits'] }} hits
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Across all named routes</div>
                </div>
                <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200/70">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Distinct Modules Visited</div>
                    <div class="text-sm font-bold text-slate-900 mt-1">
                        {{ $visitStats['distinct_routes'] }} features
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Unique section visits</div>
                </div>
            </div>

            {{-- Top Visited Routes --}}
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Top Visited Routes</div>
                @if (count($topVisitedPages) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach ($topVisitedPages as $visit)
                            <div class="p-3.5 rounded-xl border border-slate-200/70 bg-slate-50/40 hover:bg-slate-50 flex items-center justify-between transition-colors">
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
                    <div class="p-8 text-center text-slate-400 text-xs bg-slate-50/40 rounded-xl border border-dashed border-slate-200">
                        <x-bx-line-chart class="w-8 h-8 mx-auto text-slate-300 mb-1.5" />
                        No recorded page visits yet.
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- TAB 4: Security --}}
    @if ($tab === 'security')
        <div class="space-y-6">
            {{-- Password Reset Card --}}
            <form wire:submit.prevent="savePassword" class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
                <div class="border-b border-slate-100 pb-4">
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">Change Password</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Set a new password for this user account. The user must use this new password on their next login.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            New Password <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" wire:model.defer="password" id="password"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 transition-colors"
                            placeholder="Minimum 8 characters">
                        @error('password')<p class="text-xs text-rose-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Confirm New Password <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" wire:model.defer="password_confirmation" id="password_confirmation"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 transition-colors"
                            placeholder="Repeat new password">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                    <button type="submit" wire:loading.attr="disabled" wire:target="savePassword" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2 text-xs font-semibold text-white hover:bg-black transition-colors disabled:opacity-50 shadow-xs">
                        <x-bx-loader-alt class="w-3.5 h-3.5 animate-spin" wire:loading wire:target="savePassword" />
                        <span wire:loading.remove wire:target="savePassword">Update Password</span>
                        <span wire:loading wire:target="savePassword">Updating...</span>
                    </button>
                </div>
            </form>

            {{-- Email Verification Tool --}}
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-5">
                <div class="border-b border-slate-100 pb-4">
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">Administrative Email Verification</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Override or manage the email verification state of this account.</p>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl {{ $emailVerifiedAt ? 'bg-emerald-50/70 border border-emerald-200/70' : 'bg-amber-50/70 border border-amber-200/70' }}">
                    <div class="flex items-start sm:items-center gap-3">
                        @if ($emailVerifiedAt)
                            <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-emerald-900">Email Address Verified</div>
                                <div class="text-[11px] text-emerald-700 mt-0.5">
                                    Verified on {{ $emailVerifiedAt->format('F d, Y \a\t H:i:s') }}
                                </div>
                            </div>
                        @else
                            <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-amber-900">Email Address Unverified</div>
                                <div class="text-[11px] text-amber-700 mt-0.5">
                                    User has not completed the email OTP verification step.
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        @if ($emailVerifiedAt)
                            <button type="button" wire:click="toggleEmailVerification"
                                wire:confirm="Are you sure you want to mark this account's email as unverified?"
                                class="px-3.5 py-1.5 rounded-xl border border-rose-200 bg-white text-rose-700 hover:bg-rose-50 text-xs font-semibold transition-colors shadow-2xs">
                                Mark as Unverified
                            </button>
                        @else
                            <button type="button" wire:click="toggleEmailVerification"
                                wire:confirm="Are you sure you want to administratively verify this account's email address?"
                                class="px-3.5 py-1.5 rounded-xl border border-emerald-300 bg-emerald-600 text-white hover:bg-emerald-700 text-xs font-semibold transition-colors shadow-2xs">
                                Mark as Verified
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
