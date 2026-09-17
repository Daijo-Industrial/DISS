<div>
    <div class="px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto space-y-4">
        {{-- Header Section (Minimal) --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <i class="bi bi-receipt text-emerald-600"></i>
                        Invoices
                    </h1>
                    <select wire:model.live="yearFilter"
                            class="bg-white border border-slate-200 rounded-xl text-xs font-black uppercase tracking-wider text-slate-700 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 py-1.5 px-3 shadow-xs hover:border-slate-300 transition-all cursor-pointer">
                        @foreach($filterOptions['years'] as $val => $lbl)
                            <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Manage and track purchase order invoices, approval workflows, and payments.
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="{{ route('po.index') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3.5 py-1.5 text-xs font-bold text-slate-700 shadow-xs ring-1 ring-inset ring-slate-200 hover:bg-slate-50 transition-all hover:ring-slate-300">
                    <i class="bi bi-arrow-left"></i>
                    Back to POs
                </a>
            </div>
        </div>

        {{-- Interactive KPI Stat Cards (Reduced Padding & Sleek Dimensions) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            {{-- All Invoices --}}
            <div wire:click="filterByStat('all')"
                 class="cursor-pointer bg-white p-3.5 rounded-xl border transition-all duration-200 hover:shadow-sm relative overflow-hidden group {{ empty($poStatusFilter) && empty($paymentStatusFilter) ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/10' : 'border-slate-100 hover:border-slate-200' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">All Invoices</p>
                        <h3 class="text-xl font-black text-slate-900 tracking-tight mt-0.5">{{ number_format($stats['total_count']) }}</h3>
                        <div class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] font-mono font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md">
                            <span class="text-[10px] text-slate-400 font-black">TOTAL:</span> Rp {{ number_format($stats['total_amount_idr'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 group-hover:bg-slate-200 transition-colors text-base">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                </div>
            </div>

            {{-- PO Pending Approval --}}
            <div wire:click="filterByStat('pending_approval')"
                 class="cursor-pointer bg-white p-3.5 rounded-xl border transition-all duration-200 hover:shadow-sm relative overflow-hidden group {{ $poStatusFilter === 'IN_REVIEW' ? 'ring-2 ring-amber-500 border-amber-500 bg-amber-50/20' : 'border-slate-100 hover:border-amber-200' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <p class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">PO In Review</p>
                            @if($stats['pending_approval'] > 0)
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            @endif
                        </div>
                        <h3 class="text-xl font-black text-slate-900 tracking-tight mt-0.5">{{ number_format($stats['pending_approval']) }}</h3>
                        <div class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] font-mono font-bold text-amber-800 bg-amber-50 border border-amber-200/60 px-2 py-0.5 rounded-md">
                            <span class="text-[10px] text-amber-500 font-black">SUM:</span> Rp {{ number_format($stats['pending_approval_sum'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="h-9 w-9 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 group-hover:bg-amber-100 transition-colors text-base">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>

            {{-- PO Approved --}}
            <div wire:click="filterByStat('po_approved')"
                 class="cursor-pointer bg-white p-3.5 rounded-xl border transition-all duration-200 hover:shadow-sm relative overflow-hidden group {{ $poStatusFilter === 'APPROVED' ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/20' : 'border-slate-100 hover:border-emerald-200' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">PO Approved</p>
                        <h3 class="text-xl font-black text-slate-900 tracking-tight mt-0.5">{{ number_format($stats['po_approved']) }}</h3>
                        <div class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] font-mono font-bold text-emerald-800 bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 rounded-md">
                            <span class="text-[10px] text-emerald-500 font-black">SUM:</span> Rp {{ number_format($stats['po_approved_sum'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="h-9 w-9 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:bg-emerald-100 transition-colors text-base">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>

            {{-- Past Due (Unpaid) --}}
            <div wire:click="filterByStat('past_due')"
                 class="cursor-pointer bg-white p-3.5 rounded-xl border transition-all duration-200 hover:shadow-sm relative overflow-hidden group {{ $paymentStatusFilter === 'past_due' ? 'ring-2 ring-rose-500 border-rose-500 bg-rose-50/20' : 'border-slate-100 hover:border-rose-200' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <p class="text-[11px] font-bold text-rose-600 uppercase tracking-wider">Past Due</p>
                            @if($stats['past_due'] > 0)
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            @endif
                        </div>
                        <h3 class="text-xl font-black text-slate-900 tracking-tight mt-0.5">{{ number_format($stats['past_due']) }}</h3>
                        <div class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] font-mono font-bold text-rose-800 bg-rose-50 border border-rose-200/60 px-2 py-0.5 rounded-md">
                            <span class="text-[10px] text-rose-500 font-black">SUM:</span> Rp {{ number_format($stats['past_due_sum'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="h-9 w-9 rounded-lg bg-rose-50 flex items-center justify-center text-rose-600 group-hover:bg-rose-100 transition-colors text-base">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Primary Filters Bar --}}
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm space-y-4">
            <div class="flex flex-col lg:flex-row gap-3 items-center justify-between">
                {{-- Search Bar --}}
                <div class="flex-1 w-full relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="bi bi-search text-slate-400 text-sm"></i>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" 
                           class="block w-full rounded-xl border-0 py-2.5 pl-11 pr-4 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600 sm:text-sm font-medium transition-all" 
                           placeholder="Search by Invoice #, PO #, or Vendor...">
                </div>
                
                {{-- Primary Controls --}}
                <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto justify-end">
                    {{-- PO Status Filter --}}
                    <div class="w-full sm:w-auto">
                        <select wire:model.live="poStatusFilter" class="w-full rounded-xl border-0 py-2.5 pl-3.5 pr-8 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-emerald-600 sm:text-xs font-black uppercase tracking-wider bg-slate-50">
                            @foreach($filterOptions['po_statuses'] as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Per Page --}}
                    <select wire:model.live="perPage" class="rounded-xl border-0 py-2.5 pl-3 pr-8 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-emerald-600 sm:text-xs font-black uppercase tracking-wider bg-slate-50">
                        <option value="10">10 Rows</option>
                        <option value="25">25 Rows</option>
                        <option value="50">50 Rows</option>
                        <option value="100">100 Rows</option>
                    </select>

                    {{-- Advanced Filters Toggle --}}
                    <button wire:click="$toggle('showAdvancedFilters')" 
                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-black uppercase tracking-wider transition-all shadow-sm ring-1 ring-inset {{ $showAdvancedFilters || $activeFiltersCount > 0 ? 'bg-emerald-600 text-white ring-emerald-600' : 'bg-slate-50 text-slate-700 ring-slate-200 hover:bg-slate-100' }}">
                        <i class="bi bi-sliders"></i>
                        <span>Filters</span>
                        @if($activeFiltersCount > 0)
                            <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-black rounded-full {{ $showAdvancedFilters ? 'bg-white text-emerald-700' : 'bg-emerald-600 text-white' }}">
                                {{ $activeFiltersCount }}
                            </span>
                        @endif
                    </button>

                    {{-- Clear All Filters --}}
                    @if($activeFiltersCount > 0)
                        <button wire:click="clearFilters" class="rounded-xl bg-slate-50 px-4 py-2.5 text-xs font-black uppercase tracking-wider text-rose-600 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-rose-50 hover:ring-rose-200 transition-all whitespace-nowrap">
                            <i class="bi bi-x-circle mr-1"></i> Clear
                        </button>
                    @endif
                </div>
            </div>

            {{-- Collapsible Advanced Filters Drawer --}}
            @if($showAdvancedFilters)
                <div class="pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 animate-fadeIn">
                    {{-- Settlement Status --}}
                    <div>
                        <label class="block text-[11px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Settlement</label>
                        <select wire:model.live="settlementFilter" class="w-full rounded-xl border-0 py-2 pl-3 pr-8 text-xs font-bold text-slate-800 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-emerald-600 bg-slate-50">
                            @foreach($filterOptions['settlement_statuses'] as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Payment Schedule --}}
                    <div>
                        <label class="block text-[11px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Schedule</label>
                        <select wire:model.live="paymentStatusFilter" class="w-full rounded-xl border-0 py-2 pl-3 pr-8 text-xs font-bold text-slate-800 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-emerald-600 bg-slate-50">
                            @foreach($filterOptions['payment_statuses'] as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Vendor --}}
                    <div>
                        <label class="block text-[11px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Vendor</label>
                        <select wire:model.live="vendorFilter" class="w-full rounded-xl border-0 py-2 pl-3 pr-8 text-xs font-bold text-slate-800 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-emerald-600 bg-slate-50 truncate">
                            @foreach($filterOptions['vendors'] as $val => $lbl)
                                <option value="{{ $val }}">{{ Str::limit($lbl, 30) }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Currency --}}
                    <div>
                        <label class="block text-[11px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Currency</label>
                        <select wire:model.live="currencyFilter" class="w-full rounded-xl border-0 py-2 pl-3 pr-8 text-xs font-bold text-slate-800 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-emerald-600 bg-slate-50">
                            @foreach($filterOptions['currencies'] as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Attachments --}}
                    <div>
                        <label class="block text-[11px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Attachments</label>
                        <select wire:model.live="attachmentFilter" class="w-full rounded-xl border-0 py-2 pl-3 pr-8 text-xs font-bold text-slate-800 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-emerald-600 bg-slate-50">
                            @foreach($filterOptions['attachment_statuses'] as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date Filter Row --}}
                    <div class="sm:col-span-2 lg:col-span-5 pt-2 border-t border-slate-100 flex flex-wrap items-center gap-3 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400">Date Type:</span>
                            <select wire:model.live="dateType" class="rounded-lg border-0 py-1.5 pl-2.5 pr-7 text-xs font-bold text-slate-700 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-emerald-600 bg-slate-50">
                                <option value="invoice_date">Invoice Date</option>
                                <option value="payment_date">Scheduled Date (Due)</option>
                                <option value="paid_at">Settlement Date (Paid At)</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400">From:</span>
                            <input wire:model.live="dateFrom" type="date" class="rounded-lg border-0 py-1.5 px-3 text-xs font-bold text-slate-700 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-emerald-600 bg-slate-50">
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400">To:</span>
                            <input wire:model.live="dateTo" type="date" class="rounded-lg border-0 py-1.5 px-3 text-xs font-bold text-slate-700 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-emerald-600 bg-slate-50">
                        </div>

                        @if($dateFrom || $dateTo)
                            <button wire:click="clearFilter('dateFrom'); clearFilter('dateTo')" class="text-xs font-bold text-rose-500 hover:text-rose-700 ml-1">
                                Clear Dates
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Active Filter Pills --}}
            @if($activeFiltersCount > 0)
                <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-400 mr-1">Active:</span>

                    @if($search)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            <span>Search: <strong>"{{ $search }}"</strong></span>
                            <button wire:click="clearFilter('search')" class="hover:text-rose-500 text-slate-400"><i class="bi bi-x"></i></button>
                        </span>
                    @endif

                    @if($poStatusFilter)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                            <span>PO Status: <strong>{{ $filterOptions['po_statuses'][$poStatusFilter] ?? $poStatusFilter }}</strong></span>
                            <button wire:click="clearFilter('poStatusFilter')" class="hover:text-rose-500 text-amber-600"><i class="bi bi-x"></i></button>
                        </span>
                    @endif

                    @if($settlementFilter)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold {{ $settlementFilter === 'paid' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                            <span>Settlement: <strong>{{ $filterOptions['settlement_statuses'][$settlementFilter] ?? ucfirst($settlementFilter) }}</strong></span>
                            <button wire:click="clearFilter('settlementFilter')" class="hover:text-rose-500 {{ $settlementFilter === 'paid' ? 'text-emerald-600' : 'text-amber-600' }}"><i class="bi bi-x"></i></button>
                        </span>
                    @endif

                    @if($paymentStatusFilter)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold {{ $paymentStatusFilter === 'past_due' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-indigo-50 text-indigo-800 border border-indigo-200' }}">
                            <span>Schedule: <strong>{{ $filterOptions['payment_statuses'][$paymentStatusFilter] ?? ucfirst($paymentStatusFilter) }}</strong></span>
                            <button wire:click="clearFilter('paymentStatusFilter')" class="hover:text-rose-500 {{ $paymentStatusFilter === 'past_due' ? 'text-rose-600' : 'text-indigo-600' }}"><i class="bi bi-x"></i></button>
                        </span>
                    @endif

                    @if($vendorFilter)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            <span>Vendor: <strong>{{ Str::limit($vendorFilter, 20) }}</strong></span>
                            <button wire:click="clearFilter('vendorFilter')" class="hover:text-rose-500 text-slate-400"><i class="bi bi-x"></i></button>
                        </span>
                    @endif

                    @if($currencyFilter)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            <span>Currency: <strong>{{ $currencyFilter }}</strong></span>
                            <button wire:click="clearFilter('currencyFilter')" class="hover:text-rose-500 text-slate-400"><i class="bi bi-x"></i></button>
                        </span>
                    @endif

                    @if($attachmentFilter)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            <span>Attachments: <strong>{{ $filterOptions['attachment_statuses'][$attachmentFilter] ?? $attachmentFilter }}</strong></span>
                            <button wire:click="clearFilter('attachmentFilter')" class="hover:text-rose-500 text-slate-400"><i class="bi bi-x"></i></button>
                        </span>
                    @endif

                    @if($dateFrom || $dateTo)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            <span>{{ $dateType === 'invoice_date' ? 'Inv Date' : 'Pay Date' }}: <strong>{{ $dateFrom ?: 'Start' }} to {{ $dateTo ?: 'Now' }}</strong></span>
                            <button wire:click="clearFilter('dateFrom'); clearFilter('dateTo')" class="hover:text-rose-500 text-slate-400"><i class="bi bi-x"></i></button>
                        </span>
                    @endif

                    <button wire:click="clearFilters" class="text-xs font-bold text-slate-400 hover:text-rose-600 transition-colors ml-1">
                        Clear All
                    </button>
                </div>
            @endif
        </div>

        {{-- Data Table (Good, Balanced Density) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden relative">
            <div class="overflow-x-auto min-h-[400px]">
                <table class="min-w-full divide-y divide-slate-100 table-fixed">
                    <thead>
                        <tr class="bg-slate-50/80">
                            <th scope="col" class="relative px-4 py-3 w-12 text-center">
                                <span class="sr-only">Row ID</span>
                            </th>
                            <th scope="col" class="w-[20%] px-3 py-3 text-left text-xs font-black text-slate-400 uppercase tracking-widest cursor-pointer hover:text-emerald-600 transition-colors group" wire:click="sortByColumn('invoice_number')">
                                <div class="flex items-center gap-2">
                                    Invoice
                                    @if ($sortBy === 'invoice_number')
                                        <i class="bi bi-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-emerald-500"></i>
                                    @else
                                        <i class="bi bi-sort-up opacity-0 group-hover:opacity-50"></i>
                                    @endif
                                </div>
                            </th>
                            <th scope="col" class="w-[28%] px-3 py-3 text-left text-xs font-black text-slate-400 uppercase tracking-widest">
                                Parent PO & Approval State
                            </th>
                            <th scope="col" class="w-[18%] px-3 py-3 text-left text-xs font-black text-slate-400 uppercase tracking-widest cursor-pointer hover:text-emerald-600 transition-colors group" wire:click="sortByColumn('invoice_date')">
                                <div class="flex items-center gap-2">
                                    Schedule & Settlement
                                    @if ($sortBy === 'invoice_date')
                                        <i class="bi bi-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-emerald-500"></i>
                                    @else
                                        <i class="bi bi-sort-up opacity-0 group-hover:opacity-50"></i>
                                    @endif
                                </div>
                            </th>
                            <th scope="col" class="w-[18%] px-3 py-3 text-right text-xs font-black text-slate-400 uppercase tracking-widest cursor-pointer hover:text-emerald-600 transition-colors group" wire:click="sortByColumn('total')">
                                <div class="flex items-center justify-end gap-2">
                                    Total Amount
                                    @if ($sortBy === 'total')
                                        <i class="bi bi-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-emerald-500"></i>
                                    @else
                                        <i class="bi bi-sort-up opacity-0 group-hover:opacity-50"></i>
                                    @endif
                                </div>
                            </th>
                            <th scope="col" class="w-[14%] px-6 py-3 text-right text-xs font-black text-slate-400 uppercase tracking-widest">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 bg-white">
                        @forelse ($invoices as $invoice)
                            <tr class="group hover:bg-slate-50/50 transition-all duration-150">
                                {{-- Row ID Chip --}}
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    <div class="h-7 w-7 rounded-lg bg-slate-50 flex items-center justify-center text-xs font-bold text-slate-400 border border-slate-100 mx-auto">
                                        {{ $loop->iteration + ($invoices->currentPage() - 1) * $invoices->perPage() }}
                                    </div>
                                </td>
                                
                                {{-- Invoice Info --}}
                                <td class="px-3 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-500 shadow-inner shrink-0 text-sm">
                                            <i class="bi bi-receipt"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-slate-900 group-hover:text-emerald-600 transition-colors truncate max-w-[200px]">
                                                {{ $invoice->invoice_number ?? 'No Number' }}
                                            </p>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md {{ $invoice->files->count() > 0 ? 'bg-slate-100 text-slate-600' : 'bg-rose-50 text-rose-500' }}">
                                                    <i class="bi bi-paperclip mr-0.5"></i>{{ $invoice->files->count() }} Attachments
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                {{-- Parent PO & Approval State --}}
                                <td class="px-3 py-3.5">
                                    <div class="flex flex-col justify-center gap-1">
                                        @if($invoice->purchaseOrder)
                                            @php
                                                $po = $invoice->purchaseOrder;
                                                $statusEnum = $po->getStatusEnum();
                                                $isPending = $po->workflow_status === 'IN_REVIEW';
                                                $daysPending = $isPending && $po->approvalRequest?->submitted_at ? now()->diffInDays($po->approvalRequest->submitted_at) : 0;
                                            @endphp
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <a href="{{ route('po.view', $invoice->purchase_order_id) }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 hover:underline transition-all">
                                                    {{ $po->po_number }}
                                                </a>

                                                {{-- PO Status Badge --}}
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider border {{ $statusEnum->cssClass() }}">
                                                    {{ $statusEnum->label() }}
                                                </span>

                                                @if($isPending && $daysPending > 0)
                                                    <span class="text-[10px] font-black {{ $daysPending > 3 ? 'text-rose-500' : 'text-amber-500' }} flex items-center gap-0.5" title="Days in Review">
                                                        <i class="bi bi-clock-history"></i>{{ $daysPending }}d
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-2 text-xs mt-0.5">
                                                <span class="font-bold text-slate-500 truncate max-w-[210px]" title="{{ $po->vendor_name }}">
                                                    {{ $po->vendor_name }}
                                                </span>

                                                @if($isPending && $po->workflow_step)
                                                    <span class="text-[10px] font-medium text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded truncate max-w-[130px]" title="Current Reviewer: {{ $po->workflow_step }}">
                                                        <i class="bi bi-person mr-0.5"></i>{{ $po->workflow_step }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-400 w-fit">
                                                Orphaned (No PO)
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                
                                {{-- Dates, Schedule & Settlement Status --}}
                                <td class="px-3 py-3.5">
                                    <div class="flex flex-col gap-1 justify-center">
                                        {{-- Invoice Date --}}
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="font-bold text-slate-400 uppercase text-[10px] w-7">Inv:</span>
                                            <span class="font-semibold text-slate-700">{{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : '-' }}</span>
                                        </div>

                                        {{-- Target / Due Date --}}
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="font-bold text-slate-400 uppercase text-[10px] w-7">Due:</span>
                                            @if($invoice->payment_date)
                                                <span class="font-mono text-[11px] {{ !$invoice->paid_at && $invoice->payment_date->isPast() && !$invoice->payment_date->isToday() ? 'text-rose-600 font-bold' : 'text-slate-600 font-medium' }}">
                                                    {{ $invoice->payment_date->format('d M Y') }}
                                                </span>
                                            @else
                                                <span class="text-[10px] text-slate-400 italic">Unscheduled</span>
                                            @endif
                                        </div>

                                        {{-- Settlement Status Pill --}}
                                        <div class="flex items-center gap-2 text-xs mt-0.5">
                                            <span class="font-bold text-slate-400 uppercase text-[10px] w-7">Pay:</span>
                                            @if($invoice->paid_at)
                                                <span class="inline-flex items-center gap-1 font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200/70 px-1.5 py-0.5 rounded text-[10px] font-mono" title="Settled on {{ $invoice->paid_at->format('d M Y') }}">
                                                    <i class="bi bi-check-circle-fill text-[9px] text-emerald-600"></i>
                                                    Paid: {{ $invoice->paid_at->format('d M Y') }}
                                                </span>
                                            @elseif($invoice->payment_date && $invoice->payment_date->isPast() && !$invoice->payment_date->isToday())
                                                <span class="inline-flex items-center gap-1 font-bold text-rose-700 bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded text-[10px]" title="Overdue open invoice">
                                                    <i class="bi bi-exclamation-triangle-fill text-[9px] text-rose-500"></i>
                                                    Past Due
                                                </span>
                                            @else
                                                <span class="inline-flex items-center font-bold text-amber-700 bg-amber-50 border border-amber-200/70 px-1.5 py-0.5 rounded text-[10px]">
                                                    Unpaid
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                
                                {{-- Total Amount --}}
                                <td class="px-3 py-3.5 text-right whitespace-nowrap">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">{{ $invoice->total_currency }}</span>
                                    <span class="font-mono font-black text-slate-800 text-sm ml-1">{{ number_format($invoice->total, 2, '.', ',') }}</span>
                                </td>
                                
                                {{-- Actions --}}
                                <td class="px-4 py-3.5 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @can('changePaidStatus', $invoice)
                                            @if($invoice->paid_at)
                                                <button wire:click="markAsUnpaid({{ $invoice->id }})" 
                                                        class="h-7 px-2.5 rounded-lg bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-rose-600 flex items-center gap-1 text-[11px] font-bold transition-all border border-slate-200"
                                                        title="Revert to Unpaid">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                    Unpay
                                                </button>
                                            @else
                                                <button wire:click="openPaymentModal({{ $invoice->id }})" 
                                                        class="h-7 px-2.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white flex items-center gap-1 text-[11px] font-bold transition-all border border-emerald-200 shadow-2xs"
                                                        title="Mark as Paid">
                                                    <i class="bi bi-check2"></i>
                                                    Pay
                                                </button>
                                            @endif
                                        @endcan

                                        @if($invoice->purchase_order_id)
                                            <a href="{{ route('po.view', $invoice->purchase_order_id) }}" 
                                               class="h-7 px-2.5 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white flex items-center gap-1 font-bold transition-all shadow-2xs border border-indigo-100 text-[11px]">
                                                View PO
                                                <i class="bi bi-arrow-right text-[10px]"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="mx-auto h-24 w-24 bg-slate-50 rounded-full flex items-center justify-center mb-4 ring-8 ring-white shadow-inner">
                                        <i class="bi bi-inbox text-3xl text-slate-300"></i>
                                    </div>
                                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest">No Invoices Found</h3>
                                    <p class="mt-2 text-xs font-medium text-slate-500 max-w-sm mx-auto leading-relaxed">
                                        We couldn't find any invoices matching your current filters. Try clearing or adjusting your search criteria.
                                    </p>
                                    @if($activeFiltersCount > 0)
                                        <button wire:click="clearFilters" class="mt-4 px-4 py-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-xl font-bold text-xs transition-colors">
                                            Clear All Filters
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Clean Pagination Footer --}}
            @if($invoices->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $invoices->links(data: ['scrollTo' => false]) }}
                </div>
            @endif
            
            {{-- Loading Overlay --}}
            <div wire:loading.delay.longer 
                 wire:target="search, poStatusFilter, paymentStatusFilter, vendorFilter, currencyFilter, attachmentFilter, dateFrom, dateTo, dateType, perPage, sortByColumn, filterByStat, clearFilter, clearFilters" 
                 class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 flex items-center justify-center transition-all">
                <div class="flex flex-col items-center bg-white p-6 rounded-3xl shadow-2xl">
                    <div class="h-10 w-10 border-4 border-emerald-100 border-t-emerald-600 rounded-full animate-spin"></div>
                    <p class="mt-4 text-xs font-black text-slate-600 uppercase tracking-widest">Loading Data...</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Settlement Date Modal --}}
    @if($showPaymentModal && $settlingInvoice)
        <template x-teleport="body">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-100" @click.outside="$wire.closePaymentModal()">
                    {{-- Header --}}
                    <div class="px-6 py-4 bg-gradient-to-r from-emerald-500/10 to-teal-500/10 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="h-9 w-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center shadow-sm">
                                <i class="bi bi-cash-stack text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">Record Payment</h3>
                                <p class="text-[11px] text-slate-500 font-medium">Set invoice settlement date</p>
                            </div>
                        </div>
                        <button wire:click="closePaymentModal" class="h-8 w-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>

                    {{-- Invoice Summary Card --}}
                    <div class="p-6 space-y-4">
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-500 uppercase tracking-wider text-[10px]">Invoice</span>
                                <span class="font-mono font-bold text-slate-800">#{{ $settlingInvoice->invoice_number }}</span>
                            </div>
                            @if($settlingInvoice->purchaseOrder)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-slate-500 uppercase tracking-wider text-[10px]">Vendor</span>
                                    <span class="font-semibold text-slate-700 truncate max-w-[200px]" title="{{ $settlingInvoice->purchaseOrder->vendor_name }}">{{ $settlingInvoice->purchaseOrder->vendor_name }}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-200/60">
                                <span class="font-bold text-slate-500 uppercase tracking-wider text-[10px]">Total Amount</span>
                                <span class="font-mono font-black text-emerald-700 text-sm">
                                    {{ $settlingInvoice->total_currency }} {{ number_format($settlingInvoice->total, 2, '.', ',') }}
                                </span>
                            </div>
                        </div>

                        {{-- Date Input --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-slate-700">Settlement Date (Paid At)</label>
                                <button type="button" 
                                        wire:click="$set('settlementDate', '{{ now()->format('Y-m-d') }}')" 
                                        class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline">
                                    Set to Today
                                </button>
                            </div>
                            <input type="date" wire:model="settlementDate" class="w-full px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-medium focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 shadow-xs transition-all">
                            @error('settlementDate') 
                                <span class="text-rose-500 text-xs mt-1 block font-medium">{{ $message }}</span> 
                            @enderror
                            <p class="text-[11px] text-slate-400 mt-1.5 leading-relaxed">
                                Defaults to today (<span class="font-medium text-slate-600">{{ now()->format('d M Y') }}</span>). Select an earlier or specific date if the payment occurred on a different day.
                            </p>
                        </div>
                    </div>

                    {{-- Footer Actions --}}
                    <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button wire:click="closePaymentModal" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 rounded-xl transition-colors">
                            Cancel
                        </button>
                        <button wire:click="confirmPayment" 
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm hover:shadow transition-all flex items-center gap-1.5 disabled:opacity-50">
                            <i class="bi bi-check2 text-sm" wire:loading.remove wire:target="confirmPayment"></i>
                            <span wire:loading wire:target="confirmPayment" class="inline-block animate-spin mr-1">⌛</span>
                            Confirm & Mark Paid
                        </button>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>
