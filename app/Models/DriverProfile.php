<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'alternate_country_code',
    'alternate_phone',
    'aadhaar_number',
    'country',
    'state_id',
    'district_id',
    'location_id',
    'pincode',
    'address',
    'license_number',
    'license_type',
    'issue_date',
    'expiry_date',
    'badge_number',
    'badge_expiry_date',
    'employment_type',
    'joining_date',
    'salary',
    'depot_id',
    'branch_location_id',
    'account_number',
    'ifsc_code',
    'emergency_contact_name',
    'emergency_country_code',
    'emergency_contact_no',
    'medical_fitness_expiry',
    'police_verification_status',
    'verification_status',
])]
#[Table('driver_profiles')]
class DriverProfile extends Model
{
    use HasFactory;

    public const LICENSE_TYPES = [
        'lmv' => 'LMV',
        'hmv' => 'HMV',
        'transport' => 'Transport',
    ];

    public const EMPLOYMENT_TYPES = [
        'permanent' => 'Permanent',
        'contract' => 'Contract',
    ];

    public const VERIFICATION_STATUSES = [
        'pending' => 'Pending',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
    ];

    protected function casts(): array
    {
        return [
            'state_id' => 'integer',
            'district_id' => 'integer',
            'location_id' => 'integer',
            'depot_id' => 'integer',
            'branch_location_id' => 'integer',
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'badge_expiry_date' => 'date',
            'joining_date' => 'date',
            'medical_fitness_expiry' => 'date',
            'salary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function branchLocation(): BelongsTo
    {
        return $this->belongsTo(BranchLocation::class);
    }

    public function scopeExpiredLicense(Builder $query): Builder
    {
        return $query->whereDate('expiry_date', '<', now()->toDateString());
    }

    public static function expiredLicenseCount(): int
    {
        return self::expiredLicense()->count();
    }

    public function getLicenseTypeLabelAttribute(): string
    {
        return self::LICENSE_TYPES[$this->license_type] ?? '';
    }

    public function getEmploymentTypeLabelAttribute(): string
    {
        return self::EMPLOYMENT_TYPES[$this->employment_type] ?? '';
    }

    public function getVerificationStatusLabelAttribute(): string
    {
        return self::VERIFICATION_STATUSES[$this->verification_status] ?? '';
    }

    public function getPoliceVerificationStatusLabelAttribute(): string
    {
        return self::VERIFICATION_STATUSES[$this->police_verification_status] ?? '';
    }
}
