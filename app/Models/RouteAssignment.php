<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('route_assignments')]
#[Fillable([
    'route_id',
    'vehicle_id',
    'driver_id',
    'trip_id',
    'shift_type',
    'start_time',
    'end_time',
    'effective_from',
    'effective_to',
    'status',
])]
class RouteAssignment extends Model
{
    use HasFactory;

    public const SHIFT_TYPES = [
        'Morning' => 'Morning',
        'Evening' => 'Evening',
        'Night' => 'Night',
    ];

    public const STATUSES = [
        'Active' => 'Active',
        'Completed' => 'Completed',
    ];

    protected function casts(): array
    {
        return [
            'route_id' => 'integer',
            'vehicle_id' => 'integer',
            'driver_id' => 'integer',
            'trip_id' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
