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

    public $poStatusFilter = '';

    public $paymentStatusFilter = '';

    public $vendorFilter = '';

    public $currencyFilter = '';

    public $attachmentFilter = '';

    public $dateType = 'invoice_date'; // 'invoice_date' or 'payment_date'

    public $dateFrom = '';

    public $dateTo = '';

    public $showAdvancedFilters = false;

    public $perPage = 10;

    public $sortBy = 'created_at';

    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'poStatusFilter' => ['except' => ''],
        'paymentStatusFilter' => ['except' => ''],
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
            case 'unpaid':
                $this->paymentStatusFilter = 'unpaid';
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
            } else {
                $this->$key = '';
            }
        }
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->poStatusFilter = '';
        $this->paymentStatusFilter = '';
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
        if (!empty($this->vendorFilter)) $count++;
        if (!empty($this->currencyFilter)) $count++;
        if (!empty($this->attachmentFilter)) $count++;
        if (!empty($this->dateFrom)) $count++;
        if (!empty($this->dateTo)) $count++;
        return $count;
    }

    public function getFilterOptionsProperty(): array
    {
        return [
            'po_statuses' => [
                '' => 'All PO Statuses',
                'IN_REVIEW' => 'Pending Approval',
                'APPROVED' => 'Approved',
                'REJECTED' => 'Rejected',
                'DRAFT' => 'Draft',
                'CANCELLED' => 'Cancelled',
                'ORPHANED' => 'Orphaned (No PO)',
            ],
            'payment_statuses' => [
                '' => 'All Payment Statuses',
                'paid' => 'Paid',
                'unpaid' => 'Unpaid / Pending',
                'overdue' => 'Overdue (> 30 Days)',
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
        return [
            'total_count' => Invoice::count(),
            'pending_approval' => Invoice::whereHas('purchaseOrder', function ($q) {
                $q->withWorkflowStatus('IN_REVIEW');
            })->count(),
            'pending_approval_sum' => (float) Invoice::whereHas('purchaseOrder', function ($q) {
                $q->withWorkflowStatus('IN_REVIEW');
            })->where('total_currency', 'IDR')->sum('total'),
            'po_approved' => Invoice::whereHas('purchaseOrder', function ($q) {
                $q->withWorkflowStatus('APPROVED');
            })->count(),
            'po_approved_sum' => (float) Invoice::whereHas('purchaseOrder', function ($q) {
                $q->withWorkflowStatus('APPROVED');
            })->where('total_currency', 'IDR')->sum('total'),
            'unpaid' => Invoice::whereNull('payment_date')->count(),
            'unpaid_sum' => (float) Invoice::whereNull('payment_date')->where('total_currency', 'IDR')->sum('total'),
            'total_amount_idr' => (float) Invoice::where('total_currency', 'IDR')->sum('total'),
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

        // 3. Payment status filter
        if ($this->paymentStatusFilter) {
            switch ($this->paymentStatusFilter) {
                case 'paid':
                    $query->whereNotNull('payment_date');
                    break;
                case 'unpaid':
                    $query->whereNull('payment_date');
                    break;
                case 'overdue':
                    $query->whereNull('payment_date')
                        ->whereNotNull('invoice_date')
                        ->where('invoice_date', '<', now()->subDays(30));
                    break;
            }
        }

        // 4. Vendor filter
        if ($this->vendorFilter) {
            $query->whereHas('purchaseOrder', function ($poQuery) {
                $poQuery->where('vendor_name', $this->vendorFilter);
            });
        }

        // 5. Currency filter
        if ($this->currencyFilter) {
            $query->where('total_currency', $this->currencyFilter);
        }

        // 6. Attachment filter
        if ($this->attachmentFilter === 'with_attachments') {
            $query->whereExists(function ($sub) {
                $sub->selectRaw(1)->from('files')->whereRaw("files.doc_id = CONCAT('INV-', invoices.id)");
            });
        } elseif ($this->attachmentFilter === 'missing_attachments') {
            $query->whereNotExists(function ($sub) {
                $sub->selectRaw(1)->from('files')->whereRaw("files.doc_id = CONCAT('INV-', invoices.id)");
            });
        }

        // 7. Date range filter
        $validDateColumns = ['invoice_date', 'payment_date'];
        $dateColumn = in_array($this->dateType, $validDateColumns) ? $this->dateType : 'invoice_date';

        if ($this->dateFrom) {
            $query->whereDate($dateColumn, '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate($dateColumn, '<=', $this->dateTo);
        }

        // Optimized sorting
        $sortableColumns = [
            'invoice_number', 'invoice_date', 'payment_date', 'total', 'created_at',
        ];

        if (in_array($this->sortBy, $sortableColumns)) {
            $query->orderBy($this->sortBy, $this->sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
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
