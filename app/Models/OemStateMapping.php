<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('oem_state_mappings')]
#[Fillable([
    'oem_id',
    'state_id',
    'gst_number',
    'is_primary',
    'status',
])]
class OemStateMapping extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'oem_id' => 'integer',
            'state_id' => 'integer',
            'is_primary' => 'boolean',
            'status' => 'boolean',
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
}
