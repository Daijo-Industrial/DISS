{{-- ========================================================================= --}}
{{-- TAB 2: LEGALITAS & DOKUMEN                                                --}}
{{-- ========================================================================= --}}
<div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs space-y-4 min-w-0">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3 min-w-0">
        <div>
            <h2 class="text-sm font-bold text-slate-900">{{ __('fleet.show.documents_title') }}</h2>
            <p class="text-xs text-slate-400">{{ __('fleet.show.documents_desc') }}</p>
        </div>

        @if ($canManage)
            <button type="button" wire:click="openDocModal"
                class="rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-bold text-white shadow-2xs hover:bg-slate-800 transition cursor-pointer self-start sm:self-auto">
                <i class="bi bi-plus-lg mr-1"></i> {{ __('fleet.show.add_other_document') }}
            </button>
        @endif
    </div>

    @php
        $kirDoc = $documents->where('document_type', 'kir')->sortByDesc('expired_date')->first();
        $stnk1 = $documents->where('document_type', 'stnk_annual')->sortByDesc('expired_date')->first();
        $stnk5 = $documents->where('document_type', 'stnk_five_year')->sortByDesc('expired_date')->first();
        $otherDocs = $documents->whereNotIn('document_type', ['kir', 'stnk_annual', 'stnk_five_year']);

        $stnkNumber = $stnk1?->document_number ?: $stnk5?->document_number;
        $stnkAttachmentDoc = ($stnk1 && $stnk1->attachment_path) ? $stnk1 : (($stnk5 && $stnk5->attachment_path) ? $stnk5 : null);

        // Calculate combined STNK status
        $stnkWorstStatus = 'valid';
        if (($stnk1 && $stnk1->status === 'expired') || ($stnk5 && $stnk5->status === 'expired')) {
            $stnkWorstStatus = 'expired';
        } elseif (($stnk1 && $stnk1->status === 'critical') || ($stnk5 && $stnk5->status === 'critical')) {
            $stnkWorstStatus = 'critical';
        } elseif (($stnk1 && $stnk1->status === 'warning') || ($stnk5 && $stnk5->status === 'warning')) {
            $stnkWorstStatus = 'warning';
        }
    @endphp

    {{-- 2 Unified Interactive Legal Cards: KIR & STNK --}}
    <div class="grid gap-4 sm:grid-cols-2 min-w-0">
        {{-- Card 1: KIR (Uji Berkala) --}}
        <div class="rounded-2xl border p-4 sm:p-5 transition flex flex-col justify-between min-w-0
            {{ $vehicle->requires_kir ? ($kirDoc ? ($kirDoc->status === 'expired' ? 'border-rose-300 bg-rose-50/30' : ($kirDoc->status === 'critical' ? 'border-orange-300 bg-orange-50/20' : ($kirDoc->status === 'warning' ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200 bg-white shadow-2xs'))) : 'border-amber-300 bg-amber-50/20') : 'border-slate-200 bg-slate-50/30 opacity-75' }}">
            <div>
                <div class="flex items-center justify-between gap-2 min-w-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 truncate">{{ __('fleet.show.kir_card_title') }}</span>
                    <span class="rounded-md {{ $vehicle->requires_kir ? 'bg-slate-100 text-slate-700' : 'bg-slate-100 text-slate-500' }} px-2 py-0.5 text-[10px] font-bold shrink-0">
                        {{ $vehicle->requires_kir ? __('fleet.show.mandatory_commercial') : __('fleet.show.optional_passenger') }}
                    </span>
                </div>

                <div class="mt-3">
                    @if ($kirDoc)
                        <div class="text-base font-black text-slate-900">{{ $kirDoc->expired_date->isoFormat('D MMMM YYYY') }}</div>
                        <div class="mt-1 flex items-center">
                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $kirDoc->status_badge_classes }}">
                                {{ $kirDoc->status_label }}
                            </span>
                        </div>
                        <div class="font-mono text-[11px] text-slate-500 mt-2 truncate">{{ __('fleet.show.doc_number_short', ['num' => $kirDoc->document_number ?: '—']) }}</div>
                    @else
                        <p class="text-xs text-slate-400 italic">{{ __('fleet.show.no_doc_kir') }}</p>
                        @if (!$vehicle->requires_kir)
                            <p class="text-[10px] text-slate-400 mt-0.5">{{ __('fleet.show.optional_passenger_desc') }}</p>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Actions: Preview & Renew & Delete --}}
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2 min-w-0">
                <div class="flex items-center gap-1.5 min-w-0 truncate">
                    @if ($kirDoc && $kirDoc->attachment_path)
                        @if ($kirDoc->is_image_attachment)
                            <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ $kirDoc->attachment_url }}', title: '{{ __('fleet.documents.attachment') }}: {{ $kirDoc->type_label }}', subtitle: '{{ $vehicle->plate_number }} • No: {{ $kirDoc->document_number ?: '-' }}' })"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 cursor-pointer">
                                <i class="bi bi-eye"></i> {{ __('fleet.show.preview_attachment') }}
                            </button>
                        @else
                            <a href="{{ $kirDoc->attachment_url }}" target="_blank"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900">
                                <i class="bi bi-file-earmark-arrow-down"></i> {{ __('fleet.show.download_attachment') }}
                            </a>
                        @endif
                    @else
                        <span class="text-[11px] text-slate-400">{{ __('fleet.show.no_attachment') }}</span>
                    @endif
                </div>

                <div class="flex items-center gap-1 shrink-0">
                    @if ($kirDoc && $canManage)
                        <button type="button" wire:click="deleteDocument({{ $kirDoc->id }})" wire:confirm="{{ __('fleet.documents.delete_confirm') }}"
                            title="{{ __('fleet.documents.delete_document') }}"
                            class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    @endif
                    @if ($canManage)
                        <button type="button" wire:click="openDocModal('kir')"
                            class="rounded-lg bg-slate-100 hover:bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 transition cursor-pointer">
                            {{ $kirDoc ? __('fleet.show.renew_btn') : '+ ' . __('fleet.show.input_btn') . ' KIR' }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card 2: STNK & Pajak Kendaraan (Unified 1-Year & 5-Year) --}}
        <div class="rounded-2xl border p-4 sm:p-5 transition flex flex-col justify-between min-w-0
            {{ ($stnk1 || $stnk5) ? ($stnkWorstStatus === 'expired' ? 'border-rose-300 bg-rose-50/30' : ($stnkWorstStatus === 'critical' ? 'border-orange-300 bg-orange-50/20' : ($stnkWorstStatus === 'warning' ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200 bg-white shadow-2xs'))) : 'border-amber-300 bg-amber-50/20' }}">
            <div>
                <div class="flex items-center justify-between gap-2 min-w-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 truncate">{{ __('fleet.show.stnk_card_title') }}</span>
                    <span class="rounded-md bg-slate-100 text-slate-700 px-2 py-0.5 text-[10px] font-bold shrink-0">
                        {{ __('fleet.show.stnk_core_badge') }}
                    </span>
                </div>

                <div class="mt-3">
                    @if ($stnk1 || $stnk5)
                        <div class="font-mono text-xs font-bold text-slate-900 mb-2 truncate">
                            {{ __('fleet.show.doc_number_short', ['num' => $stnkNumber ?: '—']) }}
                        </div>

                        {{-- Dual Status Rows: Pajak 1 Thn & Plat 5 Thn --}}
                        <div class="space-y-2 rounded-xl bg-slate-50/80 border border-slate-100 p-2.5 text-xs">
                            {{-- Row 1: Pajak Tahunan (1 Thn) --}}
                            <div class="flex flex-wrap items-center justify-between gap-1.5 min-w-0">
                                <span class="text-[11px] font-semibold text-slate-600">{{ __('fleet.show.stnk_annual_label') }}:</span>
                                @if ($stnk1)
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <span class="font-bold text-slate-900 text-xs">{{ $stnk1->expired_date->isoFormat('D MMM YYYY') }}</span>
                                        <span class="rounded-md px-1.5 py-0.2 text-[9px] font-bold {{ $stnk1->status_badge_classes }}">
                                            {{ $stnk1->status_label }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-[11px] text-slate-400 italic">{{ __('fleet.show.no_doc_stnk1') }}</span>
                                @endif
                            </div>

                            {{-- Row 2: Plat & STNK (5 Thn) --}}
                            <div class="flex flex-wrap items-center justify-between gap-1.5 min-w-0 pt-1.5 border-t border-slate-200/60">
                                <span class="text-[11px] font-semibold text-slate-600">{{ __('fleet.show.stnk_five_year_label') }}:</span>
                                @if ($stnk5)
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <span class="font-bold text-slate-900 text-xs">{{ $stnk5->expired_date->isoFormat('D MMM YYYY') }}</span>
                                        <span class="rounded-md px-1.5 py-0.2 text-[9px] font-bold {{ $stnk5->status_badge_classes }}">
                                            {{ $stnk5->status_label }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-[11px] text-slate-400 italic">{{ __('fleet.show.no_doc_stnk5') }}</span>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic">{{ __('fleet.show.no_doc_stnk') }}</p>
                    @endif
                </div>
            </div>

            {{-- Actions: Single Preview & Unified Renew / Delete --}}
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2 min-w-0">
                <div class="flex items-center gap-1.5 min-w-0 truncate">
                    @if ($stnkAttachmentDoc)
                        @if ($stnkAttachmentDoc->is_image_attachment)
                            <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ $stnkAttachmentDoc->attachment_url }}', title: '{{ __('fleet.documents.attachment') }}: {{ __('fleet.show.stnk_card_title') }}', subtitle: '{{ $vehicle->plate_number }} • No: {{ $stnkNumber ?: '-' }}' })"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 cursor-pointer">
                                <i class="bi bi-eye"></i> {{ __('fleet.show.preview_attachment') }}
                            </button>
                        @else
                            <a href="{{ $stnkAttachmentDoc->attachment_url }}" target="_blank"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-900">
                                <i class="bi bi-file-earmark-arrow-down"></i> {{ __('fleet.show.download_attachment') }}
                            </a>
                        @endif
                    @else
                        <span class="text-[11px] text-slate-400">{{ __('fleet.show.no_attachment') }}</span>
                    @endif
                </div>

                <div class="flex items-center gap-1 shrink-0">
                    @if (($stnk1 || $stnk5) && $canManage)
                        <button type="button" wire:click="deleteStnk" wire:confirm="{{ __('fleet.show.delete_stnk_confirm') }}"
                            title="{{ __('fleet.documents.delete_document') }}"
                            class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    @endif
                    @if ($canManage)
                        <button type="button" wire:click="openDocModal('stnk')"
                            class="rounded-lg bg-slate-100 hover:bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 transition cursor-pointer">
                            {{ ($stnk1 || $stnk5) ? __('fleet.show.renew_stnk_btn') : __('fleet.show.input_stnk_btn') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Optional Secondary Section: Other Documents (Asuransi dll) --}}
    @if ($otherDocs->isNotEmpty())
        <div class="space-y-2 pt-2 border-t border-slate-100 min-w-0">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">{{ __('fleet.show.other_documents_title') }}</span>
            <div class="divide-y divide-slate-100 rounded-2xl border border-slate-200/80 bg-white overflow-hidden min-w-0">
                @foreach ($otherDocs as $oDoc)
                    <div class="p-3 sm:px-4 flex items-center justify-between gap-3 text-xs min-w-0">
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-slate-900 truncate">{{ $oDoc->type_label }}</div>
                            <div class="text-[11px] text-slate-400 truncate flex flex-wrap items-center gap-1.5 mt-0.5">
                                <span>{{ __('fleet.show.doc_number_short', ['num' => $oDoc->document_number ?: '—']) }}</span>
                                <span>•</span>
                                <span>Exp: {{ $oDoc->expired_date->isoFormat('D MMM YYYY') }}</span>
                                <span class="rounded-md px-1.5 py-0.2 text-[9px] font-bold {{ $oDoc->status_badge_classes }}">
                                    {{ $oDoc->status_label }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if ($oDoc->attachment_path)
                                @if ($oDoc->is_image_attachment)
                                    <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ $oDoc->attachment_url }}', title: '{{ __('fleet.documents.attachment') }}: {{ $oDoc->type_label }}', subtitle: '{{ $vehicle->plate_number }} • {{ $oDoc->document_number ?: '-' }}' })"
                                        class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold underline cursor-pointer flex items-center gap-1">
                                        <i class="bi bi-eye"></i> {{ __('fleet.show.preview_attachment') }}
                                    </button>
                                @else
                                    <a href="{{ $oDoc->attachment_url }}" target="_blank"
                                        class="text-xs text-slate-600 hover:text-slate-900 font-medium underline flex items-center gap-1">
                                        <i class="bi bi-file-earmark-arrow-down"></i> {{ __('fleet.show.download_attachment') }}
                                    </a>
                                @endif
                            @endif
                            @if ($canManage)
                                <button type="button" wire:click="deleteDocument({{ $oDoc->id }})" wire:confirm="{{ __('fleet.documents.delete_confirm') }}"
                                    title="{{ __('fleet.documents.delete_document') }}"
                                    class="text-slate-400 hover:text-rose-600 text-xs cursor-pointer p-1 rounded-lg hover:bg-rose-50 transition">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Section 3: Document Archive & Renewal History --}}
    @if ($documents->count() > 1)
        <div class="space-y-2 pt-2 border-t border-slate-100 min-w-0" x-data="{ showArchive: false }">
            <div class="flex items-center justify-between cursor-pointer py-1" @click="showArchive = !showArchive">
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="bi bi-archive text-slate-500"></i>
                        <span>{{ __('fleet.documents.archive_title') }}</span>
                        <span class="rounded-full bg-slate-100 px-1.5 py-0.2 text-[10px] text-slate-600 font-semibold">{{ $documents->count() }}</span>
                    </h3>
                    <p class="text-[11px] text-slate-400">{{ __('fleet.documents.archive_desc') }}</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                    <i class="bi text-xs" :class="showArchive ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </button>
            </div>

            <div x-show="showArchive" x-transition.duration.200ms x-cloak class="pt-1">
                <div class="overflow-x-auto rounded-2xl border border-slate-200/80 bg-white">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50/70 border-b border-slate-100 text-[10px] uppercase font-bold text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-3.5 py-2.5">{{ __('fleet.documents.type') }}</th>
                                <th class="px-3.5 py-2.5">{{ __('fleet.documents.doc_number') }}</th>
                                <th class="px-3.5 py-2.5">{{ __('fleet.documents.expired_date') }}</th>
                                <th class="px-3.5 py-2.5">{{ __('fleet.documents.attachment') }}</th>
                                <th class="px-3.5 py-2.5">{{ __('fleet.common.notes') }}</th>
                                @if ($canManage)
                                    <th class="px-3.5 py-2.5 text-right">{{ __('fleet.common.action') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($documents->sortByDesc('expired_date') as $doc)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-3.5 py-2.5 font-semibold text-slate-900 whitespace-nowrap">
                                        {{ $doc->type_label }}
                                    </td>
                                    <td class="px-3.5 py-2.5 font-mono text-[11px] text-slate-700 whitespace-nowrap">
                                        {{ $doc->document_number ?: '—' }}
                                    </td>
                                    <td class="px-3.5 py-2.5 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ $doc->expired_date->isoFormat('D MMM YYYY') }}</span>
                                            <span class="rounded-md px-1.5 py-0.2 text-[9px] font-bold {{ $doc->status_badge_classes }}">
                                                {{ $doc->status_label }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3.5 py-2.5 whitespace-nowrap">
                                        @if ($doc->attachment_path)
                                            @if ($doc->is_image_attachment)
                                                <button type="button" @click.prevent="$dispatch('open-lightbox', { src: '{{ $doc->attachment_url }}', title: '{{ __('fleet.documents.attachment') }}: {{ $doc->type_label }}', subtitle: '{{ $vehicle->plate_number }} • No: {{ $doc->document_number ?: '-' }}' })"
                                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 cursor-pointer">
                                                    <i class="bi bi-eye"></i> {{ __('fleet.show.preview_attachment') }}
                                                </button>
                                            @else
                                                <a href="{{ $doc->attachment_url }}" target="_blank"
                                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-700 hover:text-slate-900">
                                                    <i class="bi bi-file-earmark-arrow-down"></i> {{ __('fleet.show.download_attachment') }}
                                                </a>
                                            @endif
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3.5 py-2.5 text-slate-500 max-w-xs truncate">
                                        {{ $doc->notes ?: '—' }}
                                    </td>
                                    @if ($canManage)
                                        <td class="px-3.5 py-2.5 text-right whitespace-nowrap">
                                            <button type="button" wire:click="deleteDocument({{ $doc->id }})" wire:confirm="{{ __('fleet.documents.delete_confirm') }}"
                                                title="{{ __('fleet.documents.delete_document') }}"
                                                class="text-slate-400 hover:text-rose-600 transition cursor-pointer p-1">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
