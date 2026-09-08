<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'code',
    'status',
    'cancellation_reason',
    'cancelled_by',
    'cancelled_at',
    'trip_sheet_id',
    'side',
    'departure_time',
    'arrival_time',
    'actual_start_time',
    'actual_reach_time',
    'driver_profile_id',
    'vehicle_id',
    'trip_order_sequence_no',
    'service_code',
    'round_no',
    'trip_nature',
    'schedule_km',
    'starting_km',
    'ending_km',
    'starting_electric_charge',
    'ending_electric_charge',
    'vehicle_condition',
    'energy_status',
    'accident_status',
    'accident_remarks',
    'vehicle_breakdown',
    'medical_emergency',
    'passenger_issue',
    'security_threat',
    'is_vehicle_verified',
    'vehicle_verified_by',
    'vehicle_verified_at',
    'is_driver_verified',
    'driver_verified_by',
    'driver_verified_at',
    'is_initial_verified',
    'initial_verification_by',
    'initial_verification_at',
    'is_final_verified',
    'final_verification_by',
    'final_verification_at',
    'notes',
])]
#[Table('trip_sheet_entries')]
class TripSheetEntry extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'cancelled_by' => 'integer',
            'cancelled_at' => 'datetime',
            'energy_status' => 'boolean',
            'accident_status' => 'boolean',
            'vehicle_breakdown' => 'boolean',
            'medical_emergency' => 'boolean',
            'passenger_issue' => 'boolean',
            'security_threat' => 'boolean',
            'is_vehicle_verified' => 'boolean',
            'vehicle_verified_at' => 'datetime',
            'is_driver_verified' => 'boolean',
            'driver_verified_at' => 'datetime',
            'is_initial_verified' => 'boolean',
            'initial_verification_at' => 'datetime',
            'is_final_verified' => 'boolean',
            'final_verification_at' => 'datetime',
            'trip_order_sequence_no' => 'integer',
            'round_no' => 'integer',
            'schedule_km' => 'decimal:2',
        ];
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(TripSheet::class, 'trip_sheet_id');
    }

    protected static function booted(): void
    {
        static::updating(function (TripSheetEntry $entry): void {
            if ($entry->getOriginal('status') === 'cancelled' && $entry->isDirty([
                'status', 'driver_profile_id', 'vehicle_id', 'is_driver_verified', 'is_vehicle_verified',
                'is_initial_verified', 'is_final_verified', 'actual_start_time', 'actual_reach_time',
                'driver_verified_by', 'driver_verified_at', 'vehicle_verified_by', 'vehicle_verified_at',
                'initial_verification_by', 'initial_verification_at', 'final_verification_by', 'final_verification_at',
            ])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'trip_id' => 'A cancelled trip cannot be reopened, verified or reassigned.',
                ]);
            }
        });
    }

    public function driverProfile(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function dor(): HasOne
    {
        return $this->hasOne(TripSheetEntryDor::class);
    }

    public function scheduleStopTimes(): HasMany
    {
        return $this->hasMany(TripSheetEntryStopTime::class)->orderBy('sequence_no');
    }

    public function getRosterAttribute(): ?Roster
    {
        if ($this->relationLoaded('rosters')) {
            return $this->rosters->sortByDesc('id')->first();
        }

        return $this->rosters()->latest('rosters.id')->first();
    }

    public function rosters(): BelongsToMany
    {
        return $this->belongsToMany(Roster::class, 'roster_trip_sheet_entries')
            ->withTimestamps();
    }

    public function scopeForDepot(Builder $query, int $depotId): Builder
    {
        return $query->whereHas('sheet.trip', function (Builder $tripQuery) use ($depotId): void {
            $tripQuery->where(function (Builder $depotQuery) use ($depotId): void {
                $depotQuery->where('depot_id', $depotId)
                    ->orWhere('from_depot_id', $depotId)
                    ->orWhere('to_depot_id', $depotId);
            });
        });
    }

    public function driverVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_verified_by');
    }

    public function scopeForVehicleCode(Builder $query, string $vehicleCode): Builder
    {
        // An explicit replacement wins over roster and recurring assignment defaults.
        return $query->where(function (Builder $query) use ($vehicleCode): void {
            $matches = fn (Builder $vehicle) => $vehicle->where('vehicle_code', $vehicleCode);
            $query->whereHas('vehicle', $matches)
                ->orWhere(function (Builder $fallback) use ($matches): void {
                    $fallback->whereNull('vehicle_id')->where(function (Builder $query) use ($matches): void {
                        $query->whereHas('rosters.vehicle', $matches)
                            ->orWhere(function (Builder $query) use ($matches): void {
                                $query->whereDoesntHave('rosters', fn (Builder $roster) => $roster->whereNotNull('vehicle_id'))
                                    ->whereHas('sheet', fn (Builder $sheet) => $sheet->whereHas('trip.assignments',
                                        fn (Builder $assignment) => $assignment
                                            ->whereColumn('from_date', '<=', 'trip_sheets.date')
                                            ->whereColumn('to_date', '>=', 'trip_sheets.date')
                                            ->whereHas('vehicle', $matches)));
                            });
                    });
                });
        });
    }

}
