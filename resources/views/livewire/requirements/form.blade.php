{{-- Requirements Form (Create/Edit) — Livewire component view --}}
@section('title', $requirement?->exists ? 'Edit Requirement' : 'New Requirement')
@section('page-title', $requirement?->exists ? 'Edit Requirement' : 'New Requirement')
@section('page-subtitle', 'Configure compliance definitions, cadences, and allowed file types.')

<div x-data="{ showDeleteModal: false, showCustomMimes: false, showMimePeek: false }" @hide-delete-modal.window="showDeleteModal = false" class="space-y-6">

    {{-- Header / Breadcrumbs --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-2 text-xs font-bold">
                <li>
                    <a href="{{ route('requirements.index') }}" class="text-slate-500 hover:text-indigo-600 transition-colors">
                        Requirements
                    </a>
                </li>
                <li>
                    <div class="flex items-center text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        <span class="ml-1 text-slate-900">{{ $requirement?->exists ? 'Edit' : 'Create' }}</span>
                    </div>
                </li>
            </ol>
        </nav>

        @if ($requirement?->exists)
            <button type="button" @click="showDeleteModal = true"
                class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 px-4 py-2 text-xs font-bold transition-all active:scale-95">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                Delete Requirement
            </button>
        @endif
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center gap-3">
            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-xs font-bold text-emerald-900">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Main 2-column layout --}}
    <div class="flex flex-col lg:flex-row gap-6">

        {{-- Left: Form --}}
        <div class="flex-1 w-full lg:w-2/3 space-y-6">
            <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 p-6 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                    <h2 class="text-base font-extrabold text-slate-900">
                        Requirement {{ $requirement?->exists ? 'Editor' : 'Definition' }}
                    </h2>
                    @if ($requirement?->exists)
                        <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-mono font-bold text-slate-600">
                            #{{ $requirement->id }}
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Code --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Requirement Code <span class="text-rose-500">*</span></label>
                        <div class="flex rounded-xl overflow-hidden border {{ $errors->has('code') ? 'border-rose-300' : 'border-slate-200' }} focus-within:ring-2 focus-within:ring-indigo-500/20 focus-within:border-indigo-500 transition-all">
                            <span class="flex items-center px-3 bg-slate-50 border-r border-slate-200 text-slate-400 font-mono text-xs font-bold">
                                CODE
                            </span>
                            <input type="text" wire:model.live.debounce.400ms="code"
                                wire:keydown.debounce.400ms="checkCodeUnique" placeholder="ISO_9001_CERT"
                                class="w-full py-2.5 px-3 text-xs font-bold font-mono text-slate-900 bg-white outline-none">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Uppercase letters, digits, and underscores.</p>

                        @if (!is_null($code_is_unique))
                            <p class="text-xs font-bold mt-1 flex items-center gap-1 {{ $code_is_unique ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $code_is_unique ? '✓ Code available' : '✗ Code already taken' }}
                            </p>
                        @endif
                        @error('code')
                            <p class="text-rose-600 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Name --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Requirement Name <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model.live.debounce.300ms="name" placeholder="ISO 9001 Audit Certificate"
                            class="w-full rounded-xl border {{ $errors->has('name') ? 'border-rose-300 focus:ring-rose-400' : 'border-slate-200 focus:ring-indigo-500/20 focus:border-indigo-500' }} text-xs font-bold text-slate-900 py-2.5 px-3 outline-none transition-all">
                        @error('name')
                            <p class="text-rose-600 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Description & Scope</label>
                        <textarea rows="3" wire:model.live.debounce.300ms="description"
                            placeholder="Detailed explanation of required files, compliance scope, and verification guidelines..."
                            class="w-full rounded-xl border {{ $errors->has('description') ? 'border-rose-300' : 'border-slate-200' }} text-xs font-medium text-slate-800 placeholder-slate-400 py-2.5 px-3 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all"></textarea>
                        @error('description')
                            <p class="text-rose-600 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Preset Mime Types --}}
                <div class="mt-8 border-t border-slate-100 pt-6">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Allowed File Format Presets</label>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="selectAllPresets" class="text-xs font-bold text-indigo-600 hover:underline">Select All</button>
                            <span class="text-slate-300">|</span>
                            <button type="button" wire:click="clearPresets" class="text-xs font-bold text-slate-400 hover:text-slate-700">Clear</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @php $presets = $this->mimePresets(); @endphp
                        @foreach ($presets as $key => $p)
                            @php $isActive = in_array($key, $selected_presets); @endphp
                            <label class="flex items-start gap-3 p-3 rounded-xl border {{ $isActive ? 'border-indigo-500 bg-indigo-50/40 shadow-2xs' : 'border-slate-200/90 bg-white hover:bg-slate-50' }} cursor-pointer transition-all">
                                <input type="checkbox" wire:model="selected_presets" wire:click.prevent="togglePreset('{{ $key }}')" value="{{ $key }}" class="sr-only">
                                <div class="w-4 h-4 rounded border mt-0.5 {{ $isActive ? 'bg-indigo-600 border-indigo-600' : 'bg-white border-slate-300' }} flex items-center justify-center shrink-0 transition-colors">
                                    @if ($isActive)
                                        <svg class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    @endif
                                </div>
                                <div>
                                    <span class="text-xs font-extrabold {{ $isActive ? 'text-indigo-900' : 'text-slate-800' }}">{{ $p['label'] }}</span>
                                    <p class="text-[10px] text-slate-400 leading-tight mt-0.5">{{ $p['apps'] }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Numbers & Cadence --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-8 border-t border-slate-100 pt-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Min Files Required <span class="text-rose-500">*</span></label>
                        <input type="number" min="1" max="20" wire:model.live="min_count"
                            class="w-full rounded-xl border border-slate-200 text-xs font-bold text-slate-900 py-2.5 px-3 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Validity Period (Days)</label>
                        <input type="number" min="1" max="3650" wire:model.live="validity_days" placeholder="365"
                            class="w-full rounded-xl border border-slate-200 text-xs font-bold text-slate-900 py-2.5 px-3 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Renewal Frequency</label>
                        <div class="flex bg-slate-100/90 p-1 rounded-xl border border-slate-200/70">
                            @foreach (['once' => 'One-time', 'yearly' => 'Yearly', 'quarterly' => 'Quarterly', 'monthly' => 'Monthly'] as $val => $label)
                                <button type="button" wire:click="$set('frequency', '{{ $val }}')"
                                    class="flex-1 rounded-lg py-1.5 text-xs font-bold transition-all {{ $frequency === $val ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-span-full">
                        <label class="flex items-start gap-3.5 p-4 rounded-2xl border border-amber-200 bg-amber-50/60 cursor-pointer">
                            <input type="checkbox" wire:model="requires_approval" class="rounded border-amber-300 text-amber-600 focus:ring-amber-500 w-4 h-4 mt-0.5">
                            <div>
                                <p class="text-xs font-extrabold text-amber-900">Requires Admin Approval</p>
                                <p class="text-[11px] text-amber-700 mt-0.5">Uploaded files must be manually verified by a compliance admin before counting towards department score.</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Save CTA Button --}}
                <div class="mt-8 flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                    <a href="{{ route('requirements.index') }}" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800">
                        Cancel
                    </a>
                    <button type="button" wire:click="save" wire:loading.attr="disabled"
                        class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-200 transition-all active:scale-95">
                        Save Requirement
                    </button>
                </div>
            </div>
        </div>

        {{-- Right: Live Summary Panel --}}
        <div class="w-full lg:w-1/3 space-y-5">
            <div class="rounded-2xl bg-white/90 backdrop-blur-xl border border-slate-200/80 p-5 shadow-sm sticky top-6 space-y-4">
                <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-3">Live Policy Summary</h3>

                <div>
                    <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider">Requirement</span>
                    <p class="text-sm font-extrabold text-slate-900 leading-tight">{{ $name ?: 'Requirement Name' }}</p>
                    <span class="font-mono text-xs font-bold text-slate-500">{{ $code ?: 'CODE' }}</span>
                </div>

                <div class="p-3 rounded-xl bg-indigo-50 border border-indigo-100">
                    <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider">Policy Statement</span>
                    <p class="text-xs font-bold text-indigo-900 mt-1 leading-snug">{{ $this->policy_line }}</p>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1.5 border-b border-slate-100">
                        <span class="text-slate-500 font-bold">Min Files</span>
                        <span class="font-extrabold text-slate-900">{{ $min_count }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-100">
                        <span class="text-slate-500 font-bold">Validity</span>
                        <span class="font-extrabold text-slate-900">{{ $validity_days ? $validity_days . ' days' : 'No expiry' }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-100">
                        <span class="text-slate-500 font-bold">Frequency</span>
                        <span class="font-extrabold text-slate-900">{{ $this->frequencyLabel() }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
