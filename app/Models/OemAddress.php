<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('oem_addresses')]
#[Fillable([
    'oem_id',
    'state_id',
    'district_id',
    'city_id',
    'address_type',
    'address_line1',
    'address_line2',
    'pincode',
    'latitude',
    'longitude',
])]
class OemAddress extends Model
{
    use HasFactory;

    public const ADDRESS_TYPES = [
        'HQ' => 'HQ',
        'Billing' => 'Billing',
        'Service' => 'Service',
        'Depot' => 'Depot',
    ];

    protected function casts(): array
    {
        return [
            'oem_id' => 'integer',
            'state_id' => 'integer',
            'district_id' => 'integer',
            'city_id' => 'integer',
        ];
    }

    public function oem(): BelongsTo
    {
        return $this->belongsTo(Oem::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'city_id');
    }

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_line1,
            $this->address_line2,
            $this->city?->name,
            $this->district?->name,
            $this->state?->name,
            $this->pincode,
        ])->filter()->implode(', ');
    }
}
