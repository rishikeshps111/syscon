<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('branch_locations')]
#[Fillable(['state_id', 'district_id', 'location_id', 'code', 'name', 'status', 'remarks'])]
class BranchLocation extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'state_id' => 'integer',
            'district_id' => 'integer',
            'location_id' => 'integer',
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

    public function depots(): BelongsToMany
    {
        return $this->belongsToMany(Depot::class, 'depot_branch_location')
            ->withTimestamps();
    }
}
