<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('states')]
#[Fillable(['code', 'name', 'is_active', 'is_default'])]
class State extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function oems(): HasMany
    {
        return $this->hasMany(Oem::class);
    }

    public function oemAddresses(): HasMany
    {
        return $this->hasMany(OemAddress::class);
    }
}
