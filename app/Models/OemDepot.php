<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('oem_depots')]
#[Fillable([
    'oem_id',
    'depot_id',
    'branch_location_id',
    'status',
])]
class OemDepot extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'oem_id' => 'integer',
            'depot_id' => 'integer',
            'branch_location_id' => 'integer',
            'status' => 'boolean',
        ];
    }

    public function oem(): BelongsTo
    {
        return $this->belongsTo(Oem::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function branchLocation(): BelongsTo
    {
        return $this->belongsTo(BranchLocation::class);
    }
}
