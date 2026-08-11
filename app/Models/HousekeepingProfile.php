<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'reporting_to', 'alternate_country_code', 'alternate_phone', 'father_name', 'date_of_birth', 'aadhaar_number', 'pan_number', 'uan', 'esic_wc', 'country', 'state_id', 'district_id', 'location_id', 'pincode', 'address', 'employment_type', 'joining_date', 'salary', 'depot_id', 'branch_location_id', 'account_number', 'ifsc_code', 'emergency_contact_name', 'emergency_country_code', 'emergency_contact_no', 'medical_fitness_expiry', 'police_verification_status', 'verification_status', 'basic', 'vda', 'basic_vda', 'hra', 'special_allowance', 'conveyance_allowance', 'bonus', 'gross_salary'])]
#[Table('housekeeping_profiles')]
class HousekeepingProfile extends Model
{
    use HasFactory;

    public const EMPLOYMENT_TYPES = ['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract'];
    public const VERIFICATION_STATUSES = ['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected'];

    protected function casts(): array
    {
        return ['reporting_to' => 'integer', 'state_id' => 'integer', 'district_id' => 'integer', 'location_id' => 'integer', 'depot_id' => 'integer', 'branch_location_id' => 'integer', 'date_of_birth' => 'date', 'joining_date' => 'date', 'medical_fitness_expiry' => 'date', 'salary' => 'decimal:2', 'gross_salary' => 'decimal:2'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function state(): BelongsTo { return $this->belongsTo(State::class); }
    public function district(): BelongsTo { return $this->belongsTo(District::class); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
    public function depot(): BelongsTo { return $this->belongsTo(Depot::class); }
    public function reportingTo(): BelongsTo { return $this->belongsTo(User::class, 'reporting_to'); }
    public function branchLocation(): BelongsTo { return $this->belongsTo(BranchLocation::class); }
    public function getEmploymentTypeLabelAttribute(): string { return self::EMPLOYMENT_TYPES[$this->employment_type] ?? ''; }
    public function getVerificationStatusLabelAttribute(): string { return self::VERIFICATION_STATUSES[$this->verification_status] ?? ''; }
    public function getPoliceVerificationStatusLabelAttribute(): string { return self::VERIFICATION_STATUSES[$this->police_verification_status] ?? ''; }
}
