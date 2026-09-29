<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Enums\VehicleStatus;
use App\Models\ServiceRecord;
use App\Models\VehicleDocument;
use App\Models\VehicleInspection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

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
        'odometer',
        'status',
        'sold_at',
    ];

    protected $appends = ['display_name'];

    protected $casts = [
        'status' => VehicleStatus::class,
        'year' => 'integer',
        'odometer' => 'integer',
        'requires_kir' => 'boolean',
        'sold_at' => 'date',
    ];

    public function getIsSoldAttribute(): bool
    {
        return (bool) $this->sold_at || $this->status === 'sold';
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
        return $this->hasMany(VehicleInspection::class)->orderByDesc('created_at');
    }

    public function activeCheckOut()
    {
        return $this->hasOne(VehicleInspection::class)
            ->where('inspection_type', VehicleInspection::TYPE_CHECK_OUT)
            ->whereDoesntHave('checkInRecord')
            ->latestOfMany();
    }

    public function getIsOutOnTripAttribute(): bool
    {
        return $this->activeCheckOut !== null;
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'commercial_truck' => 'Truk / Mobil Gede',
            'pickup' => 'Pick-up / Bak',
            default => 'Mobil Penumpang / Kecil',
        };
    }

    public function getFuelTypeLabelAttribute(): string
    {
        return match ($this->fuel_type) {
            'diesel' => 'Solar / Diesel',
            'ev' => 'Listrik (EV)',
            default => 'Bensin',
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
