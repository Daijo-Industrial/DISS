@extends('new.layouts.guest')

@section('title', 'Login')

@section('content')
    {{-- Header --}}
    <div class="mb-6 text-center">
        <h2 class="text-xl font-black text-slate-900 tracking-tight">
            {{ __('auth.welcome_back') }}
        </h2>
        <p class="mt-1.5 text-sm text-slate-500 font-medium">
            {{ __('auth.sign_in_subtitle') }}
        </p>
    </div>

    {{-- Session / error messages --}}
    @if (session('status'))
        <div
            class="mb-4 flex flex-col gap-2 rounded-2xl bg-emerald-50/80 backdrop-blur-sm border border-emerald-100 p-4">
            <div class="flex items-start gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100">
                    <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-emerald-800 leading-relaxed">
                        {{ session('status') }}
                    </p>
                    @if (session('submitted_ticket_number') && session('submitted_ticket_nik'))
                        <div class="mt-2.5">
                            <a href="{{ route('support.track', ['ticket' => session('submitted_ticket_number'), 'nik' => session('submitted_ticket_nik')]) }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition-colors">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>{{ __('ticketing.login_help.track_progress') }}</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @php
        $modalErrorKeys = ['nik', 'nik_or_email', 'issue_type', 'description', 'contact_number'];
        $loginErrors = collect($errors->getMessages())
            ->except($modalErrorKeys)
            ->flatten();
    @endphp

    @if ($loginErrors->isNotEmpty())
        <div role="alert" class="mb-4 rounded-2xl bg-rose-50/80 backdrop-blur-sm border border-rose-100 px-4 py-3.5">
            <div class="flex items-start gap-3 mb-2">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-100">
                    <svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-rose-700 mb-2">{{ __('auth.errors_title') }}</p>
                    <ul class="space-y-1">
                        @foreach ($loginErrors as $error)
                            <li class="text-xs font-medium text-rose-600 flex items-start gap-1.5">
                                <span class="text-rose-400 mt-0.5">•</span>
                                <span>{{ $error }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Login Form --}}
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                {{ __('auth.email_address') }}
            </label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="block w-full rounded-xl border border-slate-200/60 bg-white/50 backdrop-blur-sm pl-12 pr-4 py-3 text-sm font-medium text-slate-900 placeholder-slate-400 shadow-sm outline-none transition-all duration-300
                           focus:border-blue-300 focus:bg-white focus:ring-4 focus:ring-blue-500/10
                           hover:border-slate-300"
                    placeholder="your.email@example.com">
            </div>
        </div>

        {{-- Password --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    {{ __('auth.password_label') }}
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                        class="text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors">
                        {{ __('auth.forgot_password') }}
                    </a>
                @endif
            </div>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    class="block w-full rounded-xl border border-slate-200/60 bg-white/50 backdrop-blur-sm pl-12 pr-4 py-3 text-sm font-medium text-slate-900 placeholder-slate-400 shadow-sm outline-none transition-all duration-300
                           focus:border-blue-300 focus:bg-white focus:ring-4 focus:ring-blue-500/10
                           hover:border-slate-300"
                    placeholder="••••••••">
            </div>
        </div>

        {{-- Remember me --}}
        <div class="flex items-center justify-between">
            <label class="inline-flex items-center gap-2.5 cursor-pointer group">
                <input type="checkbox" name="remember"
                    class="h-4 w-4 rounded-md border-slate-300 text-blue-600 focus:ring-2 focus:ring-blue-500/20 focus:ring-offset-0 transition-all"
                    {{ old('remember') ? 'checked' : '' }}>
                <span class="text-sm font-medium text-slate-600 group-hover:text-slate-900 transition-colors">{{ __('auth.remember_me') }}</span>
            </label>
        </div>

        {{-- Submit --}}
        <button type="submit"
            class="group relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-blue-600 to-violet-600 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-200 transition-all duration-300
                   hover:shadow-xl hover:shadow-blue-300 hover:scale-[1.02]
                   focus:outline-none focus:ring-4 focus:ring-blue-500/20
                   active:scale-[0.98]">
            <span class="relative z-10 flex items-center justify-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                {{ __('auth.sign_in') }}
            </span>
            <div
                class="absolute inset-0 bg-gradient-to-r from-blue-500 to-violet-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            </div>
        </button>

        {{-- IT Support Help Quick Trigger --}}
        <div class="pt-3 text-center">
            <button type="button" onclick="openLoginHelpModal('new')"
                class="text-xs font-semibold text-slate-500 hover:text-indigo-600 transition-colors inline-flex items-center gap-1.5 focus:outline-none">
                <svg class="h-3.5 w-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span>{{ __('ticketing.login_help.trigger') }}</span>
            </button>
        </div>

    </form>
@endsection

@push('modals')
    {{-- Floating IT Concierge Bubble Button (Identical to inside-app IT Concierge) --}}
    <button type="button"
        id="guestConciergeToggleBtn"
        onclick="toggleLoginHelpModal()"
        aria-expanded="false"
        aria-label="Toggle IT Concierge"
        class="fixed bottom-6 right-6 z-[100] flex h-14 w-14 items-center justify-center rounded-full bg-slate-900 text-white shadow-2xl shadow-indigo-500/30 hover:bg-slate-800 transition-transform hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        <svg id="guestConciergeIconClosed" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <svg id="guestConciergeIconOpen" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    {{-- Guest IT Support Concierge Panel --}}
    <div id="loginHelpModal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="loginHelpModalTitle"
        class="fixed bottom-24 right-4 sm:right-6 z-[100] hidden w-[calc(100vw-2rem)] sm:w-[420px] rounded-3xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex-col max-h-[calc(100vh-8rem)] transition-all duration-200">
        
        {{-- Header --}}
        <div class="bg-slate-900 px-6 py-5 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h3 id="loginHelpModalTitle" class="text-sm font-black text-white">{{ __('ticketing.login_help.modal_title') }}</h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">{{ __('ticketing.login_help.modal_subtitle') }}</p>
                </div>
            </div>
            <button type="button" onclick="closeLoginHelpModal()" aria-label="Close"
                class="text-slate-400 hover:text-white transition-colors p-1">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Tabs --}}
        <div role="tablist" aria-label="Support options" class="flex border-b border-slate-100 bg-slate-50/50 shrink-0">
            <button type="button" role="tab" id="guestTabBtnNew" aria-selected="true" aria-controls="guestTabContentNew"
                onclick="switchGuestTab('new')"
                class="flex-1 py-3 text-xs font-bold uppercase tracking-widest text-indigo-600 border-b-2 border-indigo-600 bg-white transition-all">
                {{ __('ticketing.concierge.tabs.new') }}
            </button>
            <button type="button" role="tab" id="guestTabBtnTrack" aria-selected="false" aria-controls="guestTabContentTrack"
                onclick="switchGuestTab('track')"
                class="flex-1 py-3 text-xs font-bold uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">
                {{ __('ticketing.login_help.check_status') }}
            </button>
        </div>

        {{-- Content Area --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-4">
            {{-- Tab 1: New Ticket Request --}}
            <div id="guestTabContentNew" role="tabpanel" aria-labelledby="guestTabBtnNew">
                <form method="POST" action="{{ route('support.login-assistance') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="nik_input" class="block text-xs font-bold text-slate-700 mb-1">
                            {{ __('ticketing.login_help.nik') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="nik" id="nik_input" required value="{{ old('nik', old('nik_or_email')) }}"
                            placeholder="{{ __('ticketing.login_help.nik_placeholder') }}"
                            class="block w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-slate-50 shadow-sm">
                        <div id="nik-feedback" aria-live="polite" class="mt-1.5 min-h-[1.25rem] text-[11px] font-medium transition-all duration-200"></div>
                        @if ($errors->has('nik') || $errors->has('nik_or_email'))
                            <span class="text-xs text-rose-500 mt-1 block font-semibold">{{ $errors->first('nik') ?: $errors->first('nik_or_email') }}</span>
                        @endif
                    </div>

                    <div>
                        <label for="issue_type" class="block text-xs font-bold text-slate-700 mb-1">
                            {{ __('ticketing.login_help.issue_type') }} <span class="text-rose-500">*</span>
                        </label>
                        <select name="issue_type" id="issue_type" required
                            class="block w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-slate-50 shadow-sm">
                            <option value="Password Reset / Account Locked" @selected(old('issue_type') === 'Password Reset / Account Locked')>{{ __('ticketing.login_help.options.password_reset') }}</option>
                            <option value="New Employee Account Setup" @selected(old('issue_type') === 'New Employee Account Setup')>{{ __('ticketing.login_help.options.account_setup') }}</option>
                            <option value="Login Error / Access Denied" @selected(old('issue_type') === 'Login Error / Access Denied')>{{ __('ticketing.login_help.options.login_error') }}</option>
                            <option value="Hardware / Computer / Printer" @selected(old('issue_type') === 'Hardware / Computer / Printer')>{{ __('ticketing.login_help.options.hardware') }}</option>
                            <option value="Network / Wi-Fi / Connectivity" @selected(old('issue_type') === 'Network / Wi-Fi / Connectivity')>{{ __('ticketing.login_help.options.network') }}</option>
                            <option value="Software / Application Issue" @selected(old('issue_type') === 'Software / Application Issue')>{{ __('ticketing.login_help.options.software') }}</option>
                            <option value="General Inquiry / Other IT Issue" @selected(old('issue_type') === 'General Inquiry / Other IT Issue')>{{ __('ticketing.login_help.options.other') }}</option>
                        </select>
                        @error('issue_type')
                            <span class="text-xs text-rose-500 mt-1 block font-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="contact_number" class="block text-xs font-bold text-slate-700 mb-1">
                            {{ __('ticketing.login_help.phone') }}
                        </label>
                        <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number') }}"
                            placeholder="{{ __('ticketing.login_help.phone_placeholder') }}"
                            class="block w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-slate-50 shadow-sm">
                        @error('contact_number')
                            <span class="text-xs text-rose-500 mt-1 block font-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-bold text-slate-700 mb-1">
                            {{ __('ticketing.login_help.details') }} <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="description" id="description" rows="3" required
                            placeholder="{{ __('ticketing.login_help.details_placeholder') }}"
                            class="block w-full rounded-xl border border-slate-200 py-2 px-3 text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-slate-50 shadow-sm">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="text-xs text-rose-500 mt-1 block font-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="pt-1">
                        <button type="submit"
                            class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-xs font-bold text-white hover:bg-indigo-700 shadow-md shadow-indigo-200 transition-colors flex items-center justify-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <span>{{ __('ticketing.login_help.submit') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Tab 2: Track Status --}}
            <div id="guestTabContentTrack" role="tabpanel" aria-labelledby="guestTabBtnTrack" class="hidden space-y-4">
                <div class="rounded-2xl bg-indigo-50/60 border border-indigo-100 p-4">
                    <p class="text-xs font-semibold text-indigo-950 leading-relaxed">
                        {{ __('ticketing.track.subtitle') }}
                    </p>
                </div>

                <form method="GET" action="{{ route('support.track') }}" class="space-y-4">
                    <div>
                        <label for="track_ticket" class="block text-xs font-bold text-slate-700 mb-1">
                            {{ __('ticketing.track.ticket_number') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="ticket" id="track_ticket" required
                            value="{{ old('ticket', request('ticket', session('submitted_ticket_number'))) }}"
                            placeholder="{{ __('ticketing.track.ticket_number_placeholder') }}"
                            class="block w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-slate-50 shadow-sm uppercase font-mono">
                    </div>

                    <div>
                        <label for="track_nik" class="block text-xs font-bold text-slate-700 mb-1">
                            {{ __('ticketing.track.nik') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="nik" id="track_nik" required
                            value="{{ old('nik', request('nik', session('submitted_ticket_nik'))) }}"
                            placeholder="{{ __('ticketing.track.nik_placeholder') }}"
                            class="block w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-slate-50 shadow-sm">
                    </div>

                    <div class="pt-1">
                        <button type="submit"
                            class="w-full rounded-xl bg-slate-900 px-4 py-3 text-xs font-bold text-white hover:bg-slate-800 shadow-md shadow-slate-300 transition-colors flex items-center justify-center gap-2">
                            <svg class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span>{{ __('ticketing.track.check_button') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        function openLoginHelpModal(tab = 'new') {
            const modal = document.getElementById('loginHelpModal');
            const iconClosed = document.getElementById('guestConciergeIconClosed');
            const iconOpen = document.getElementById('guestConciergeIconOpen');
            const toggleBtn = document.getElementById('guestConciergeToggleBtn');

            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                if (iconClosed) iconClosed.classList.add('hidden');
                if (iconOpen) iconOpen.classList.remove('hidden');
                if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
                switchGuestTab(tab);
                setTimeout(() => {
                    document.getElementById(tab === 'new' ? 'nik_input' : 'track_ticket')?.focus();
                }, 60);
            }
        }

        function closeLoginHelpModal() {
            const modal = document.getElementById('loginHelpModal');
            const iconClosed = document.getElementById('guestConciergeIconClosed');
            const iconOpen = document.getElementById('guestConciergeIconOpen');
            const toggleBtn = document.getElementById('guestConciergeToggleBtn');

            if (modal) {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
                if (iconClosed) iconClosed.classList.remove('hidden');
                if (iconOpen) iconOpen.classList.add('hidden');
                if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
            }
        }

        function toggleLoginHelpModal() {
            const modal = document.getElementById('loginHelpModal');
            if (modal && !modal.classList.contains('hidden')) {
                closeLoginHelpModal();
            } else {
                openLoginHelpModal('new');
            }
        }

        function switchGuestTab(tab) {
            const contentNew = document.getElementById('guestTabContentNew');
            const contentTrack = document.getElementById('guestTabContentTrack');
            const btnNew = document.getElementById('guestTabBtnNew');
            const btnTrack = document.getElementById('guestTabBtnTrack');

            if (tab === 'new') {
                if (contentNew) contentNew.classList.remove('hidden');
                if (contentTrack) contentTrack.classList.add('hidden');
                if (btnNew) {
                    btnNew.className = 'flex-1 py-3 text-xs font-bold uppercase tracking-widest text-indigo-600 border-b-2 border-indigo-600 bg-white transition-all';
                    btnNew.setAttribute('aria-selected', 'true');
                }
                if (btnTrack) {
                    btnTrack.className = 'flex-1 py-3 text-xs font-bold uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all';
                    btnTrack.setAttribute('aria-selected', 'false');
                }
            } else {
                if (contentNew) contentNew.classList.add('hidden');
                if (contentTrack) contentTrack.classList.remove('hidden');
                if (btnNew) {
                    btnNew.className = 'flex-1 py-3 text-xs font-bold uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all';
                    btnNew.setAttribute('aria-selected', 'false');
                }
                if (btnTrack) {
                    btnTrack.className = 'flex-1 py-3 text-xs font-bold uppercase tracking-widest text-indigo-600 border-b-2 border-indigo-600 bg-white transition-all';
                    btnTrack.setAttribute('aria-selected', 'true');
                }
            }
        }

        const openSupportModal = openLoginHelpModal;
        const closeSupportModal = closeLoginHelpModal;

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLoginHelpModal();
            }
        });

        document.addEventListener('click', function(e) {
            const modal = document.getElementById('loginHelpModal');
            const toggleBtn = document.getElementById('guestConciergeToggleBtn');
            if (modal && !modal.classList.contains('hidden')) {
                if (!modal.contains(e.target) && (!toggleBtn || !toggleBtn.contains(e.target))) {
                    if (!e.target.closest('[onclick*="openLoginHelpModal"]')) {
                        closeLoginHelpModal();
                    }
                }
            }
        });

        @if ($errors->hasAny($modalErrorKeys ?? ['nik', 'nik_or_email', 'issue_type', 'description', 'contact_number']))
            openLoginHelpModal();
        @endif

        // Client-side format validation strictly for NIK (Option 1: Zero public network requests / anti-enumeration)
        (function() {
            const input = document.getElementById('nik_input');
            const feedback = document.getElementById('nik-feedback');
            if (!input || !feedback) return;

            input.addEventListener('input', function() {
                const val = input.value.trim();

                if (!val) {
                    feedback.replaceChildren();
                    return;
                }

                // If user enters an email: show disabled warning
                if (val.includes('@')) {
                    feedback.replaceChildren();
                    const span = document.createElement('span');
                    span.className = 'inline-flex items-center gap-1 text-rose-600 font-semibold';
                    span.textContent = '✕ ' + @json(__('ticketing.login_help.corporate_email_disabled'));
                    feedback.appendChild(span);
                    return;
                }

                // If user is entering an NIK: check syntax (alphanumeric, length 3 to 20, no spaces)
                const nikRegex = /^[a-zA-Z0-9]{3,20}$/;
                feedback.replaceChildren();
                if (nikRegex.test(val)) {
                    const span = document.createElement('span');
                    span.className = 'inline-flex items-center gap-1 text-emerald-600 font-semibold';
                    span.textContent = '✓ ' + @json(__('ticketing.login_help.nik_format_valid'));
                    feedback.appendChild(span);
                }
            });
        })();
    </script>
@endpush
