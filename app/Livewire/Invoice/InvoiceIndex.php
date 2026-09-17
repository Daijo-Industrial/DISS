<?php

namespace App\Livewire\Invoice;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceIndex extends Component
{
    use WithPagination;

    public $search = '';

    public $yearFilter = '';

    public $poStatusFilter = '';

    public $paymentStatusFilter = '';

    public $settlementFilter = '';

    public $vendorFilter = '';

    public $currencyFilter = '';

    public $attachmentFilter = '';

    public $dateType = 'invoice_date'; // 'invoice_date', 'payment_date', or 'paid_at'

    public $dateFrom = '';

    public $dateTo = '';

    public $showAdvancedFilters = false;

    public $perPage = 10;

    public $sortBy = 'created_at';

    public $sortDirection = 'desc';

    // Payment Settlement Modal
    public bool $showPaymentModal = false;
    public ?int $settlingInvoiceId = null;
    public ?Invoice $settlingInvoice = null;
    public string $settlementDate = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'yearFilter' => ['except' => ''],
        'poStatusFilter' => ['except' => ''],
        'paymentStatusFilter' => ['except' => ''],
        'settlementFilter' => ['except' => ''],
        'vendorFilter' => ['except' => ''],
        'currencyFilter' => ['except' => ''],
        'attachmentFilter' => ['except' => ''],
        'dateType' => ['except' => 'invoice_date'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortBy' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount()
    {
        if ($this->yearFilter === '') {
            $this->yearFilter = (string) now()->year;
        }
    }

    public function updatingYearFilter()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPoStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingPaymentStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingSettlementFilter()
    {
        $this->resetPage();
    }

    public function updatingVendorFilter()
    {
        $this->resetPage();
    }

    public function updatingCurrencyFilter()
    {
        $this->resetPage();
    }

    public function updatingAttachmentFilter()
    {
        $this->resetPage();
    }

    public function updatingDateType()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function sortByColumn($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function filterByStat($type)
    {
        $this->clearFilters();

        switch ($type) {
            case 'pending_approval':
                $this->poStatusFilter = 'IN_REVIEW';
                break;
            case 'po_approved':
                $this->poStatusFilter = 'APPROVED';
                break;
            case 'past_due':
                $this->paymentStatusFilter = 'past_due';
                $this->settlementFilter = 'unpaid';
                break;
            case 'upcoming':
                $this->paymentStatusFilter = 'upcoming';
                $this->settlementFilter = 'unpaid';
                break;
            case 'unpaid':
                $this->settlementFilter = 'unpaid';
                break;
            case 'paid':
                $this->settlementFilter = 'paid';
                break;
            case 'all':
                // Already cleared
                break;
        }

        $this->resetPage();
    }

    public function clearFilter($key)
    {
        if (property_exists($this, $key)) {
            if ($key === 'dateType') {
                $this->dateType = 'invoice_date';
            } elseif ($key === 'yearFilter') {
                $this->yearFilter = 'all';
            } else {
                $this->$key = '';
            }
        }
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->yearFilter = (string) now()->year;
        $this->poStatusFilter = '';
        $this->paymentStatusFilter = '';
        $this->settlementFilter = '';
        $this->vendorFilter = '';
        $this->currencyFilter = '';
        $this->attachmentFilter = '';
        $this->dateType = 'invoice_date';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function getActiveFiltersCountProperty(): int
    {
        $count = 0;
        if (!empty($this->search)) $count++;
        if (!empty($this->poStatusFilter)) $count++;
        if (!empty($this->paymentStatusFilter)) $count++;
        if (!empty($this->settlementFilter)) $count++;
        if (!empty($this->vendorFilter)) $count++;
        if (!empty($this->currencyFilter)) $count++;
        if (!empty($this->attachmentFilter)) $count++;
        if (!empty($this->dateFrom)) $count++;
        if (!empty($this->dateTo)) $count++;
        return $count;
    }

    public function getFilterOptionsProperty(): array
    {
        $years = Invoice::query()
            ->selectRaw('DISTINCT YEAR(COALESCE(invoice_date, created_at)) as year')
            ->pluck('year')
            ->filter(fn ($y) => $y >= 2020 && $y <= now()->year + 1)
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        $yearOptions = ['all' => 'All Years'];
        foreach ($years as $y) {
            $yearOptions[(string) $y] = (string) $y;
        }

        return [
            'years' => $yearOptions,
            'po_statuses' => [
                '' => 'All PO Statuses',
                'IN_REVIEW' => 'Pending Approval',
                'APPROVED' => 'Approved',
                'REJECTED' => 'Rejected',
                'DRAFT' => 'Draft',
                'CANCELLED' => 'Cancelled',
                'ORPHANED' => 'Orphaned (No PO)',
            ],
            'settlement_statuses' => [
                '' => 'All Settlements',
                'unpaid' => 'Unpaid / Open',
                'paid' => 'Paid / Settled',
            ],
            'payment_statuses' => [
                '' => 'All Schedules',
                'past_due' => 'Past Due (< Today & Unpaid)',
                'upcoming' => 'Upcoming (>= Today & Unpaid)',
                'unscheduled' => 'Unscheduled (No Date)',
                'paid' => 'Settled / Paid',
                'unpaid' => 'Unpaid / Open',
            ],
            'attachment_statuses' => [
                '' => 'All Attachments',
                'with_attachments' => 'With Attachments',
                'missing_attachments' => 'Missing Attachments',
            ],
            'vendors' => ['' => 'All Vendors'] + PurchaseOrder::query()
                ->whereNotNull('vendor_name')
                ->whereHas('invoices')
                ->distinct()
                ->orderBy('vendor_name')
                ->pluck('vendor_name', 'vendor_name')
                ->toArray(),
            'currencies' => ['' => 'All Currencies'] + Invoice::query()
                ->whereNotNull('total_currency')
                ->distinct()
                ->orderBy('total_currency')
                ->pluck('total_currency', 'total_currency')
                ->toArray(),
        ];
    }

    public function getStatsProperty(): array
    {
        $today = today();
        $baseQuery = Invoice::query();

        if ($this->yearFilter && $this->yearFilter !== 'all') {
            $year = (int) $this->yearFilter;
            $baseQuery->where(function ($q) use ($year) {
                $q->whereYear('invoice_date', $year)
                    ->orWhere(function ($sq) use ($year) {
                        $sq->whereNull('invoice_date')
                            ->whereYear('created_at', $year);
                    });
            });
        }

        return [
            'total_count' => (clone $baseQuery)->count(),
            'total_amount_idr' => (float) (clone $baseQuery)->where('total_currency', 'IDR')->sum('total'),
            'pending_approval' => (clone $baseQuery)->whereHas('purchaseOrder', function ($q) {
                $q->withWorkflowStatus('IN_REVIEW');
            })->count(),
            'pending_approval_sum' => (float) (clone $baseQuery)->whereHas('purchaseOrder', function ($q) {
                $q->withWorkflowStatus('IN_REVIEW');
            })->where('total_currency', 'IDR')->sum('total'),
            'po_approved' => (clone $baseQuery)->whereHas('purchaseOrder', function ($q) {
                $q->withWorkflowStatus('APPROVED');
            })->count(),
            'po_approved_sum' => (float) (clone $baseQuery)->whereHas('purchaseOrder', function ($q) {
                $q->withWorkflowStatus('APPROVED');
            })->where('total_currency', 'IDR')->sum('total'),
            // Truly past due: UNPAID and scheduled payment_date has passed
            'past_due' => (clone $baseQuery)->whereNull('paid_at')
                ->whereNotNull('payment_date')
                ->where('payment_date', '<', $today)
                ->count(),
            'past_due_sum' => (float) (clone $baseQuery)->whereNull('paid_at')
                ->whereNotNull('payment_date')
                ->where('payment_date', '<', $today)
                ->where('total_currency', 'IDR')
                ->sum('total'),
            // Upcoming: UNPAID and scheduled payment_date is today or in future
            'upcoming' => (clone $baseQuery)->whereNull('paid_at')
                ->whereNotNull('payment_date')
                ->where('payment_date', '>=', $today)
                ->count(),
            'upcoming_sum' => (float) (clone $baseQuery)->whereNull('paid_at')
                ->whereNotNull('payment_date')
                ->where('payment_date', '>=', $today)
                ->where('total_currency', 'IDR')
                ->sum('total'),
            // Unscheduled: UNPAID with no payment_date
            'unscheduled' => (clone $baseQuery)->whereNull('paid_at')->whereNull('payment_date')->count(),
            'unscheduled_sum' => (float) (clone $baseQuery)->whereNull('paid_at')->whereNull('payment_date')->where('total_currency', 'IDR')->sum('total'),
            // Total Unpaid liabilities
            'unpaid' => (clone $baseQuery)->whereNull('paid_at')->count(),
            'unpaid_sum' => (float) (clone $baseQuery)->whereNull('paid_at')->where('total_currency', 'IDR')->sum('total'),
            // Total Paid
            'paid' => (clone $baseQuery)->whereNotNull('paid_at')->count(),
            'paid_sum' => (float) (clone $baseQuery)->whereNotNull('paid_at')->where('total_currency', 'IDR')->sum('total'),
        ];
    }

    public function getInvoicesQuery()
    {
        $query = Invoice::query()
            ->with([
                'purchaseOrder.approvalRequest.steps',
                'files',
            ]);

        // 1. Free-text search
        if ($this->search) {
            $searchTerm = trim($this->search);
            if (strlen($searchTerm) > 0) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('invoice_number', 'like', '%' . $searchTerm . '%')
                        ->orWhereHas('purchaseOrder', function ($poQuery) use ($searchTerm) {
                            $poQuery->where('po_number', 'like', '%' . $searchTerm . '%')
                                ->orWhere('vendor_name', 'like', '%' . $searchTerm . '%');
                        });
                });
            }
        }

        // 2. Parent PO Workflow / Approval Status filter
        if ($this->poStatusFilter) {
            if ($this->poStatusFilter === 'ORPHANED') {
                $query->whereNull('purchase_order_id');
            } else {
                $query->whereHas('purchaseOrder', function ($poQuery) {
                    $poQuery->withWorkflowStatus($this->poStatusFilter);
                });
            }
        }

        // 3. Settlement filter (Paid vs Unpaid)
        if ($this->settlementFilter) {
            if ($this->settlementFilter === 'paid') {
                $query->whereNotNull('paid_at');
            } elseif ($this->settlementFilter === 'unpaid') {
                $query->whereNull('paid_at');
            }
        }

        // 4. Payment schedule filter
        if ($this->paymentStatusFilter) {
            $today = today();
            switch ($this->paymentStatusFilter) {
                case 'past_due':
                case 'overdue':
                    $query->whereNull('paid_at')
                        ->whereNotNull('payment_date')
                        ->where('payment_date', '<', $today);
                    break;
                case 'upcoming':
                    $query->whereNull('paid_at')
                        ->whereNotNull('payment_date')
                        ->where('payment_date', '>=', $today);
                    break;
                case 'unscheduled':
                    $query->whereNull('payment_date');
                    break;
                case 'paid':
                    $query->whereNotNull('paid_at');
                    break;
                case 'unpaid':
                    $query->whereNull('paid_at');
                    break;
            }
        }

        // 5. Vendor filter
        if ($this->vendorFilter) {
            $query->whereHas('purchaseOrder', function ($poQuery) {
                $poQuery->where('vendor_name', $this->vendorFilter);
            });
        }

        // 6. Currency filter
        if ($this->currencyFilter) {
            $query->where('total_currency', $this->currencyFilter);
        }

        // 7. Attachment filter
        if ($this->attachmentFilter === 'with_attachments') {
            $query->whereExists(function ($sub) {
                $sub->selectRaw(1)->from('files')->whereRaw("files.doc_id = CONCAT('INV-', invoices.id)");
            });
        } elseif ($this->attachmentFilter === 'missing_attachments') {
            $query->whereNotExists(function ($sub) {
                $sub->selectRaw(1)->from('files')->whereRaw("files.doc_id = CONCAT('INV-', invoices.id)");
            });
        }

        // Year filter
        if ($this->yearFilter && $this->yearFilter !== 'all') {
            $year = (int) $this->yearFilter;
            $query->where(function ($q) use ($year) {
                $q->whereYear('invoice_date', $year)
                    ->orWhere(function ($sq) use ($year) {
                        $sq->whereNull('invoice_date')
                            ->whereYear('created_at', $year);
                    });
            });
        }

        // 8. Date range filter
        $validDateColumns = ['invoice_date', 'payment_date', 'paid_at'];
        $dateColumn = in_array($this->dateType, $validDateColumns) ? $this->dateType : 'invoice_date';

        if ($this->dateFrom) {
            $query->whereDate($dateColumn, '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate($dateColumn, '<=', $this->dateTo);
        }

        // Optimized sorting
        $sortableColumns = [
            'invoice_number', 'invoice_date', 'payment_date', 'paid_at', 'total', 'created_at',
        ];

        if (in_array($this->sortBy, $sortableColumns)) {
            $query->orderBy($this->sortBy, $this->sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function openPaymentModal(int $invoiceId)
    {
        $invoice = Invoice::with('purchaseOrder')->findOrFail($invoiceId);
        $this->authorize('changePaidStatus', $invoice);

        $this->settlingInvoiceId = $invoiceId;
        $this->settlingInvoice = $invoice;
        $this->settlementDate = now()->format('Y-m-d');
        $this->showPaymentModal = true;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->settlingInvoiceId = null;
        $this->settlingInvoice = null;
        $this->settlementDate = '';
        $this->resetValidation('settlementDate');
    }

    public function confirmPayment()
    {
        if (!$this->settlingInvoiceId) {
            return;
        }

        $this->validate([
            'settlementDate' => 'required|date',
        ]);

        $this->markAsPaid($this->settlingInvoiceId, $this->settlementDate);
        $this->closePaymentModal();
    }

    public function markAsPaid(int $invoiceId, ?string $paidDate = null)
    {
        $invoice = Invoice::with('purchaseOrder')->findOrFail($invoiceId);

        $this->authorize('changePaidStatus', $invoice);

        $invoice->markAsPaid($paidDate ?: today());

        $this->dispatch('banner-message', [
            'style' => 'success',
            'message' => "Invoice #{$invoice->invoice_number} marked as paid on " . ($invoice->paid_at ? $invoice->paid_at->format('d M Y') : 'today') . '.',
        ]);
    }

    public function markAsUnpaid(int $invoiceId)
    {
        $invoice = Invoice::with('purchaseOrder')->findOrFail($invoiceId);

        $this->authorize('changePaidStatus', $invoice);

        $invoice->markAsUnpaid();

        $this->dispatch('banner-message', [
            'style' => 'info',
            'message' => "Invoice #{$invoice->invoice_number} marked as unpaid.",
        ]);
    }

    public function getInvoicesProperty()
    {
        return $this->getInvoicesQuery()->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.invoice.invoice-index', [
            'invoices' => $this->invoices,
            'stats' => $this->stats,
            'filterOptions' => $this->filterOptions,
            'activeFiltersCount' => $this->activeFiltersCount,
        ]);
    }
}
