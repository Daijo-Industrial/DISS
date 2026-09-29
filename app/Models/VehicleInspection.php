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
        'driver_name',
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
        'odometer' => 'integer',
        'fuel_percentage' => 'integer',
        'checklist_results' => 'array',
        'defect_photos' => 'array',
    ];

    public const TYPE_CHECK_OUT = 'check_out';

    public const TYPE_CHECK_IN = 'check_in';

    public const SEVERITY_NONE = 'none';

    public const SEVERITY_MINOR = 'minor';

    public const SEVERITY_CRITICAL = 'critical_grounded';

    public static function checklistFields(): array
    {
        return [
            'body' => [
                'label' => 'Bodi Eksterior',
                'description' => 'Kondisi fisik luar (kebersihan, baret, penyok)',
                'icon' => 'car-front',
            ],
            'tires' => [
                'label' => 'Kondisi Ban',
                'description' => 'Tekanan angin, keausan tapak ban, ban serep',
                'icon' => 'circle',
            ],
            'interior' => [
                'label' => 'Isi Dalam Mobil',
                'description' => 'Kebersihan kabin, dongkrak, segitiga pengaman, toolkit, APAR',
                'icon' => 'box-seam',
            ],
            'headlights' => [
                'label' => 'Lampu Depan',
                'description' => 'Lampu dekat, lampu jauh (high beam)',
                'icon' => 'brightness-high',
            ],
            'brake_lights' => [
                'label' => 'Lampu Rem',
                'description' => 'Lampu rem belakang berfungsi normal saat pedal diinjak',
                'icon' => 'shield-exclamation',
            ],
            'turn_signals' => [
                'label' => 'Lampu Sein & Hazard',
                'description' => 'Indikator sein kiri, kanan, dan lampu hazard menyala',
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
            self::SEVERITY_MINOR => 'Minor Issue (Boleh Jalan)',
            self::SEVERITY_CRITICAL => 'Kritis / Grounded (Tidak Boleh Jalan)',
            default => 'Aman (Fit to Drive)',
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
}
