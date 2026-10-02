@extends('new.layouts.guest')

@section('title', 'Reset Password')

@section('content')
    <div class="mb-6 text-center">
        <h2 class="text-xl font-black text-slate-900 tracking-tight">
            Reset Password
        </h2>
        <p class="mt-1.5 text-sm text-slate-500 font-medium">
            Enter your email to receive a password reset link
        </p>
    </div>

    @if (session('status'))
        <div class="mb-5 flex items-start gap-3 rounded-2xl bg-emerald-50/80 backdrop-blur-sm border border-emerald-100 p-4">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100">
                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-emerald-700 leading-relaxed pt-1">
                {{ session('status') }}
            </p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-2xl bg-rose-50/80 backdrop-blur-sm border border-rose-100 p-4">
            <div class="flex items-start gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-100">
                    <svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-rose-700 mb-1">Please fix the following error:</p>
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li class="text-xs font-medium text-rose-600">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Email Address
            </label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                    class="block w-full rounded-xl border border-slate-200/60 bg-white/50 backdrop-blur-sm pl-12 pr-4 py-3 text-sm font-medium text-slate-900 placeholder-slate-400 shadow-sm outline-none transition-all duration-300
                           focus:border-blue-300 focus:bg-white focus:ring-4 focus:ring-blue-500/10 hover:border-slate-300"
                    placeholder="your.email@example.com">
            </div>
        </div>

        <button type="submit"
            class="group relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-blue-600 to-violet-600 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-200 transition-all duration-300
                   hover:shadow-xl hover:shadow-blue-300 hover:scale-[1.02] focus:outline-none focus:ring-4 focus:ring-blue-500/20 active:scale-[0.98]">
            <span class="relative z-10 flex items-center justify-center gap-2">
                Send Password Reset Link
            </span>
            <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-violet-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        </button>

        <div class="text-center pt-2">
            <a href="{{ route('login') }}" class="text-xs font-bold text-slate-500 hover:text-blue-600 transition-colors inline-flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Sign In
            </a>
        </div>
    </form>
@endsection
