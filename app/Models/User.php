<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'code', 'ref_code', 'phone', 'country_code', 'avatar', 'is_active', 'failed_login_attempts'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'failed_login_attempts' => 'integer',
        ];
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar && file_exists(public_path('storage/' . $this->avatar))) {
            return asset('storage/' . $this->avatar);
        }
        return asset('assets/img/user.png');
    }

    public function getFullPhoneAttribute()
    {
        return trim(($this->country_code ?? '') . ' ' . ($this->phone ?? ''));
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class);
    }

    public function driverProfile(): HasOne
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function housekeepingProfile(): HasOne
    {
        return $this->hasOne(HousekeepingProfile::class);
    }

    public function controllerProfile(): HasOne
    {
        return $this->hasOne(ControllerProfile::class);
    }

    public function supervisorProfile(): HasOne
    {
        return $this->hasOne(SupervisorProfile::class);
    }

    public function staffDocuments(): HasMany
    {
        return $this->hasMany(StaffDocument::class);
    }

    public function userLogs(): HasMany
    {
        return $this->hasMany(UserLog::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(UserDeviceToken::class);
    }

    public function todayTripNotificationLogs(): HasMany
    {
        return $this->hasMany(TodayTripNotificationLog::class);
    }

    public function salaryComponentValues(): HasMany
    {
        return $this->hasMany(UserSalaryComponentValue::class);
    }

    public function controllerDocuments(): HasMany
    {
        return $this->hasMany(ControllerDocument::class);
    }

    public function supervisorDocuments(): HasMany
    {
        return $this->hasMany(SupervisorDocument::class);
    }

    public function driverDocuments(): HasMany
    {
        return $this->hasMany(DriverDocument::class);
    }

    public function housekeepingDocuments(): HasMany
    {
        return $this->hasMany(HousekeepingDocument::class);
    }

    public function reportedComplaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'reported_by_user_id');
    }

    public function complaintsAgainst(): HasMany
    {
        return $this->hasMany(Complaint::class, 'against_user_id');
    }

    public function verifiedOems(): HasMany
    {
        return $this->hasMany(Oem::class, 'verified_by');
    }

    public function createdOems(): HasMany
    {
        return $this->hasMany(Oem::class, 'created_by');
    }

    public function updatedOems(): HasMany
    {
        return $this->hasMany(Oem::class, 'updated_by');
    }
}
