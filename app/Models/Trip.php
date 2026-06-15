<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'service_type_id',
    'route_id',
    'depot_id',
    'state_id',
    'code',
    'title',
    'schedule_type',
    'start_time',
    'end_time',
    'halt_time',
    'trip_side',
    'schedule_km',
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

    public const TRIP_SIDES = [
        'up' => 'Up',
        'down' => 'Down',
        'both' => 'Both',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'from_date' => 'date',
            'to_date' => 'date',
            'schedule_km' => 'decimal:2',
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

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TripAssignment::class);
    }

    public function latestAssignment()
    {
        return $this->hasOne(TripAssignment::class)->latestOfMany();
    }

    public function sheetEntries(): HasManyThrough
    {
        return $this->hasManyThrough(TripSheetEntry::class, TripSheet::class);
    }

    public function sheets(): HasMany
    {
        return $this->hasMany(TripSheet::class);
    }

    public function getTripTitleAttribute(): string
    {
        return $this->title ?: ($this->route?->route_name ?: '');
    }
}
