<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('route_schedules')]
#[Fillable([
    'route_id',
    'schedule_date',
    'planned_start_time',
    'planned_end_time',
    'vehicle_id',
    'driver_id',
    'status',
])]
class RouteSchedule extends Model
{
    use HasFactory;

    public const STATUSES = [
        'Planned' => 'Planned',
        'Running' => 'Running',
        'Completed' => 'Completed',
        'Cancelled' => 'Cancelled',
    ];

    protected function casts(): array
    {
        return [
            'route_id' => 'integer',
            'vehicle_id' => 'integer',
            'driver_id' => 'integer',
            'schedule_date' => 'date',
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
