<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vehicle_id',
    'driver_id',
    'route_id',
    'assigned_from',
    'assigned_to',
    'status',
])]
#[Table('vehicle_assignments')]
class VehicleAssignment extends Model
{
    use HasFactory;

    public const STATUSES = [
        'Active' => 'Active',
        'Completed' => 'Completed',
    ];

    protected function casts(): array
    {
        return [
            'vehicle_id' => 'integer',
            'driver_id' => 'integer',
            'route_id' => 'integer',
            'assigned_from' => 'datetime',
            'assigned_to' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
