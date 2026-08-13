<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'leave_name',
    'short_name',
    'leave_category',
    'max_leaves_per_year',
    'carry_forward_allowed',
    'max_carry_forward_limit',
    'encashment_allowed',
    'applicable_for',
    'gender_specific',
    'minimum_service_required',
    'minimum_leave_days',
    'maximum_leave_days_per_request',
    'advance_notice_days',
    'allow_half_day',
    'requires_approval',
    'is_active',
    'description',
    'remarks',
])]
#[Table('leave_types')]
class LeaveType extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'Paid Leave',
        'Unpaid Leave',
    ];

    public const APPLICABLE_FOR = [
        'all_employees' => 'All Employees',
        'drivers' => 'Drivers',
        'staff' => 'Staff',
        'housekeeping' => 'Housekeeping',
        'controllers' => 'Controllers',
        'supervisors' => 'Supervisors',
    ];

    public const GENDERS = [
        'all' => 'All',
        'male' => 'Male',
        'female' => 'Female',
    ];

    protected function casts(): array
    {
        return [
            'max_leaves_per_year' => 'decimal:2',
            'carry_forward_allowed' => 'boolean',
            'max_carry_forward_limit' => 'decimal:2',
            'encashment_allowed' => 'boolean',
            'minimum_leave_days' => 'decimal:2',
            'maximum_leave_days_per_request' => 'decimal:2',
            'advance_notice_days' => 'integer',
            'allow_half_day' => 'boolean',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
