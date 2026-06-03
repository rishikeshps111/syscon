<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'code',
    'state_id',
    'oem_id',
    'depot_id',
    'duty_date',
    'shift_type',
    'shift_start_time',
    'shift_end_time',
    'trip_sheet_entry_id',
    'trip_assignment_id',
    'driver_profile_id',
    'vehicle_id',
    'supervisor_profile_id',
    'controller_profile_id',
    'reporting_time',
    'reporting_to_time',
    'remarks',
    'status',
    'attendance_status',
    'created_by',
    'updated_by',
])]
#[Table('rosters')]
class Roster extends Model
{
    use HasFactory;

    public const PREFIX_MODULE = 'Roaster Module';

    public const SHIFT_TYPES = [
        'morning' => 'Morning',
        'evening' => 'Evening',
        'night' => 'Night',
    ];

    public const STATUSES = [
        'assigned' => 'Assigned',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'missed' => 'Missed',
    ];

    public const ATTENDANCE_STATUSES = [
        'present' => 'Present',
        'absent' => 'Absent',
        'late' => 'Late',
    ];

    protected function casts(): array
    {
        return [
            'duty_date' => 'date',
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

    public function tripSheetEntry(): BelongsTo
    {
        return $this->belongsTo(TripSheetEntry::class);
    }

    public function tripAssignment(): BelongsTo
    {
        return $this->belongsTo(TripAssignment::class);
    }

    public function driverProfile(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function supervisorProfile(): BelongsTo
    {
        return $this->belongsTo(SupervisorProfile::class);
    }

    public function controllerProfile(): BelongsTo
    {
        return $this->belongsTo(ControllerProfile::class);
    }
}
