<?php

namespace App\Models;

use App\Infrastructure\Persistence\Eloquent\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'document_type',
        'document_number',
        'expired_date',
        'last_renewed_date',
        'attachment_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'expired_date' => 'date',
        'last_renewed_date' => 'date',
    ];

    public const TYPE_STNK = 'stnk';

    public const TYPE_KIR = 'kir';

    public const TYPE_STNK_ANNUAL = 'stnk_annual';

    public const TYPE_STNK_FIVE_YEAR = 'stnk_five_year';

    public const TYPE_INSURANCE = 'insurance';

    public const TYPE_OTHER = 'other';

    public static function typeLabels(): array
    {
        return [
            self::TYPE_STNK => __('fleet.documents.types.stnk'),
            self::TYPE_KIR => __('fleet.documents.types.kir'),
            self::TYPE_STNK_ANNUAL => __('fleet.documents.types.stnk_annual'),
            self::TYPE_STNK_FIVE_YEAR => __('fleet.documents.types.stnk_five_year'),
            self::TYPE_INSURANCE => __('fleet.documents.types.insurance'),
            self::TYPE_OTHER => __('fleet.documents.types.other'),
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeLabels()[$this->document_type] ?? ucfirst(str_replace('_', ' ', $this->document_type));
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return $this->type_label;
    }

    public function getIsImageAttachmentAttribute(): bool
    {
        return (bool) preg_match('/\.(jpg|jpeg|png|webp)$/i', (string) $this->attachment_path);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? asset('storage/' . $this->attachment_path) : null;
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDaysRemainingAttribute(): int
    {
        if (! $this->expired_date) {
            return 999;
        }

        return (int) now()->startOfDay()->diffInDays($this->expired_date->startOfDay(), false);
    }

    public function getStatusAttribute(): string
    {
        $days = $this->days_remaining;

        if ($days < 0) {
            return 'expired';
        }
        if ($days <= 7) {
            return 'critical';
        }
        if ($days <= 30) {
            return 'warning';
        }

        return 'valid';
    }

    public function getStatusLabelAttribute(): string
    {
        $days = $this->days_remaining;

        return match ($this->status) {
            'expired' => __('fleet.documents.statuses.expired', ['days' => abs($days)]),
            'critical' => __('fleet.documents.statuses.critical', ['days' => $days]),
            'warning' => __('fleet.documents.statuses.warning', ['days' => $days]),
            default => __('fleet.documents.statuses.valid', ['days' => $days]),
        };
    }

    public function getStatusBadgeClassesAttribute(): string
    {
        return match ($this->status) {
            'expired' => 'bg-rose-100 text-rose-800 ring-1 ring-rose-200',
            'critical' => 'bg-orange-100 text-orange-800 ring-1 ring-orange-200',
            'warning' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-200',
            default => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200',
        };
    }
}
