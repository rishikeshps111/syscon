<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vehicle_id',
    'maintenance_type',
    'description',
    'cost',
    'vendor_name',
    'service_date',
    'next_service_due',
    'status',
])]
#[Table('vehicle_maintenance_logs')]
class VehicleMaintenanceLog extends Model
{
    use HasFactory;

    public const TYPES = [
        'Service' => 'Service',
        'Repair' => 'Repair',
        'Breakdown' => 'Breakdown',
    ];

    public const STATUSES = [
        'Open' => 'Open',
        'Closed' => 'Closed',
    ];

    protected function casts(): array
    {
        return [
            'vehicle_id' => 'integer',
            'cost' => 'decimal:2',
            'service_date' => 'date',
            'next_service_due' => 'date',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
