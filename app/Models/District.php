<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('districts')]
#[Fillable(['state_id', 'code', 'name', 'is_active', 'is_default'])]
class District extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'state_id' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}
