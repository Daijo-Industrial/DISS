<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'invoice_number',
        'invoice_date',
        'payment_date',
        'paid_at',
        'total',
        'total_currency',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'payment_date' => 'date',
        'paid_at' => 'date',
        'total' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * File attachments via the existing `files` table doc_id convention.
     *
     * Convention: doc_id = 'INV-{invoice.id}'
     *
     * Usage:
     *   $invoice->files                         // collection of File models
     *   File::create(['doc_id' => $invoice->file_doc_id, ...])
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'doc_id', 'file_doc_id');
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Canonical doc_id string used to link File records to this invoice.
     * Stored as a virtual attribute — never persisted to the database.
     */
    public function getFileDocIdAttribute(): string
    {
        return 'INV-' . $this->id;
    }

    /**
     * Check if invoice has been settled/paid.
     */
    public function getIsPaidAttribute(): bool
    {
        return !is_null($this->paid_at);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopePaid($query)
    {
        return $query->whereNotNull('paid_at');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereNull('paid_at');
    }

    public function scopePastDue($query)
    {
        return $query->whereNull('paid_at')
            ->whereNotNull('payment_date')
            ->where('payment_date', '<', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->whereNull('paid_at')
            ->whereNotNull('payment_date')
            ->where('payment_date', '>=', today());
    }

    // =========================================================================
    // Actions
    // =========================================================================

    public function markAsPaid($date = null): bool
    {
        $this->paid_at = $date ? \Carbon\Carbon::parse($date) : today();
        return $this->save();
    }

    public function markAsUnpaid(): bool
    {
        $this->paid_at = null;
        return $this->save();
    }
}
