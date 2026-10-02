@extends('new.layouts.app')

@section('title', 'Email Notification Settings')

@section('content')
    <div class="max-w-4xl mx-auto py-4">
        {{-- Page Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-black tracking-tight text-slate-900">Email Notification Settings</h1>
            <p class="text-sm font-medium text-slate-500 mt-1">Configure recipient and CC addresses for automated system notifications</p>
        </div>

        {{-- Settings Card --}}
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-6 sm:p-8">
                <form method="POST" action="{{ route('email.update') }}" class="space-y-6">
                    @csrf

                    {{-- Feature Selection --}}
                    <div>
                        <label for="feature" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Notification Feature
                        </label>
                        <div class="relative">
                            <select id="feature" name="feature"
                                class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 outline-none transition cursor-pointer">
                                @foreach ($featureNames as $feature)
                                    <option value="{{ $feature }}">{{ ucfirst($feature) }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-[11px] font-medium text-slate-400 mt-1.5">Select which system event recipients to configure.</p>
                    </div>

                    {{-- Primary Recipient (To) --}}
                    <div>
                        <label for="to" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            To (Primary Recipients)
                        </label>
                        <input id="to" type="text" name="to" required autofocus
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3 text-sm font-medium text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 outline-none transition @error('to') border-rose-300 ring-rose-500/10 @enderror"
                            placeholder="recipient@example.com">
                        @error('to')
                            <p class="text-xs font-medium text-rose-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Carbon Copy (Cc) --}}
                    <div>
                        <label for="cc" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            CC (Separated by Semicolon ;)
                        </label>
                        <textarea id="cc" name="cc" rows="4" required
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-4 text-sm font-medium text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 outline-none transition @error('cc') border-rose-300 ring-rose-500/10 @enderror"
                            placeholder="user1@example.com; user2@example.com"></textarea>
                        @error('cc')
                            <p class="text-xs font-medium text-rose-600 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-md shadow-blue-200 hover:bg-blue-700 active:scale-95 transition-all">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('extraJs')
    <script>
        function updateEmailSettings(selectedFeature) {
            fetch(`/get-email-settings/${selectedFeature}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('to').value = data.to || '';
                    document.getElementById('cc').value = Array.isArray(data.cc) ? data.cc.join(';') : (data.cc || '');
                })
                .catch(error => console.error('Error fetching email settings:', error));
        }

        document.getElementById('feature').addEventListener('change', function() {
            updateEmailSettings(this.value);
        });

        const defaultFeature = document.getElementById('feature').value;
        if (defaultFeature) {
            updateEmailSettings(defaultFeature);
        }
    </script>
@endpush
