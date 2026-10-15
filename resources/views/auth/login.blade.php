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

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-rose-50/80 backdrop-blur-sm border border-rose-100 px-4 py-3.5">
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
                        @foreach ($errors->all() as $error)
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

        {{-- Login Help & Public Ticket Tracking Links --}}
        <div class="pt-3 flex flex-col items-center gap-2 text-center">
            <button type="button" onclick="openLoginHelpModal()"
                class="text-xs font-semibold text-slate-500 hover:text-indigo-600 transition-colors inline-flex items-center gap-1.5 focus:outline-none">
                <svg class="h-3.5 w-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                {{ __('ticketing.login_help.trigger') }}
            </button>
            <a href="{{ route('support.track') }}"
                class="text-xs font-semibold text-slate-400 hover:text-blue-600 transition-colors inline-flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                {{ __('ticketing.login_help.check_status') }}
            </a>
        </div>

    </form>
@endsection

@push('modals')
    {{-- Login Assistance Modal --}}
    <div id="loginHelpModal" class="fixed inset-0 z-[999] hidden items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm transition-opacity duration-200">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-100 space-y-4 relative">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="h-9 w-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">{{ __('ticketing.login_help.modal_title') }}</h3>
                        <p class="text-[11px] text-slate-500">{{ __('ticketing.login_help.modal_subtitle') }}</p>
                    </div>
                </div>
                <button type="button" onclick="closeLoginHelpModal()" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('support.login-assistance') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">{{ __('ticketing.login_help.nik') }} <span class="text-rose-500">*</span></label>
                    <input type="text" name="nik" id="nik_input" required value="{{ old('nik', old('nik_or_email')) }}"
                        placeholder="{{ __('ticketing.login_help.nik_placeholder') }}"
                        class="block w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-slate-50 shadow-sm">
                    <div id="nik-feedback" class="mt-1.5 min-h-[1.25rem] text-[11px] font-medium transition-all duration-200"></div>
                    @error('nik')
                        <span class="text-xs text-rose-500 mt-1 block font-semibold">{{ $message }}</span>
                    @enderror
                    @error('nik_or_email')
                        <span class="text-xs text-rose-500 mt-1 block font-semibold">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">{{ __('ticketing.login_help.issue_type') }} <span class="text-rose-500">*</span></label>
                    <select name="issue_type" required
                        class="block w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-slate-50 shadow-sm">
                        <option value="Password Reset / Account Locked">{{ __('ticketing.login_help.options.password_reset') }}</option>
                        <option value="New Employee Account Setup">{{ __('ticketing.login_help.options.account_setup') }}</option>
                        <option value="Login Error / Access Denied">{{ __('ticketing.login_help.options.login_error') }}</option>
                        <option value="Other Login Issue">{{ __('ticketing.login_help.options.other') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">{{ __('ticketing.login_help.phone') }}</label>
                    <input type="text" name="contact_number" value="{{ old('contact_number') }}"
                        placeholder="{{ __('ticketing.login_help.phone_placeholder') }}"
                        class="block w-full rounded-xl border border-slate-200 py-2.5 px-3 text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-slate-50 shadow-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">{{ __('ticketing.login_help.details') }} <span class="text-rose-500">*</span></label>
                    <textarea name="description" rows="3" required placeholder="{{ __('ticketing.login_help.details_placeholder') }}"
                        class="block w-full rounded-xl border border-slate-200 py-2 px-3 text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-slate-50 shadow-sm">{{ old('description') }}</textarea>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeLoginHelpModal()"
                        class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                        {{ __('ticketing.login_help.cancel') }}
                    </button>
                    <button type="submit"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700 shadow-md shadow-indigo-200 transition-colors">
                        {{ __('ticketing.login_help.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        function openLoginHelpModal() {
            const modal = document.getElementById('loginHelpModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }
        function closeLoginHelpModal() {
            const modal = document.getElementById('loginHelpModal');
            if (modal) {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLoginHelpModal();
            }
        });
        document.getElementById('loginHelpModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeLoginHelpModal();
            }
        });

        @if ($errors->has('nik') || $errors->has('nik_or_email') || $errors->has('issue_type') || $errors->has('description'))
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
