<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('locations')]
#[Fillable(['state_id', 'district_id', 'code', 'name', 'pincode', 'is_active', 'is_default'])]
class Location extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'state_id' => 'integer',
            'district_id' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function oemAddresses(): HasMany
    {
        return $this->hasMany(OemAddress::class, 'city_id');
    }
}
