<div>
    <div class="px-4 sm:px-6 lg:px-8 py-5 max-w-[1600px] mx-auto space-y-4">
        {{-- Header Section (Minimal) --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="bi bi-receipt text-emerald-600"></i>
                    Invoices
                </h1>
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

            {{-- Unpaid Invoices --}}
            <div wire:click="filterByStat('unpaid')"
                 class="cursor-pointer bg-white p-3.5 rounded-xl border transition-all duration-200 hover:shadow-sm relative overflow-hidden group {{ $paymentStatusFilter === 'unpaid' ? 'ring-2 ring-indigo-500 border-indigo-500 bg-indigo-50/20' : 'border-slate-100 hover:border-indigo-200' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider">Unpaid Invoices</p>
                        <h3 class="text-xl font-black text-slate-900 tracking-tight mt-0.5">{{ number_format($stats['unpaid']) }}</h3>
                        <div class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] font-mono font-bold text-indigo-800 bg-indigo-50 border border-indigo-200/60 px-2 py-0.5 rounded-md">
                            <span class="text-[10px] text-indigo-500 font-black">SUM:</span> Rp {{ number_format($stats['unpaid_sum'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="h-9 w-9 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:bg-indigo-100 transition-colors text-base">
                        <i class="bi bi-wallet2"></i>
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
                <div class="pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 animate-fadeIn">
                    {{-- Payment Status --}}
                    <div>
                        <label class="block text-[11px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Payment Status</label>
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
                    <div class="sm:col-span-2 lg:col-span-4 pt-2 border-t border-slate-100 flex flex-wrap items-center gap-3 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400">Date Type:</span>
                            <select wire:model.live="dateType" class="rounded-lg border-0 py-1.5 pl-2.5 pr-7 text-xs font-bold text-slate-700 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-emerald-600 bg-slate-50">
                                <option value="invoice_date">Invoice Date</option>
                                <option value="payment_date">Payment Date</option>
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

                    @if($paymentStatusFilter)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-800 border border-indigo-200">
                            <span>Payment: <strong>{{ $filterOptions['payment_statuses'][$paymentStatusFilter] ?? $paymentStatusFilter }}</strong></span>
                            <button wire:click="clearFilter('paymentStatusFilter')" class="hover:text-rose-500 text-indigo-600"><i class="bi bi-x"></i></button>
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
                                    Dates & Payment
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
                                
                                {{-- Dates & Payment Status --}}
                                <td class="px-3 py-3.5">
                                    <div class="flex flex-col gap-1 justify-center">
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="font-bold text-slate-400 uppercase text-[10px] w-7">Inv:</span>
                                            <span class="font-semibold text-slate-700">{{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : '-' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="font-bold text-slate-400 uppercase text-[10px] w-7">Pay:</span>
                                            @if($invoice->payment_date)
                                                <span class="font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded text-[11px] font-mono">
                                                    {{ $invoice->payment_date->format('d M Y') }}
                                                </span>
                                            @else
                                                <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded">
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
                                <td class="px-6 py-3.5 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($invoice->purchaseOrder)
                                            <a href="{{ route('po.view', $invoice->purchase_order_id) }}" 
                                               class="h-8 px-3 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white flex items-center gap-1.5 font-bold transition-all shadow-sm border border-indigo-100 text-xs">
                                                View PO
                                                <i class="bi bi-arrow-right text-[11px]"></i>
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
</div>
