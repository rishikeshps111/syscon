<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vehicle_id',
    'fuel_type',
    'quantity',
    'cost',
    'odometer_reading',
    'date',
])]
#[Table('vehicle_fuel_logs')]
class VehicleFuelLog extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'vehicle_id' => 'integer',
            'quantity' => 'decimal:2',
            'cost' => 'decimal:2',
            'odometer_reading' => 'integer',
            'date' => 'date',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
