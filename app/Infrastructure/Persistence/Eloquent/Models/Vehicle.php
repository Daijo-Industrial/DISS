<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Enums\VehicleStatus;
use App\Models\ServiceRecord;
use App\Models\VehicleDocument;
use App\Models\VehicleInspection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'plate_number',
        'driver_name',
        'brand',
        'model',
        'category',
        'fuel_type',
        'requires_kir',
        'year',
        'vin',
        'image_path',
        'odometer',
        'status',
        'sold_at',
    ];

    protected $appends = ['display_name', 'image_url'];

    protected $casts = [
        'status' => VehicleStatus::class,
        'year' => 'integer',
        'odometer' => 'integer',
        'requires_kir' => 'boolean',
        'sold_at' => 'date',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function getIsSoldAttribute(): bool
    {
        return (bool) $this->sold_at || $this->status === VehicleStatus::SOLD || $this->status === 'sold';
    }

    public function setPlateNumberAttribute($value)
    {
        $this->attributes['plate_number'] = strtoupper(trim($value));
    }

    public function setVinAttribute($value)
    {
        $this->attributes['vin'] = $value ? strtoupper(trim($value)) : null;
    }

    public function setBrandAttribute($value)
    {
        $this->attributes['brand'] = $value ? trim($value) : null;
    }

    public function setModelAttribute($value)
    {
        $this->attributes['model'] = $value ? trim($value) : null;
    }

    public function serviceRecords()
    {
        return $this->hasMany(ServiceRecord::class)->orderByDesc('service_date');
    }

    public function latestService()
    {
        return $this->hasOne(ServiceRecord::class)->latestOfMany('service_date');
    }

    public function documents()
    {
        return $this->hasMany(VehicleDocument::class)->orderBy('expired_date');
    }

    public function inspections()
    {
        return $this->hasMany(VehicleInspection::class)->orderByDesc('checked_at')->orderByDesc('id');
    }

    public function activeCheckOut(): HasOne
    {
        return $this->hasOne(VehicleInspection::class)
            ->where('inspection_type', VehicleInspection::TYPE_CHECK_OUT)
            ->whereDoesntHave('checkInRecord')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)
                    ->from('vehicle_inspections as newer_ins')
                    ->whereColumn('newer_ins.vehicle_id', 'vehicle_inspections.vehicle_id')
                    ->where(function ($nq) {
                        $nq->whereColumn('newer_ins.checked_at', '>', 'vehicle_inspections.checked_at')
                            ->orWhere(function ($sub) {
                                $sub->whereColumn('newer_ins.checked_at', '=', 'vehicle_inspections.checked_at')
                                    ->whereColumn('newer_ins.id', '>', 'vehicle_inspections.id');
                            });
                    });
            });
    }

    public function getIsOutOnTripAttribute(): bool
    {
        return $this->activeCheckOut !== null;
    }

    public function getIsNewlyAddedAttribute(): bool
    {
        // 1. Must be registered within the last 7 days
        if (! $this->created_at || $this->created_at->lt(now()->subDays(7))) {
            return false;
        }

        // 2. Exclude sold or retired vehicles
        if ($this->is_sold || in_array($this->status, [VehicleStatus::RETIRED, 'retired'], true)) {
            return false;
        }

        // 3. If withExists was loaded via query
        if (isset($this->attributes['has_completed_p2h'])) {
            return ! (bool) $this->attributes['has_completed_p2h'];
        }

        // 4. If inspections relation was already loaded
        if ($this->relationLoaded('inspections')) {
            return ! $this->inspections->contains(fn ($i) => $i->inspection_type === VehicleInspection::TYPE_CHECK_IN);
        }

        // 5. Fallback query: check if any completed check-in inspection exists
        return ! $this->inspections()->where('inspection_type', VehicleInspection::TYPE_CHECK_IN)->exists();
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'commercial_truck' => __('fleet.categories.commercial_truck'),
            'pickup' => __('fleet.categories.pickup'),
            'other' => __('fleet.categories.other'),
            default => __('fleet.categories.passenger'),
        };
    }

    public function getFuelTypeLabelAttribute(): string
    {
        return match ($this->fuel_type) {
            'diesel' => __('fleet.fuel_types.diesel'),
            'ev' => __('fleet.fuel_types.ev'),
            default => __('fleet.fuel_types.petrol'),
        };
    }

    public function getDisplayNameAttribute(): string
    {   // e.g. "B 1234 XYZ — Avanza (2019)"
        $name = trim($this->brand . ' ' . $this->model);

        return sprintf('%s — %s (%s)', $this->plate_number, $name ?: 'Vehicle', $this->year ?: 'N/A');
    }

    public function getRegionNameAttribute(): ?string
    {
        if (! $this->plate_number) {
            return null;
        }

        $code = strtoupper(explode(' ', trim($this->plate_number))[0] ?? '');

        return config("fleet.plate_regions.{$code}");
    }
}
