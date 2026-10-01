{{-- ========================================================================= --}}
{{-- TAB 2: LEGALITAS & DOKUMEN                                                --}}
{{-- ========================================================================= --}}
<div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
        <div>
            <h2 class="text-sm font-bold text-slate-900">{{ __('fleet.show.documents_title') }}</h2>
            <p class="text-xs text-slate-400">{{ __('fleet.show.documents_desc') }}</p>
        </div>

        @if ($canManage)
            <button type="button" wire:click="openDocModal"
                class="rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-bold text-white shadow-2xs hover:bg-slate-800 transition cursor-pointer">
                <i class="bi bi-plus-lg mr-1"></i> {{ __('fleet.show.add_other_document') }}
            </button>
        @endif
    </div>

    @php
        $kirDoc = $documents->where('document_type', 'kir')->sortByDesc('expired_date')->first();
        $stnk1 = $documents->where('document_type', 'stnk_annual')->sortByDesc('expired_date')->first();
        $stnk5 = $documents->where('document_type', 'stnk_five_year')->sortByDesc('expired_date')->first();
        $otherDocs = $documents->whereNotIn('document_type', ['kir', 'stnk_annual', 'stnk_five_year']);

        $kirDays = $kirDoc ? (int) now()->startOfDay()->diffInDays($kirDoc->expired_date->startOfDay(), false) : null;
        $stnk1Days = $stnk1 ? (int) now()->startOfDay()->diffInDays($stnk1->expired_date->startOfDay(), false) : null;
        $stnk5Days = $stnk5 ? (int) now()->startOfDay()->diffInDays($stnk5->expired_date->startOfDay(), false) : null;
    @endphp

    {{-- 3 Unified Interactive Legal Cards --}}
    <div class="grid gap-3 sm:grid-cols-3">
        {{-- Card 1: KIR --}}
        <div class="rounded-2xl border p-4 transition flex flex-col justify-between
            {{ $vehicle->requires_kir ? ($kirDoc ? ($kirDoc->status === 'expired' ? 'border-rose-300 bg-rose-50/30' : 'border-slate-200 bg-white shadow-2xs') : 'border-amber-300 bg-amber-50/20') : 'border-slate-200 bg-slate-50/30 opacity-70' }}">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700">{{ __('fleet.show.kir_card_title') }}</span>
                    <span class="rounded-md {{ $vehicle->requires_kir ? 'bg-slate-100 text-slate-700' : 'bg-slate-100 text-slate-500' }} px-2 py-0.5 text-[10px] font-bold">
                        {{ $vehicle->requires_kir ? __('fleet.show.mandatory_commercial') : __('fleet.show.optional_passenger') }}
                    </span>
                </div>

                <div class="mt-3">
                    @if ($kirDoc)
                        <div class="text-base font-black text-slate-900">{{ $kirDoc->expired_date->isoFormat('DD MMMM YYYY') }}</div>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $kirDoc->status_badge_classes }}">
                                {{ $kirDoc->status_label }}
                            </span>
                            @if ($kirDays !== null)
                                <span class="rounded-md px-1.5 py-0.5 text-[10px] font-bold {{ $kirDays < 0 ? 'bg-rose-100 text-rose-800' : ($kirDays <= 30 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">
                                    {{ $kirDays < 0 ? __('fleet.show.expired_days_ago', ['days' => abs($kirDays)]) : ($kirDays === 0 ? __('fleet.show.expires_today') : __('fleet.show.days_remaining', ['days' => $kirDays])) }}
                                </span>
                            @endif
                        </div>
                        <div class="font-mono text-[11px] text-slate-500 mt-2">{{ __('fleet.show.doc_number_short', ['num' => $kirDoc->document_number ?: '—']) }}</div>
                    @else
                        <p class="text-xs text-slate-400 italic">{{ __('fleet.show.no_doc_kir') }}</p>
                    @endif
                </div>
            </div>

            {{-- Actions: Preview & Renew --}}
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                @if ($kirDoc && $kirDoc->attachment_path)
                    @php $isImageDoc = (bool) preg_match('/\.(jpg|jpeg|png|webp)$/i', $kirDoc->attachment_path); @endphp
                    @if ($isImageDoc)
                        <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $kirDoc->attachment_path) }}', title: 'Lampiran Dokumen: {{ $kirDoc->document_type_label }}', subtitle: '{{ $vehicle->plate_number }} • No: {{ $kirDoc->document_number ?: '-' }}' })"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900 cursor-pointer">
                            <i class="bi bi-eye"></i> {{ __('fleet.show.preview_attachment') }}
                        </button>
                    @else
                        <a href="{{ asset('storage/' . $kirDoc->attachment_path) }}" target="_blank"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900">
                            <i class="bi bi-file-earmark-arrow-down"></i> {{ __('fleet.show.download_attachment') }}
                        </a>
                    @endif
                @else
                    <span class="text-[11px] text-slate-400">{{ __('fleet.show.no_attachment') }}</span>
                @endif

                @if ($canManage)
                    <button type="button" wire:click="openDocModal('kir')"
                        class="rounded-lg bg-slate-100 hover:bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 transition cursor-pointer">
                        {{ $kirDoc ? __('fleet.show.renew_btn') : '+ ' . __('fleet.show.input_btn') . ' KIR' }}
                    </button>
                @endif
            </div>
        </div>

        {{-- Card 2: STNK 1 Tahun --}}
        <div class="rounded-2xl border p-4 transition flex flex-col justify-between
            {{ $stnk1 ? ($stnk1->status === 'expired' ? 'border-rose-300 bg-rose-50/30' : 'border-slate-200 bg-white shadow-2xs') : 'border-amber-300 bg-amber-50/20' }}">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700">{{ __('fleet.show.stnk_annual_card_title') }}</span>
                    <span class="rounded-md bg-emerald-50 text-emerald-700 px-2 py-0.5 text-[10px] font-bold border border-emerald-200/60">
                        {{ __('fleet.show.annual_badge') }}
                    </span>
                </div>

                <div class="mt-3">
                    @if ($stnk1)
                        <div class="text-base font-black text-slate-900">{{ $stnk1->expired_date->isoFormat('DD MMMM YYYY') }}</div>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $stnk1->status_badge_classes }}">
                                {{ $stnk1->status_label }}
                            </span>
                            @if ($stnk1Days !== null)
                                <span class="rounded-md px-1.5 py-0.5 text-[10px] font-bold {{ $stnk1Days < 0 ? 'bg-rose-100 text-rose-800' : ($stnk1Days <= 30 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">
                                    {{ $stnk1Days < 0 ? __('fleet.show.expired_days_ago', ['days' => abs($stnk1Days)]) : ($stnk1Days === 0 ? __('fleet.show.expires_today') : __('fleet.show.days_remaining', ['days' => $stnk1Days])) }}
                                </span>
                            @endif
                        </div>
                        <div class="font-mono text-[11px] text-slate-500 mt-2">{{ __('fleet.show.doc_number_short', ['num' => $stnk1->document_number ?: '—']) }}</div>
                    @else
                        <p class="text-xs text-slate-400 italic">{{ __('fleet.show.no_doc_stnk1') }}</p>
                    @endif
                </div>
            </div>

            {{-- Actions: Preview & Renew --}}
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                @if ($stnk1 && $stnk1->attachment_path)
                    @php $isImageDoc = (bool) preg_match('/\.(jpg|jpeg|png|webp)$/i', $stnk1->attachment_path); @endphp
                    @if ($isImageDoc)
                        <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $stnk1->attachment_path) }}', title: 'Lampiran Dokumen: {{ $stnk1->document_type_label }}', subtitle: '{{ $vehicle->plate_number }} • No: {{ $stnk1->document_number ?: '-' }}' })"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900 cursor-pointer">
                            <i class="bi bi-eye"></i> {{ __('fleet.show.preview_attachment') }}
                        </button>
                    @else
                        <a href="{{ asset('storage/' . $stnk1->attachment_path) }}" target="_blank"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900">
                            <i class="bi bi-file-earmark-arrow-down"></i> {{ __('fleet.show.download_attachment') }}
                        </a>
                    @endif
                @else
                    <span class="text-[11px] text-slate-400">{{ __('fleet.show.no_attachment') }}</span>
                @endif

                @if ($canManage)
                    <button type="button" wire:click="openDocModal('stnk_annual')"
                        class="rounded-lg bg-slate-100 hover:bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 transition cursor-pointer">
                        {{ $stnk1 ? __('fleet.show.renew_btn') : '+ ' . __('fleet.show.input_btn') . ' STNK' }}
                    </button>
                @endif
            </div>
        </div>

        {{-- Card 3: STNK 5 Tahun --}}
        <div class="rounded-2xl border p-4 transition flex flex-col justify-between
            {{ $stnk5 ? ($stnk5->status === 'expired' ? 'border-rose-300 bg-rose-50/30' : 'border-slate-200 bg-white shadow-2xs') : 'border-slate-200 bg-white shadow-2xs' }}">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700">{{ __('fleet.show.stnk_five_year_card_title') }}</span>
                    <span class="rounded-md bg-blue-50 text-blue-700 px-2 py-0.5 text-[10px] font-bold border border-blue-200/60">
                        {{ __('fleet.show.five_year_badge') }}
                    </span>
                </div>

                <div class="mt-3">
                    @if ($stnk5)
                        <div class="text-base font-black text-slate-900">{{ $stnk5->expired_date->isoFormat('DD MMMM YYYY') }}</div>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $stnk5->status_badge_classes }}">
                                {{ $stnk5->status_label }}
                            </span>
                            @if ($stnk5Days !== null)
                                <span class="rounded-md px-1.5 py-0.5 text-[10px] font-bold {{ $stnk5Days < 0 ? 'bg-rose-100 text-rose-800' : ($stnk5Days <= 30 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">
                                    {{ $stnk5Days < 0 ? __('fleet.show.expired_days_ago', ['days' => abs($stnk5Days)]) : ($stnk5Days === 0 ? __('fleet.show.expires_today') : __('fleet.show.days_remaining', ['days' => $stnk5Days])) }}
                                </span>
                            @endif
                        </div>
                        <div class="font-mono text-[11px] text-slate-500 mt-2">{{ __('fleet.show.doc_number_short', ['num' => $stnk5->document_number ?: '—']) }}</div>
                    @else
                        <p class="text-xs text-slate-400 italic">{{ __('fleet.show.no_doc_stnk5') }}</p>
                    @endif
                </div>
            </div>

            {{-- Actions: Preview & Renew --}}
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                @if ($stnk5 && $stnk5->attachment_path)
                    @php $isImageDoc = (bool) preg_match('/\.(jpg|jpeg|png|webp)$/i', $stnk5->attachment_path); @endphp
                    @if ($isImageDoc)
                        <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ asset('storage/' . $stnk5->attachment_path) }}', title: 'Lampiran Dokumen: {{ $stnk5->document_type_label }}', subtitle: '{{ $vehicle->plate_number }} • No: {{ $stnk5->document_number ?: '-' }}' })"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900 cursor-pointer">
                            <i class="bi bi-eye"></i> {{ __('fleet.show.preview_attachment') }}
                        </button>
                    @else
                        <a href="{{ asset('storage/' . $stnk5->attachment_path) }}" target="_blank"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900">
                            <i class="bi bi-file-earmark-arrow-down"></i> {{ __('fleet.show.download_attachment') }}
                        </a>
                    @endif
                @else
                    <span class="text-[11px] text-slate-400">{{ __('fleet.show.no_attachment') }}</span>
                @endif

                @if ($canManage)
                    <button type="button" wire:click="openDocModal('stnk_five_year')"
                        class="rounded-lg bg-slate-100 hover:bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 transition cursor-pointer">
                        {{ $stnk5 ? __('fleet.show.renew_btn') : '+ ' . __('fleet.show.input_btn') . ' STNK 5th' }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Optional Secondary Section: Other Documents (Asuransi dll) --}}
    @if ($otherDocs->isNotEmpty())
        <div class="space-y-2 pt-2 border-t border-slate-100">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">{{ __('fleet.show.other_documents_title') }}</span>
            <div class="divide-y divide-slate-100 rounded-2xl border border-slate-200/80 bg-white overflow-hidden">
                @foreach ($otherDocs as $oDoc)
                    <div class="p-3 sm:px-4 flex items-center justify-between gap-3 text-xs">
                        <div>
                            <div class="font-bold text-slate-900">{{ $oDoc->type_label }}</div>
                            <div class="text-[11px] text-slate-400">
                                {{ __('fleet.show.doc_number_short', ['num' => $oDoc->document_number ?: '—']) }} • Exp: {{ $oDoc->expired_date->isoFormat('DD MMM YYYY') }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($oDoc->attachment_path)
                                <a href="{{ asset('storage/' . $oDoc->attachment_path) }}" target="_blank"
                                    class="text-xs text-slate-600 hover:text-slate-900 font-medium underline">
                                    {{ __('fleet.show.download_attachment') }}
                                </a>
                            @endif
                            @if ($canManage)
                                <button type="button" wire:click="deleteDocument({{ $oDoc->id }})" wire:confirm="{{ __('fleet.documents.delete_confirm') }}"
                                    class="text-rose-600 hover:text-rose-800 text-xs cursor-pointer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
