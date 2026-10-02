<div class="max-w-6xl mx-auto space-y-6 pb-12">
    {{-- Header Banner & User Profile Summary --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 p-6 md:p-8 text-white shadow-xl">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -top-10 w-64 h-64 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4 sm:gap-5">
                <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl bg-gradient-to-tr from-blue-500 via-indigo-600 to-violet-500 flex items-center justify-center text-white text-xl sm:text-2xl font-black shadow-lg shadow-blue-500/30 ring-4 ring-white/10 shrink-0">
                    {{ strtoupper(mb_substr($user->name ?? 'U', 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white truncate">{{ $user->name }}</h1>
                        @if ($user->email_verified_at)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                Verified
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                Unverified
                            </span>
                        @endif
                    </div>
                    <p class="text-xs sm:text-sm text-slate-300 font-mono mt-0.5">{{ $user->email }}</p>

                    <div class="flex items-center gap-2 mt-3 flex-wrap text-xs">
                        @if ($user->department)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-white/10 backdrop-blur-md text-slate-200 border border-white/10 font-medium">
                                <svg class="w-3.5 h-3.5 mr-1.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                {{ $user->department->name }}
                            </span>
                        @endif

                        @if ($employee && $employee->nik)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-white/5 text-slate-300 border border-white/10 font-mono">
                                NIK: {{ $employee->nik }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="shrink-0 flex items-center gap-3">
                <a href="{{ route('signatures.manage') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-semibold backdrop-blur-md border border-white/10 transition-all active:scale-95">
                    <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    Manage Signatures
                </a>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-1.5 flex items-center gap-1.5 overflow-x-auto scrollbar-none">
        {{-- Tab: Profile --}}
        <button type="button" wire:click="setTab('profile')"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all whitespace-nowrap {{ $tab === 'profile' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}">
            <svg class="w-4 h-4 {{ $tab === 'profile' ? 'text-white' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
            Profile & Identity
        </button>

        {{-- Tab: Security --}}
        <button type="button" wire:click="setTab('security')"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all whitespace-nowrap {{ $tab === 'security' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}">
            <svg class="w-4 h-4 {{ $tab === 'security' ? 'text-white' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
            Security & Password
        </button>

        {{-- Tab: Notifications --}}
        <button type="button" wire:click="setTab('notifications')"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all whitespace-nowrap {{ $tab === 'notifications' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}">
            <svg class="w-4 h-4 {{ $tab === 'notifications' ? 'text-white' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            Notifications
        </button>

        {{-- Tab: Signatures --}}
        <button type="button" wire:click="setTab('signatures')"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all whitespace-nowrap {{ $tab === 'signatures' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}">
            <svg class="w-4 h-4 {{ $tab === 'signatures' ? 'text-white' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
            </svg>
            Digital Signatures
        </button>

        {{-- Tab: System Abilities (Strictly Super-Admin Only) --}}
        @if ($isSuperAdmin)
            <button type="button" wire:click="setTab('abilities')"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all whitespace-nowrap {{ $tab === 'abilities' ? 'bg-indigo-600 text-white shadow-sm' : 'text-indigo-700 bg-indigo-50/70 hover:bg-indigo-100/80' }}">
                <svg class="w-4 h-4 {{ $tab === 'abilities' ? 'text-white' : 'text-indigo-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                System Abilities (Admin)
            </button>
        @endif
    </div>

    {{-- ========================================================= --}}
    {{-- TAB 1: PROFILE & IDENTITY --}}
    {{-- ========================================================= --}}
    @if ($tab === 'profile')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Edit Account Form --}}
            <div class="lg:col-span-2 space-y-6">
                @if (session('profile_success'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-xs font-semibold text-emerald-800 flex items-center gap-3 shadow-xs">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <span>{{ session('profile_success') }}</span>
                    </div>
                @endif

                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 md:p-8 shadow-xs">
                    <div class="border-b border-slate-100 pb-4 mb-6">
                        <h2 class="text-base font-bold text-slate-900">Personal Information</h2>
                        <p class="text-xs text-slate-500 mt-1">Manage your basic account identity. Note that changing your email address will require 6-digit OTP verification.</p>
                    </div>

                    <form wire:submit.prevent="updateProfile" class="space-y-5">
                        {{-- Name --}}
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                Full Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" wire:model="name"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 font-medium focus:bg-white focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10 transition-all">
                            @error('name')
                                <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email Address --}}
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                Email Address <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="email" wire:model="email"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 font-medium focus:bg-white focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10 transition-all pr-24">
                                <div class="absolute inset-y-0 right-3 flex items-center">
                                    @if ($user->email_verified_at && strtolower(trim($email)) === strtolower(trim($user->email)))
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-100 pointer-events-none">
                                            Verified
                                        </span>
                                    @elseif (!$user->email_verified_at && strtolower(trim($email)) === strtolower(trim($user->email)))
                                        <button type="button" wire:click="verifyCurrentEmail" wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800 bg-amber-100/80 hover:bg-amber-200 px-2 py-0.5 rounded-lg border border-amber-300 transition-colors">
                                            Verify now
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <p class="mt-1.5 text-[11px] text-slate-500">
                                A 6-digit OTP will be dispatched to the new email address before changes are saved.
                            </p>
                            @error('email')
                                <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Action Buttons --}}
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                            <button type="submit" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-black shadow-md shadow-slate-900/10 transition-all active:scale-95 disabled:opacity-50">
                                <span wire:loading.remove wire:target="updateProfile">Save Changes</span>
                                <span wire:loading wire:target="updateProfile">Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- HR Master Data (Linked Employee) --}}
            <div class="space-y-6">
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs">
                    <div class="flex items-center gap-3 pb-4 mb-4 border-b border-slate-100">
                        <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Employment Record</h3>
                            <p class="text-[11px] text-slate-400">Verified HR Master Data</p>
                        </div>
                    </div>

                    @if ($employee)
                        <dl class="space-y-3.5 text-xs">
                            <div class="flex justify-between items-center py-1.5 border-b border-slate-50">
                                <dt class="text-slate-500 font-medium">NIK</dt>
                                <dd class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded">{{ $employee->nik }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-1.5 border-b border-slate-50">
                                <dt class="text-slate-500 font-medium">Department</dt>
                                <dd class="font-semibold text-slate-800">{{ $user->department?->name ?? $employee->dept_code ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-1.5 border-b border-slate-50">
                                <dt class="text-slate-500 font-medium">Position</dt>
                                <dd class="font-semibold text-slate-800">{{ $employee->position ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-1.5 border-b border-slate-50">
                                <dt class="text-slate-500 font-medium">Branch / Plant</dt>
                                <dd class="font-semibold text-slate-800">{{ $employee->branch ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-1.5 border-b border-slate-50">
                                <dt class="text-slate-500 font-medium">Employment Type</dt>
                                <dd class="font-semibold text-slate-800">{{ ucfirst($employee->employment_type ?? 'Regular') }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-1.5">
                                <dt class="text-slate-500 font-medium">Status</dt>
                                <dd>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Active
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    @else
                        <div class="text-center py-6 text-slate-400">
                            <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                            </svg>
                            <p class="text-xs font-medium text-slate-600">No linked employee record</p>
                            <p class="text-[11px] text-slate-400 mt-1">Contact HR/Admin if this account belongs to an active employee.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ========================================================= --}}
    {{-- TAB 2: SECURITY & PASSWORD --}}
    {{-- ========================================================= --}}
    @if ($tab === 'security')
        <div class="max-w-2xl mx-auto space-y-6">
            @if (session('password_success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-xs font-semibold text-emerald-800 flex items-center gap-3 shadow-xs">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    <span>{{ session('password_success') }}</span>
                </div>
            @endif

            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 md:p-8 shadow-xs">
                <div class="border-b border-slate-100 pb-4 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-slate-100 text-slate-800 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Change Account Password</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Ensure your account is protected with a strong, distinct password.</p>
                        </div>
                    </div>
                </div>

                <form wire:submit.prevent="changePassword" class="space-y-4">
                    {{-- Current Password --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                            Current Password <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" wire:model="current_password"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 focus:bg-white focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10 transition-all">
                        @error('current_password')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- New Password --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                New Password <span class="text-rose-500">*</span>
                            </label>
                            <input type="password" wire:model="password"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 focus:bg-white focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10 transition-all">
                            @error('password')
                                <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                Confirm New Password <span class="text-rose-500">*</span>
                            </label>
                            <input type="password" wire:model="password_confirmation"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 focus:bg-white focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10 transition-all">
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 text-[11px] text-slate-600 space-y-1">
                        <p class="font-semibold text-slate-700">Password requirements:</p>
                        <ul class="list-disc list-inside space-y-0.5 text-slate-500">
                            <li>Minimum 8 characters in length</li>
                            <li>Include letters and numbers for higher protection</li>
                        </ul>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                        <button type="submit" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-black shadow-md shadow-slate-900/10 transition-all active:scale-95 disabled:opacity-50">
                            <span wire:loading.remove wire:target="changePassword">Update Password</span>
                            <span wire:loading wire:target="changePassword">Updating...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ========================================================= --}}
    {{-- TAB 3: NOTIFICATION PREFERENCES --}}
    {{-- ========================================================= --}}
    @if ($tab === 'notifications')
        <div class="max-w-3xl mx-auto space-y-6">
            @if (session('notification_success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-xs font-semibold text-emerald-800 flex items-center gap-3 shadow-xs">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    <span>{{ session('notification_success') }}</span>
                </div>
            @endif

            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 md:p-8 shadow-xs">
                <div class="border-b border-slate-100 pb-4 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Email Delivery Mode</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Control how and when workflow notifications are sent to your inbox.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    {{-- Both --}}
                    <button type="button" wire:click="$set('global_mode', 'both')"
                        class="p-5 rounded-2xl border-2 text-left transition-all relative {{ $global_mode === 'both' ? 'border-indigo-600 bg-indigo-50/50 ring-4 ring-indigo-500/10' : 'border-slate-100 bg-slate-50/50 hover:border-slate-200' }}">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-900">Both</span>
                            @if ($global_mode === 'both')
                                <span class="h-2 w-2 rounded-full bg-indigo-600"></span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-600 font-medium leading-relaxed">Immediate action alerts plus daily morning digests.</p>
                        <span class="inline-block mt-3 px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700">Recommended</span>
                    </button>

                    {{-- Immediate --}}
                    <button type="button" wire:click="$set('global_mode', 'immediate')"
                        class="p-5 rounded-2xl border-2 text-left transition-all relative {{ $global_mode === 'immediate' ? 'border-blue-600 bg-blue-50/50 ring-4 ring-blue-500/10' : 'border-slate-100 bg-slate-50/50 hover:border-slate-200' }}">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-900">Immediate</span>
                            @if ($global_mode === 'immediate')
                                <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-600 font-medium leading-relaxed">Receive emails immediately as soon as action is required.</p>
                    </button>

                    {{-- Daily Summary --}}
                    <button type="button" wire:click="$set('global_mode', 'daily_summary')"
                        class="p-5 rounded-2xl border-2 text-left transition-all relative {{ $global_mode === 'daily_summary' ? 'border-amber-600 bg-amber-50/50 ring-4 ring-amber-500/10' : 'border-slate-100 bg-slate-50/50 hover:border-slate-200' }}">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-900">Daily Summary</span>
                            @if ($global_mode === 'daily_summary')
                                <span class="h-2 w-2 rounded-full bg-amber-600"></span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-600 font-medium leading-relaxed">Receive a single consolidated morning digest of pending items.</p>
                    </button>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('account.notifications') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors">
                        Advanced Module Overrides &rarr;
                    </a>

                    <button type="button" wire:click="saveNotifications" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-black shadow-md shadow-slate-900/10 transition-all active:scale-95 disabled:opacity-50">
                        <span wire:loading.remove wire:target="saveNotifications">Save Preferences</span>
                        <span wire:loading wire:target="saveNotifications">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ========================================================= --}}
    {{-- TAB 4: DIGITAL SIGNATURES --}}
    {{-- ========================================================= --}}
    @if ($tab === 'signatures')
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 md:p-8 shadow-xs">
                <div class="border-b border-slate-100 pb-4 mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Digital Signature Status</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Manage your digital specimen used for signing approval documents.</p>
                        </div>
                    </div>

                    <div>
                        @if ($hasDefaultSignature)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Active Signature
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> No Default Signature
                            </span>
                        @endif
                    </div>
                </div>

                <div class="bg-slate-50/70 rounded-2xl p-6 border border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <h4 class="text-sm font-bold text-slate-800">Workflow Document Signatures</h4>
                        <p class="text-xs text-slate-500 mt-1 max-w-lg">
                            Your signature specimen is cryptographically stamped on approved purchase requests, overtime requests, budget reports, and compliance forms.
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0">
                        <a href="{{ route('signatures.manage') }}"
                            class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-black text-white text-xs font-bold transition-all shadow-sm">
                            Manage Signatures
                        </a>
                        <a href="{{ route('signatures.capture') }}"
                            class="px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-800 text-xs font-bold transition-all shadow-xs">
                            Capture New
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ========================================================= --}}
    {{-- TAB 5: SYSTEM ABILITIES (SUPER-ADMIN ONLY) --}}
    {{-- ========================================================= --}}
    @if ($tab === 'abilities' && $isSuperAdmin && $abilitiesData)
        <div class="space-y-6">
            {{-- Super Admin Banner --}}
            <div class="rounded-3xl border border-indigo-200 bg-gradient-to-r from-indigo-50 via-purple-50 to-blue-50 p-6 shadow-xs">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-md shadow-indigo-300">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-indigo-950">Super Administrator Global Access Granted</h3>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-indigo-200 text-indigo-900">
                                Gate::before Bypass
                            </span>
                        </div>
                        <p class="text-xs text-indigo-800 mt-1 leading-relaxed">
                            As a Super Administrator, your account possesses unrestricted authorization across all modules and actions defined in the system. Below is the full directory of {{ $abilitiesData['total_permissions_count'] }} registered permissions across {{ count($abilitiesData['modules']) }} modules.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Filter & Search Toolbar --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="relative w-full sm:w-80">
                    <input type="text" wire:model.live.debounce.300ms="abilitiesSearch"
                        placeholder="Search permissions or modules..."
                        class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50/50 text-xs text-slate-900 focus:bg-white focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 transition-all">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>

                <div class="flex items-center gap-4 text-xs text-slate-500 font-medium">
                    <span>Total: <strong class="text-slate-800">{{ $abilitiesData['total_permissions_count'] }}</strong></span>
                    <span>•</span>
                    <span>Signature Required: <strong class="text-amber-600">{{ $abilitiesData['signature_permissions_count'] }}</strong></span>
                </div>
            </div>

            {{-- Modules Grid --}}
            <div class="space-y-4">
                @forelse ($abilitiesData['modules'] as $module)
                    <div class="rounded-3xl border border-slate-200/80 bg-white overflow-hidden shadow-xs">
                        <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                                <h3 class="text-sm font-bold text-slate-900">{{ $module['name'] }}</h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200/80 text-slate-600">
                                    {{ count($module['permissions']) }} abilities
                                </span>
                            </div>
                        </div>

                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach ($module['permissions'] as $perm)
                                <div class="p-3 rounded-2xl border border-slate-100 bg-slate-50/40 hover:bg-slate-50 transition-all flex flex-col justify-between gap-2">
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="text-xs font-bold text-slate-800 leading-tight">
                                            {{ $perm['label'] }}
                                        </span>
                                        <span class="h-2 w-2 rounded-full bg-emerald-500 shrink-0 mt-1" title="Active"></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-100/60">
                                        <span class="font-mono text-[10px] text-slate-400 truncate">{{ $perm['name'] }}</span>
                                        @if ($perm['requires_signature'])
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0">
                                                ✍️ Signature
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center bg-white rounded-3xl border border-slate-200 text-slate-500">
                        <p class="text-sm font-semibold">No permissions match your search query "{{ $abilitiesSearch }}".</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    {{-- ========================================================= --}}
    {{-- EMAIL CHANGE 6-DIGIT OTP VERIFICATION MODAL --}}
    {{-- ========================================================= --}}
    @if ($showEmailOtpModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-6 sm:p-8 shadow-2xl relative animate-in fade-in zoom-in-95 duration-200">
                <button type="button" wire:click="cancelEmailChange" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="text-center space-y-3 mb-6">
                    <div class="h-14 w-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto shadow-inner">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </div>

                    <h3 class="text-lg font-black text-slate-900 tracking-tight">{{ strtolower(trim($pendingEmail)) === strtolower(trim($user->email)) ? 'Verify Your Email Address' : 'Verify Your New Email' }}</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        We sent a 6-digit confirmation code to:
                        <br><strong class="font-mono text-slate-900 text-sm">{{ $pendingEmail }}</strong>
                    </p>
                </div>

                <form wire:submit.prevent="confirmEmailChange" class="space-y-4">
                    <div>
                        <label class="block text-center text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                            Enter 6-Digit Code
                        </label>
                        <input type="text" wire:model="emailOtp" maxlength="6" autofocus
                            placeholder="123456"
                            class="w-full text-center text-2xl font-mono tracking-[0.5em] rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 focus:bg-white focus:border-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-900/10 transition-all font-bold">
                        @error('emailOtp')
                            <p class="mt-2 text-center text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2 space-y-2">
                        <button type="submit" wire:loading.attr="disabled"
                            class="w-full py-3 rounded-xl bg-slate-900 hover:bg-black text-white text-xs font-bold shadow-md shadow-slate-900/10 transition-all active:scale-98 disabled:opacity-50">
                            <span wire:loading.remove wire:target="confirmEmailChange">{{ strtolower(trim($pendingEmail)) === strtolower(trim($user->email)) ? 'Verify Email' : 'Verify & Update Email' }}</span>
                            <span wire:loading wire:target="confirmEmailChange">Verifying...</span>
                        </button>

                        <div class="flex items-center justify-between text-xs pt-2">
                            <button type="button" wire:click="resendEmailOtp"
                                class="text-blue-600 hover:text-blue-800 font-bold transition-colors">
                                Resend code
                            </button>
                            <button type="button" wire:click="cancelEmailChange"
                                class="text-slate-400 hover:text-slate-600 font-medium transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
