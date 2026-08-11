<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'depot_id',
    'reporting_to',
    'employment_type',
    'father_name',
    'date_of_birth',
    'aadhaar_number',
    'pan_number',
    'date_of_joining',
    'uan',
    'esic_wc',
    'country',
    'state_id',
    'district_id',
    'location_id',
    'bank_account_number',
    'ifsc_code',
    'basic',
    'vda',
    'basic_vda',
    'hra',
    'special_allowance',
    'conveyance_allowance',
    'bonus',
    'gross_salary',
])]
#[Table('supervisor_profiles')]
class SupervisorProfile extends Model
{
    use HasFactory;

    public const EMPLOYMENT_TYPES = [
        'full_time' => 'Full-time',
        'part_time' => 'Part-time',
        'contract' => 'Contract',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'date_of_joining' => 'date',
            'depot_id' => 'integer',
            'state_id' => 'integer',
            'district_id' => 'integer',
            'location_id' => 'integer',
            'basic' => 'decimal:2',
            'vda' => 'decimal:2',
            'basic_vda' => 'decimal:2',
            'hra' => 'decimal:2',
            'special_allowance' => 'decimal:2',
            'conveyance_allowance' => 'decimal:2',
            'bonus' => 'decimal:2',
            'gross_salary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function reportingTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporting_to');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function getEmploymentTypeLabelAttribute(): string
    {
        return self::EMPLOYMENT_TYPES[$this->employment_type] ?? '';
    }
}
