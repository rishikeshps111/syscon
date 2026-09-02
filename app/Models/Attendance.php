<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('attendances')]
#[Fillable([
    'attendance_date',
    'month',
    'year',
    'user_type',
    'user_id',
    'status',
    'half_day_period',
    'duty_type',
    'shift',
    'leave_id',
    'remarks',
    'created_by',
    'updated_by',
])]
class Attendance extends Model
{
    use HasFactory;

    public const STATUSES = [
        'present' => 'Present',
        'absent' => 'Absent',
        'half_day' => 'Half Day',
        'week_off' => 'Week Off',
    ];

    public const SHIFTS = [
        'Morning' => 'Morning',
        'Evening' => 'Evening',
        'Night' => 'Night',
    ];

    public const ROLES = [
        'Supervisor' => 'Supervisor',
        'Controller' => 'Controller',
        'Staff' => 'Staff',
        'Driver' => 'Driver',
        'Housekeeping' => 'Housekeeping',
    ];

    public const HALF_DAY_PERIODS = [
        'morning' => 'Morning',
        'afternoon' => 'Afternoon',
    ];

    public const DUTY_TYPES = [
        'D' => 'D',
        'DD' => 'DD',
        'DDD' => 'DDD',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'month' => 'integer',
            'year' => 'integer',
            'user_id' => 'integer',
            'leave_id' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leave(): BelongsTo
    {
        return $this->belongsTo(Leave::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
