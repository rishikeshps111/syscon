<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('leaves')]
#[Fillable([
    'code',
    'leave_for',
    'user_id',
    'leave_type_id',
    'driver_leave_type',
    'from_date',
    'to_date',
    'leave_date',
    'number_of_days',
    'shift',
    'assigned_vehicle_route',
    'attachment_path',
    'reason',
    'remarks',
    'status',
    'created_by',
    'updated_by',
])]
class Leave extends Model
{
    use HasFactory;

    public const TYPES = [
        'general' => 'General Leave System',
        'driver' => 'Shift-Based Leave System',
    ];

    public const DRIVER_LEAVE_TYPES = [
        'Planned Leave' => 'Planned Leave',
        'Emergency Leave' => 'Emergency Leave',
        'No Show' => 'No Show',
        'Half Shift Leave' => 'Half Shift Leave',
    ];

    public const SHIFTS = [
        'Morning' => 'Morning',
        'Evening' => 'Evening',
        'Night' => 'Night',
    ];

    public const STATUSES = [
        'Pending' => 'Pending',
        'Approved' => 'Approved',
        'Rejected' => 'Rejected',
        'Cancelled' => 'Cancelled',
        'Auto Marked' => 'Auto Marked',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'leave_type_id' => 'integer',
            'from_date' => 'date',
            'to_date' => 'date',
            'leave_date' => 'date',
            'number_of_days' => 'decimal:2',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
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
