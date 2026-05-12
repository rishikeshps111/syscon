<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['state_id', 'start_point_id', 'end_point_id', 'code', 'name', 'distance', 'estimated_duration', 'route_type', 'is_active'])]
#[Table('routes')]
class Route extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'distance' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function startPoint(): BelongsTo
    {
        return $this->belongsTo(Depot::class, 'start_point_id');
    }

    public function endPoint(): BelongsTo
    {
        return $this->belongsTo(Depot::class, 'end_point_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class);
    }
}
