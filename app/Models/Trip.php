<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'service_type_id',
    'route_id',
    'depot_id',
    'code',
    'title',
    'schedule_type',
    'start_time',
    'end_time',
    'from_date',
    'to_date',
    'status',
    'notes',
    'cancellation_reason',
    'is_active',
    'created_by',
    'updated_by',
])]
#[Table('trips')]
class Trip extends Model
{
    use HasFactory;

    public const PREFIX_MODULE = 'Trip Module';

    public const SERVICE_TYPES = [
        'Intercity' => 'Intercity',
        'Intracity' => 'Intracity',
    ];

    public const STATUSES = [
        'Active' => 'Active',
        'Inactive' => 'Inactive',
        'Cancelled' => 'Cancelled',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'from_date' => 'date',
            'to_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Trip $trip) {
            if (! $trip->status) {
                $trip->status = $trip->is_active ? 'Active' : 'Inactive';
            }

            $trip->is_active = ! in_array($trip->status, ['Inactive', 'Cancelled'], true);
        });
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TripAssignment::class);
    }

    public function latestAssignment()
    {
        return $this->hasOne(TripAssignment::class)->latestOfMany();
    }

    public function sheetEntries(): HasMany
    {
        return $this->hasMany(TripSheetEntry::class);
    }

    public function getTripTitleAttribute(): string
    {
        return $this->title ?: ($this->route?->route_name ?: '');
    }
}
