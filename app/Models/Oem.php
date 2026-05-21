<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('oems')]
#[Fillable([
    'state_id',
    'oem_code',
    'oem_name',
    'short_name',
    'oem_type',
    'registration_type',
    'gst_number',
    'pan_number',
    'cin_number',
    'status',
    'is_verified',
    'verified_by',
    'verified_at',
    'created_by',
    'updated_by',
    'remarks',
])]
class Oem extends Model
{
    use HasFactory;

    public const OEM_TYPES = [
        'Manufacturer' => 'Manufacturer',
        'Service Provider' => 'Service Provider',
        'Dealer' => 'Dealer',
    ];

    public const REGISTRATION_TYPES = [
        'Company' => 'Company',
        'Partnership' => 'Partnership',
        'Proprietor' => 'Proprietor',
    ];

    public const STATUSES = [
        'Active' => 'Active',
        'Inactive' => 'Inactive',
        'Blocked' => 'Blocked',
    ];

    protected function casts(): array
    {
        return [
            'state_id' => 'integer',
            'is_verified' => 'boolean',
            'verified_by' => 'integer',
            'verified_at' => 'datetime',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(OemContact::class);
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(OemContact::class)->where('is_primary', true);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(OemAddress::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OemDocument::class);
    }

    public function bankDetails(): HasMany
    {
        return $this->hasMany(OemBankDetail::class);
    }

    public function primaryBankDetail(): HasOne
    {
        return $this->hasOne(OemBankDetail::class)->where('is_primary', true);
    }

    public function stateMappings(): HasMany
    {
        return $this->hasMany(OemStateMapping::class);
    }

    public function primaryStateMapping(): HasOne
    {
        return $this->hasOne(OemStateMapping::class)->where('is_primary', true);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->short_name ?: $this->oem_name;
    }
}
