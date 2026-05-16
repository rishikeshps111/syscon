<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'code',
    'holiday_name',
    'holiday_date',
    'holiday_type',
    'applicable_location',
    'state_id',
    'branch_location_id',
    'applicable_for',
    'department_ids',
    'designation_ids',
    'holiday_duration',
    'is_recurring_yearly',
    'is_active',
    'description',
    'remarks',
])]
#[Table('holidays')]
class Holiday extends Model
{
    use HasFactory;

    public const TYPES = [
        'national' => 'National Holiday',
        'state' => 'State Holiday',
        'company' => 'Company Holiday',
    ];

    public const LOCATIONS = [
        'all' => 'All Locations',
        'state' => 'Specific State',
        'branch' => 'Specific Branch',
    ];

    public const APPLICABLE_FOR = [
        'all_employees' => 'All Employees',
        'specific_departments' => 'Specific Departments',
        'specific_designations' => 'Specific Designations',
    ];

    public const DURATIONS = [
        'full_day' => 'Full Day',
        'half_day' => 'Half Day',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'state_id' => 'integer',
            'branch_location_id' => 'integer',
            'department_ids' => 'array',
            'designation_ids' => 'array',
            'is_recurring_yearly' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function branchLocation(): BelongsTo
    {
        return $this->belongsTo(BranchLocation::class);
    }

    public function getHolidayTypeLabelAttribute(): string
    {
        return self::TYPES[$this->holiday_type] ?? $this->holiday_type;
    }

    public function getApplicableLocationLabelAttribute(): string
    {
        return match ($this->applicable_location) {
            'state' => $this->state?->name ?? 'Specific State',
            'branch' => $this->branchLocation?->name ?? 'Specific Branch',
            default => 'All',
        };
    }

    public function getApplicableForLabelAttribute(): string
    {
        return self::APPLICABLE_FOR[$this->applicable_for] ?? $this->applicable_for;
    }

    public function getHolidayDurationLabelAttribute(): string
    {
        return self::DURATIONS[$this->holiday_duration] ?? $this->holiday_duration;
    }
}
