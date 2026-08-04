<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'trip_id',
    'code',
    'date',
    'status',
])]
#[Table('trip_sheets')]
class TripSheet extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pending' => 'Pending',
        'initial_verification_completed' => 'Initial Verification Completed',
        'verification_completed' => 'Trip Completed',
        'cancelled' => 'Cancelled',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TripSheetEntry::class);
    }
}
