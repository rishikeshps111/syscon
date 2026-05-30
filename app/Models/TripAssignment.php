<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['trip_id', 'from_date', 'to_date', 'vehicle_id', 'driver_profile_id', 'notes', 'created_by', 'updated_by'])]
#[Table('trip_assignments')]
class TripAssignment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driverProfile(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class);
    }
}
