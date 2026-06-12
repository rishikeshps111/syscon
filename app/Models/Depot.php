<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['state_id', 'district_id', 'location_id', 'code', 'name', 'short_name', 'is_active'])]
#[Table('depots')]
class Depot extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'state_id' => 'integer',
            'district_id' => 'integer',
            'location_id' => 'integer',
            'is_active' => 'boolean',
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

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function branchLocations(): BelongsToMany
    {
        return $this->belongsToMany(BranchLocation::class, 'depot_branch_location')
            ->withTimestamps();
    }
}
