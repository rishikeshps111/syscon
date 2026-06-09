<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'trip_sheet_id',
    'side',
    'departure_time',
    'arrival_time',
    'actual_start_time',
    'actual_reach_time',
    'driver_profile_id',
    'vehicle_id',
    'starting_km',
    'starting_electric_charge',
    'vehicle_condition',
    'is_vehicle_verified',
    'vehicle_verified_by',
    'vehicle_verified_at',
    'is_driver_verified',
    'driver_verified_by',
    'driver_verified_at',
    'is_verified_by_supervisor',
    'verified_by_supervisor',
    'verified_by_supervisor_at',
    'is_verified_by_controller',
    'verified_by_controller',
    'verified_by_controller_at',
    'notes',
])]
#[Table('trip_sheet_entries')]
class TripSheetEntry extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_vehicle_verified' => 'boolean',
            'vehicle_verified_at' => 'datetime',
            'is_driver_verified' => 'boolean',
            'driver_verified_at' => 'datetime',
            'is_verified_by_supervisor' => 'boolean',
            'verified_by_supervisor_at' => 'datetime',
            'is_verified_by_controller' => 'boolean',
            'verified_by_controller_at' => 'datetime',
        ];
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(TripSheet::class, 'trip_sheet_id');
    }

    public function driverProfile(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function dor(): HasOne
    {
        return $this->hasOne(TripSheetEntryDor::class);
    }

    public function getRosterAttribute(): ?Roster
    {
        if ($this->relationLoaded('rosters')) {
            return $this->rosters->sortByDesc('id')->first();
        }

        return $this->rosters()->latest('rosters.id')->first();
    }

    public function rosters(): BelongsToMany
    {
        return $this->belongsToMany(Roster::class, 'roster_trip_sheet_entries')
            ->withTimestamps();
    }
}
