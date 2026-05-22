<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('vehicles')]
#[Fillable([
    'state_id',
    'oem_id',
    'depot_id',
    'branch_id',
    'vehicle_code',
    'vehicle_no',
    'vehicle_type',
    'fuel_type',
    'vehicle_category',
    'make',
    'model',
    'variant',
    'capacity_seating',
    'capacity_load',
    'battery_capacity',
    'range_km',
    'engine_no',
    'chassis_no',
    'registration_date',
    'registration_valid_upto',
    'fitness_expiry',
    'permit_expiry',
    'insurance_expiry',
    'pollution_expiry',
    'gps_enabled',
    'gps_imei',
    'status',
    'is_verified',
    'created_by',
    'updated_by',
    'remarks',
])]
class Vehicle extends Model
{
    use HasFactory;

    public const TYPES = [
        'BUS' => 'Bus',
        'CAR' => 'Car',
        'VAN' => 'Van',
        'TRUCK' => 'Truck',
        'AUTO' => 'Auto',
    ];

    public const FUEL_TYPES = [
        'ELECTRIC' => 'Electric',
        'DIESEL' => 'Diesel',
        'PETROL' => 'Petrol',
        'CNG' => 'CNG',
        'HYBRID' => 'Hybrid',
    ];

    public const CATEGORIES = [
        'Passenger' => 'Passenger',
        'Cargo' => 'Cargo',
    ];

    public const STATUSES = [
        'Active' => 'Active',
        'Inactive' => 'Inactive',
        'Under Maintenance' => 'Under Maintenance',
        'Scrap' => 'Scrap',
    ];

    protected function casts(): array
    {
        return [
            'state_id' => 'integer',
            'oem_id' => 'integer',
            'depot_id' => 'integer',
            'branch_id' => 'integer',
            'capacity_seating' => 'integer',
            'capacity_load' => 'decimal:2',
            'battery_capacity' => 'decimal:2',
            'range_km' => 'integer',
            'registration_date' => 'date',
            'registration_valid_upto' => 'date',
            'fitness_expiry' => 'date',
            'permit_expiry' => 'date',
            'insurance_expiry' => 'date',
            'pollution_expiry' => 'date',
            'gps_enabled' => 'boolean',
            'is_verified' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function oem(): BelongsTo
    {
        return $this->belongsTo(Oem::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(BranchLocation::class, 'branch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VehicleDocument::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(VehicleMaintenanceLog::class);
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(VehicleFuelLog::class);
    }
}
