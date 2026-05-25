<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'state_id',
    'district_id',
    'route_code',
    'route_name',
    'code',
    'name',
    'start_point_id',
    'end_point_id',
    'total_distance_km',
    'distance',
    'estimated_duration',
    'route_type',
    'route_category',
    'status',
    'is_active',
    'remarks',
    'created_by',
    'updated_by',
])]
#[Table('routes')]
class Route extends Model
{
    use HasFactory;

    public const ROUTE_TYPES = [
        'Intercity' => 'Intercity',
        'Intracity' => 'Intracity',
    ];

    public const ROUTE_CATEGORIES = [
        'Passenger' => 'Passenger',
        'Cargo' => 'Cargo',
    ];

    public const STATUSES = [
        'Active' => 'Active',
        'Inactive' => 'Inactive',
    ];

    protected function casts(): array
    {
        return [
            'state_id' => 'integer',
            'district_id' => 'integer',
            'start_point_id' => 'integer',
            'end_point_id' => 'integer',
            'total_distance_km' => 'decimal:2',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Route $route) {
            if (! $route->district_id && $route->start_point_id) {
                $route->district_id = Location::find($route->start_point_id)?->district_id;
            }

            if (! $route->route_category) {
                $route->route_category = 'Passenger';
            }

            if (! $route->status) {
                $route->status = 'Active';
            }
        });
    }

    public function getCodeAttribute(): ?string
    {
        return $this->route_code;
    }

    public function setCodeAttribute(?string $value): void
    {
        $this->attributes['route_code'] = $value;
    }

    public function getNameAttribute(): ?string
    {
        return $this->route_name;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['route_name'] = $value;
    }

    public function getDistanceAttribute(): mixed
    {
        if ($this->total_distance_km === null) {
            return null;
        }

        $distance = (float) $this->total_distance_km;

        return floor($distance) === $distance ? (int) $distance : $distance;
    }

    public function setDistanceAttribute($value): void
    {
        $this->attributes['total_distance_km'] = $value;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'Active';
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['status'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Active' : 'Inactive';
    }

    public function setRouteTypeAttribute(?string $value): void
    {
        $this->attributes['route_type'] = strtolower((string) $value) === 'intercity' ? 'Intercity' : $value;
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function startPoint(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'start_point_id');
    }

    public function endPoint(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'end_point_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    public function routeAssignments(): HasMany
    {
        return $this->hasMany(RouteAssignment::class);
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(RouteAssignment::class)->where('status', 'Active')->latestOfMany();
    }

    public function activeRouteAssignments(): HasMany
    {
        return $this->hasMany(RouteAssignment::class)->where('status', 'Active');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(RouteSchedule::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
