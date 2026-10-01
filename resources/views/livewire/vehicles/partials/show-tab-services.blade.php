{{-- ========================================================================= --}}
{{-- TAB 3: RIWAYAT SERVIS BENGKEL & BIAYA                                     --}}
{{-- ========================================================================= --}}
<div class="rounded-3xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs space-y-4 min-w-0">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3 min-w-0">
        <div>
            <h2 class="text-sm font-bold text-slate-900">{{ __('fleet.show.services_title') }}</h2>
            <p class="text-xs text-slate-400">{{ __('fleet.show.services_desc') }}</p>
        </div>

        @if (!$vehicle->is_sold && $canManage)
            <a href="{{ route('services.create', $vehicle) }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-bold text-white shadow-2xs hover:bg-slate-800 transition self-start sm:self-auto">
                <i class="bi bi-plus-lg"></i>
                <span>{{ __('fleet.show.add_service_btn') }}</span>
            </a>
        @endif
    </div>

    @if ($canViewCosts)
        {{-- iOS Wallet-Style Compact Cost Summary Bar --}}
        <div class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-3 sm:p-4 grid grid-cols-2 divide-x divide-slate-200/60 min-w-0">
            <div class="pr-3 sm:pr-4 min-w-0">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block truncate">{{ __('fleet.show.ytd_cost_label', ['year' => now()->year]) }}</span>
                <div class="text-sm sm:text-xl font-black text-slate-900 mt-1 font-mono truncate" title="Rp {{ number_format($ytdCost, 0, ',', '.') }}">
                    Rp {{ number_format($ytdCost, 0, ',', '.') }}
                </div>
            </div>
            <div class="pl-3 sm:pl-4 min-w-0">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block truncate">{{ __('fleet.show.lifetime_cost_label') }}</span>
                <div class="text-sm sm:text-xl font-black text-slate-900 mt-1 font-mono truncate" title="Rp {{ number_format($lifetimeCost, 0, ',', '.') }}">
                    Rp {{ number_format($lifetimeCost, 0, ',', '.') }}
                </div>
            </div>
        </div>
    @endif

    {{-- Filter Controls: Year & Workshop Search --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 min-w-0">
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <select wire:model.live="year" class="rounded-xl border border-slate-200 bg-slate-50/70 py-1.5 px-3 text-xs text-slate-700 focus:outline-none cursor-pointer w-full sm:w-auto">
                <option value="all">{{ __('fleet.show.filter_year_all') }}</option>
                @foreach ($availableYears as $yr)
                    <option value="{{ $yr }}">{{ $yr }}</option>
                @endforeach
            </select>
        </div>

        <div class="relative w-full sm:w-64">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" wire:model.live.debounce.300ms="workshop"
                placeholder="{{ __('fleet.show.search_workshop_placeholder') }}"
                class="w-full rounded-xl border border-slate-200 bg-slate-50/70 py-1.5 pl-8 pr-3 text-xs text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-400">
        </div>
    </div>

    {{-- Records Table --}}
    @if ($records->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-xs text-slate-400">
            {{ __('fleet.show.empty_services') }}
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200/80 min-w-0">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 font-semibold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">{{ __('fleet.show.col_service_date') }}</th>
                        <th class="px-4 py-3">{{ __('fleet.show.col_workshop') }}</th>
                        <th class="px-4 py-3">{{ __('fleet.show.col_odometer') }}</th>
                        <th class="px-4 py-3">{{ __('fleet.show.col_parts_action') }}</th>
                        @if ($canViewCosts)
                            <th class="px-4 py-3 text-right">{{ __('fleet.show.col_cost') }}</th>
                        @endif
                        @if ($canManage)
                            <th class="px-4 py-3 text-right">{{ __('fleet.show.col_action') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @foreach ($records as $rec)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-4 py-3 font-bold text-slate-900 whitespace-nowrap">{{ $rec->service_date->isoFormat('DD MMM YYYY') }}</td>
                            <td class="px-4 py-3">{{ $rec->workshop ?: __('fleet.show.internal_workshop') }}</td>
                            <td class="px-4 py-3 font-semibold whitespace-nowrap">{{ number_format($rec->odometer) }} km</td>
                            <td class="px-4 py-3">
                                @if ($rec->items->isNotEmpty())
                                    <ul class="list-disc list-inside space-y-0.5 text-slate-600">
                                        @foreach ($rec->items->take(2) as $it)
                                            <li>{{ $it->part_name }} ({{ $it->action }})</li>
                                        @endforeach
                                        @if ($rec->items->count() > 2)
                                            <li class="text-slate-400">{{ __('fleet.show.more_items', ['count' => $rec->items->count() - 2]) }}</li>
                                        @endif
                                    </ul>
                                @else
                                    <span class="text-slate-400 italic">{{ $rec->notes ?: '—' }}</span>
                                @endif
                            </td>
                            @if ($canViewCosts)
                                <td class="px-4 py-3 text-right font-bold text-slate-900 whitespace-nowrap font-mono">
                                    Rp {{ number_format($rec->total_cost, 0, ',', '.') }}
                                </td>
                            @endif
                            @if ($canManage)
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('services.edit', $rec) }}" class="text-slate-600 hover:text-slate-900 mr-2" title="{{ __('fleet.common.edit') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" wire:click="deleteService({{ $rec->id }})" wire:confirm="{{ __('fleet.show.delete_service_confirm') }}" class="text-rose-600 hover:text-rose-800 cursor-pointer" title="{{ __('fleet.common.delete') }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>{{ $records->links() }}</div>
    @endif
</div>
