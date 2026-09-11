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
            <button type="button" @click="showDeleteModal = true" wire:click="openDeleteModal"
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

    {{-- Delete Confirmation Modal --}}
    @if ($requirement?->exists)
        <template x-teleport="body">
            <div x-show="showDeleteModal" class="relative z-[100]" x-cloak>
                {{-- Backdrop --}}
                <div x-show="showDeleteModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                    @click="showDeleteModal = false"
                    @keydown.escape.window="showDeleteModal = false"></div>

                {{-- Modal Dialog --}}
                <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <div x-show="showDeleteModal"
                            x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave="ease-in duration-200"
                            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">

                            {{-- Header & Content --}}
                            <div class="bg-white p-6">
                                <div class="flex items-start gap-4">
                                    <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 sm:mx-0">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-base font-extrabold text-slate-900">Delete Requirement</h3>
                                            <button type="button" @click="showDeleteModal = false" class="rounded-lg p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1">
                                            Are you sure you want to permanently delete
                                            <strong class="text-slate-900">{{ $requirement->name }}</strong>?
                                        </p>
                                    </div>
                                </div>

                                {{-- Usage Statistics Card --}}
                                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-3">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-bold text-slate-600">Assigned Departments</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg font-mono font-bold text-xs {{ $usage['assignments'] > 0 ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $usage['assignments'] }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-bold text-slate-600">Uploaded Files</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg font-mono font-bold text-xs {{ $usage['uploads'] > 0 ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                                            {{ $usage['uploads'] }}
                                        </span>
                                    </div>

                                    @if ($usage['uploads'] > 0)
                                        <div class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-[11px] text-rose-800 font-medium leading-relaxed">
                                            <strong class="font-bold block text-rose-900 mb-0.5">Cannot delete with active uploads</strong>
                                            This requirement currently contains {{ $usage['uploads'] }} uploaded file(s). You must review and delete the uploaded files before this requirement can be deleted.
                                        </div>
                                    @elseif ($usage['assignments'] > 0)
                                        <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-[11px] text-amber-800 font-medium leading-relaxed">
                                            <strong class="font-bold block text-amber-900 mb-0.5">Notice</strong>
                                            Deleting this requirement will automatically detach it from {{ $usage['assignments'] }} assigned department(s).
                                        </div>
                                    @else
                                        <div class="p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 text-[11px] text-emerald-800 font-medium flex items-center gap-1.5">
                                            <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            No department assignments or uploads detected. Safe to delete.
                                        </div>
                                    @endif
                                </div>

                                {{-- Confirmation Input --}}
                                <div class="mt-5">
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Type <code class="font-mono text-rose-600 font-extrabold bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">{{ $requirement->code }}</code> to confirm:
                                    </label>
                                    <input type="text"
                                        wire:model="delete_confirm_input"
                                        placeholder="{{ $requirement->code }}"
                                        class="w-full rounded-xl border {{ $errors->has('delete_confirm_input') ? 'border-rose-400 focus:ring-rose-500/20' : 'border-slate-200 focus:border-rose-500 focus:ring-rose-500/20' }} text-xs font-mono font-bold text-slate-900 py-2.5 px-3 outline-none transition-all">
                                    @error('delete_confirm_input')
                                        <p class="text-rose-600 text-xs mt-1.5 font-bold">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Footer actions --}}
                            <div class="bg-slate-50 border-t border-slate-100 px-6 py-3.5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
                                <button type="button" @click="showDeleteModal = false"
                                    class="w-full sm:w-auto px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-all">
                                    Cancel
                                </button>
                                <button type="button"
                                    wire:click="deleteRequirement"
                                    wire:loading.attr="disabled"
                                    @disabled($usage['uploads'] > 0 || $delete_in_progress)
                                    class="w-full sm:w-auto px-5 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm shadow-rose-200 transition-all flex items-center justify-center gap-1.5">
                                    <svg wire:loading wire:target="deleteRequirement" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="deleteRequirement">
                                        @if ($usage['uploads'] > 0)
                                            Resolve Uploads First
                                        @else
                                            Delete Permanently
                                        @endif
                                    </span>
                                    <span wire:loading wire:target="deleteRequirement">Deleting...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>
