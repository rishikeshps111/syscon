<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'trip_id',
    'trip_date',
    'departure_time',
    'arrival_time',
    'actual_start_time',
    'actual_reach_time',
    'verified_by',
    'approved_by',
    'shift',
    'driver_profile_id',
    'vehicle_id',
    'notes',
])]
#[Table('trip_sheet_entries')]
class TripSheetEntry extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'trip_date' => 'date',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function driverProfile(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
