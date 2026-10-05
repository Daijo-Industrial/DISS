<?php

namespace App\Models;

use App\Infrastructure\Persistence\Eloquent\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'parent_inspection_id',
        'inspection_type',
        'checked_at',
        'driver_name',
        'created_by',
        'inspector_id',
        'odometer',
        'fuel_percentage',
        'checklist_results',
        'severity',
        'defect_notes',
        'defect_photos',
        'trip_purpose',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'odometer' => 'integer',
        'fuel_percentage' => 'integer',
        'checklist_results' => 'array',
        'defect_photos' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $inspection) {
            if (empty($inspection->checked_at)) {
                $inspection->checked_at = now();
            }
        });
    }

    public const TYPE_CHECK_OUT = 'check_out';

    public const TYPE_CHECK_IN = 'check_in';

    public const SEVERITY_NONE = 'none';

    public const SEVERITY_MINOR = 'minor';

    public const SEVERITY_CRITICAL = 'critical_grounded';

    public static function checklistFields(): array
    {
        return [
            'body' => [
                'label' => __('fleet.inspection.points.body.label'),
                'description' => __('fleet.inspection.points.body.description'),
                'icon' => 'car-front',
            ],
            'tires' => [
                'label' => __('fleet.inspection.points.tires.label'),
                'description' => __('fleet.inspection.points.tires.description'),
                'icon' => 'circle',
            ],
            'interior' => [
                'label' => __('fleet.inspection.points.interior.label'),
                'description' => __('fleet.inspection.points.interior.description'),
                'icon' => 'box-seam',
            ],
            'headlights' => [
                'label' => __('fleet.inspection.points.headlights.label'),
                'description' => __('fleet.inspection.points.headlights.description'),
                'icon' => 'brightness-high',
            ],
            'brake_lights' => [
                'label' => __('fleet.inspection.points.brake_lights.label'),
                'description' => __('fleet.inspection.points.brake_lights.description'),
                'icon' => 'shield-exclamation',
            ],
            'turn_signals' => [
                'label' => __('fleet.inspection.points.turn_signals.label'),
                'description' => __('fleet.inspection.points.turn_signals.description'),
                'icon' => 'arrow-left-right',
            ],
        ];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function parentCheckOut()
    {
        return $this->belongsTo(self::class, 'parent_inspection_id');
    }

    public function checkInRecord()
    {
        return $this->hasOne(self::class, 'parent_inspection_id');
    }

    public function getTripDistanceAttribute(): ?int
    {
        if ($this->inspection_type === self::TYPE_CHECK_IN && $this->parentCheckOut) {
            return max(0, $this->odometer - $this->parentCheckOut->odometer);
        }

        return null;
    }

    public function getSeverityLabelAttribute(): string
    {
        return match ($this->severity) {
            self::SEVERITY_MINOR => __('fleet.inspection.severity_minor'),
            self::SEVERITY_CRITICAL => __('fleet.inspection.severity_critical'),
            default => __('fleet.inspection.severity_none'),
        };
    }

    public function getSeverityBadgeClassesAttribute(): string
    {
        return match ($this->severity) {
            self::SEVERITY_MINOR => 'bg-amber-100 text-amber-800 ring-1 ring-amber-200',
            self::SEVERITY_CRITICAL => 'bg-rose-100 text-rose-800 ring-1 ring-rose-200',
            default => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200',
        };
    }

    public function getCheckedAtAttribute($value)
    {
        return $value ? $this->asDateTime($value) : $this->created_at;
    }
}
