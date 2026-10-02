@extends('new.layouts.app')

@section('title', 'Verify Email')

@section('content')
    <div class="max-w-lg mx-auto py-8">
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xl shadow-slate-900/5 p-8 text-center">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 mb-6">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>

            <h2 class="text-xl font-black text-slate-900 tracking-tight mb-2">
                Verify Your Email Address
            </h2>

            <p class="text-sm text-slate-500 font-medium leading-relaxed mb-6">
                Before proceeding, please check your inbox for a verification link. If you did not receive the email, we can gladly send you another one.
            </p>

            @if (session('resent'))
                <div class="mb-6 flex items-start gap-3 rounded-2xl bg-emerald-50 border border-emerald-100 p-4 text-left">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="text-xs font-semibold text-emerald-800 leading-normal pt-1">
                        A fresh verification link has been sent to your email address.
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 w-full rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-md shadow-blue-200 hover:bg-blue-700 active:scale-95 transition-all">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Resend Verification Email
                </button>
            </form>
        </div>
    </div>
@endsection
