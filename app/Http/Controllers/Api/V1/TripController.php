<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Models\TripSheetEntry;
use App\Models\TripSheetEntryDor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TripController extends Controller
{
    public function index(Request $request)
    {
        $controllerProfileId = $request->user()->controllerProfile?->id;

        if (! $controllerProfileId) {
            return TripResource::collection(collect());
        }

        $query = $this->tripQuery($controllerProfileId);

        $records = QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::callback('date', function (Builder $query, $value): void {
                    $query->whereHas('sheet', fn(Builder $sheetQuery) => $sheetQuery
                        ->whereDate('date', Carbon::parse($value)->toDateString()));
                }),
                AllowedFilter::callback('trip', function (Builder $query, $value): void {
                    $value = trim((string) $value);

                    $query->whereHas('sheet', function (Builder $sheetQuery) use ($value): void {
                        $sheetQuery->where('code', 'like', '%' . $value . '%')
                            ->orWhereHas('trip', function (Builder $tripQuery) use ($value): void {
                                if (is_numeric($value)) {
                                    $tripQuery->where('id', (int) $value);
                                }

                                $tripQuery->orWhere('code', 'like', '%' . $value . '%')
                                    ->orWhere('title', 'like', '%' . $value . '%');
                            });
                    });
                }),
                AllowedFilter::callback('roster_status', function (Builder $query, $value): void {
                    $query->whereHas('rosters', fn(Builder $rosterQuery) => $rosterQuery->where('status', $value));
                }),
                AllowedFilter::callback('search', function (Builder $query, $value): void {
                    $search = trim((string) $value);

                    $query->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery->whereHas('vehicle', fn(Builder $vehicleQuery) => $vehicleQuery
                            ->where('vehicle_no', 'like', '%' . $search . '%')
                            ->orWhere('vehicle_code', 'like', '%' . $search . '%'))
                            ->orWhereHas('driverProfile.user', fn(Builder $userQuery) => $userQuery
                                ->where('name', 'like', '%' . $search . '%')
                                ->orWhere('code', 'like', '%' . $search . '%'));
                    });
                }),
            )
            ->defaultSort('-id')
            ->paginate(10);

        return TripResource::collection($records);
    }

    public function today(Request $request)
    {
        $controllerProfileId = $request->user()->controllerProfile?->id;
        $today = Carbon::today()->toDateString();

        if (! $controllerProfileId) {
            return TripResource::collection(collect())->additional([
                'meta' => [
                    'date' => Carbon::parse($today)->format('d M Y'),
                    'total_count' => 0,
                    'is_verified_by_controller_false_count' => 0,
                ],
            ]);
        }

        $query = $this->tripQuery($controllerProfileId)
            ->whereHas('sheet', fn(Builder $sheetQuery) => $sheetQuery->whereDate('date', $today));

        $vehicleCode = trim((string) ($request->input('vehicle_code') ?? $request->input('vehical_code') ?? ''));

        if ($vehicleCode !== '') {
            $query->where(function (Builder $query) use ($vehicleCode): void {
                $query->whereHas('vehicle', fn(Builder $vehicleQuery) => $vehicleQuery->where('vehicle_code', $vehicleCode))
                    ->orWhereHas('rosters.vehicle', fn(Builder $vehicleQuery) => $vehicleQuery->where('vehicle_code', $vehicleCode))
                    ->orWhereHas('sheet.trip.assignments.vehicle', fn(Builder $vehicleQuery) => $vehicleQuery->where('vehicle_code', $vehicleCode));
            });
        }

        $totalCount = (clone $query)->count();
        $controllerUnverifiedCount = (clone $query)
            ->where(function (Builder $query): void {
                $query->where('is_verified_by_controller', false)
                    ->orWhereNull('is_verified_by_controller');
            })
            ->count();

        $records = $this->applyTodayTripOrder($query)->get();

        return TripResource::collection($records)->additional([
            'meta' => [
                'date' => Carbon::parse($today)->format('d M Y'),
                'total_count' => $totalCount,
                'is_verified_by_controller_false_count' => $controllerUnverifiedCount,
            ],
        ]);
    }

    public function show(Request $request, TripSheetEntry $tripSheetEntry)
    {
        $controllerProfileId = $request->user()->controllerProfile?->id;

        abort_if(! $controllerProfileId, 404);

        $record = $this->tripQuery($controllerProfileId)
            ->with([
                'dor',
                'driverProfile.state',
                'driverProfile.district',
                'driverProfile.location',
                'driverProfile.depot',
                'driverProfile.branchLocation',
                'vehicle.state',
                'vehicle.oem',
                'vehicle.depot',
                'vehicle.branch',
                'sheet.trip.serviceType',
                'sheet.trip.assignments.driverProfile.user',
                'sheet.trip.assignments.vehicle',
            ])
            ->whereKey($tripSheetEntry->getKey())
            ->firstOrFail();

        return (new TripResource($record))->withDetails();
    }

    public function driverIndex(Request $request)
    {
        $driverProfileId = $request->user()->driverProfile?->id;

        if (! $driverProfileId) {
            return TripResource::collection(collect());
        }

        $query = $this->driverTripQuery($driverProfileId);

        $records = QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::callback('date', function (Builder $query, $value): void {
                    $query->whereHas('sheet', fn(Builder $sheetQuery) => $sheetQuery
                        ->whereDate('date', Carbon::parse($value)->toDateString()));
                }),
                AllowedFilter::callback('trip', function (Builder $query, $value): void {
                    $value = trim((string) $value);

                    $query->whereHas('sheet', function (Builder $sheetQuery) use ($value): void {
                        $sheetQuery->where('code', 'like', '%' . $value . '%')
                            ->orWhereHas('trip', function (Builder $tripQuery) use ($value): void {
                                if (is_numeric($value)) {
                                    $tripQuery->where('id', (int) $value);
                                }

                                $tripQuery->orWhere('code', 'like', '%' . $value . '%')
                                    ->orWhere('title', 'like', '%' . $value . '%');
                            });
                    });
                }),
                AllowedFilter::callback('roster_status', function (Builder $query, $value): void {
                    $query->whereHas('rosters', fn(Builder $rosterQuery) => $rosterQuery->where('status', $value));
                }),
                AllowedFilter::callback('search', function (Builder $query, $value): void {
                    $search = trim((string) $value);

                    $query->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery->whereHas('vehicle', fn(Builder $vehicleQuery) => $vehicleQuery
                            ->where('vehicle_no', 'like', '%' . $search . '%')
                            ->orWhere('vehicle_code', 'like', '%' . $search . '%'))
                            ->orWhereHas('driverProfile.user', fn(Builder $userQuery) => $userQuery
                                ->where('name', 'like', '%' . $search . '%')
                                ->orWhere('code', 'like', '%' . $search . '%'));
                    });
                }),
            )
            ->defaultSort('-id')
            ->paginate(10);

        return TripResource::collection($records);
    }

    public function driverToday(Request $request)
    {
        $driverProfileId = $request->user()->driverProfile?->id;
        $today = Carbon::today()->toDateString();

        if (! $driverProfileId) {
            return TripResource::collection(collect())->additional([
                'meta' => [
                    'date' => Carbon::parse($today)->format('d M Y'),
                    'total_count' => 0,
                    'is_verified_by_controller_false_count' => 0,
                ],
            ]);
        }

        $query = $this->driverTripQuery($driverProfileId)
            ->whereHas('sheet', fn(Builder $sheetQuery) => $sheetQuery->whereDate('date', $today));

        $vehicleCode = trim((string) ($request->input('vehicle_code') ?? $request->input('vehical_code') ?? ''));

        if ($vehicleCode !== '') {
            $query->where(function (Builder $query) use ($vehicleCode): void {
                $query->whereHas('vehicle', fn(Builder $vehicleQuery) => $vehicleQuery->where('vehicle_code', $vehicleCode))
                    ->orWhereHas('rosters.vehicle', fn(Builder $vehicleQuery) => $vehicleQuery->where('vehicle_code', $vehicleCode))
                    ->orWhereHas('sheet.trip.assignments.vehicle', fn(Builder $vehicleQuery) => $vehicleQuery->where('vehicle_code', $vehicleCode));
            });
        }

        $totalCount = (clone $query)->count();
        $controllerUnverifiedCount = (clone $query)
            ->where(function (Builder $query): void {
                $query->where('is_verified_by_controller', false)
                    ->orWhereNull('is_verified_by_controller');
            })
            ->count();

        $records = $this->applyTodayTripOrder($query)->get();

        return TripResource::collection($records)->additional([
            'meta' => [
                'date' => Carbon::parse($today)->format('d M Y'),
                'total_count' => $totalCount,
                'is_verified_by_controller_false_count' => $controllerUnverifiedCount,
            ],
        ]);
    }

    public function driverShow(Request $request, TripSheetEntry $tripSheetEntry)
    {
        $driverProfileId = $request->user()->driverProfile?->id;

        abort_if(! $driverProfileId, 404);

        $record = $this->driverTripQuery($driverProfileId)
            ->with([
                'dor',
                'driverProfile.state',
                'driverProfile.district',
                'driverProfile.location',
                'driverProfile.depot',
                'driverProfile.branchLocation',
                'vehicle.state',
                'vehicle.oem',
                'vehicle.depot',
                'vehicle.branch',
                'sheet.trip.serviceType',
                'sheet.trip.assignments.driverProfile.user',
                'sheet.trip.assignments.vehicle',
            ])
            ->whereKey($tripSheetEntry->getKey())
            ->firstOrFail();

        return (new TripResource($record))->withDetails();
    }

    public function verifyDriver(Request $request)
    {
        $validated = $request->validate([
            'trip_id' => ['required', 'integer'],
            'driver_code' => ['required', 'string', 'max:255'],
        ]);

        $controllerProfileId = $request->user()->controllerProfile?->id;

        abort_if(! $controllerProfileId, 404);

        $driver = User::role('Driver')
            ->with('driverProfile')
            ->where('code', trim($validated['driver_code']))
            ->first();

        if (! $driver?->driverProfile) {
            $this->invalidDriverQr();
        }

        $record = $this->tripQuery($controllerProfileId)
            ->with([
                'driverVerifiedBy',
                'sheet.trip.assignments.driverProfile.user',
                'sheet.trip.assignments.vehicle',
            ])
            ->whereKey($validated['trip_id'])
            ->firstOrFail();

        if (! $this->driverBelongsToTrip($record, (int) $driver->driverProfile->id)) {
            $this->invalidDriverQr();
        }

        $record->forceFill([
            'is_driver_verified' => true,
            'driver_verified_by' => (string) $request->user()->name,
            'driver_verified_at' => now(),
        ])->save();

        $record->load([
            'driverVerifiedBy',
            'driverProfile.user',
            'vehicle',
            'sheet.trip.route.startPoint',
            'sheet.trip.route.endPoint',
            'sheet.trip.depot',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Driver verified successfully.',
            'data' => (new TripResource($record))->resolve($request),
        ]);
    }

    public function startVerification(Request $request)
    {
        $validated = $request->validate(
            [
                'trip_id' => ['required', 'integer'],
                'actual_start_time' => ['nullable', 'date_format:H:i'],
                'actual_end_time' => ['nullable', 'date_format:H:i'],
                'odometer_start_reading' => ['nullable', 'numeric', 'min:0'],
                'odometer_start_image_path' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'odometer_end_reading' => ['nullable', 'numeric', 'min:0'],
                'odometer_end_image_path' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'route_start_soc_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'route_start_soc_percent_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'route_end_soc_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'route_end_soc_percent_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'remarks' => ['nullable', 'string'],
                'is_vehical_verified' => ['nullable', 'boolean'],
                'start_punc' => ['nullable', 'string', 'max:255'],
                'reason_for_kilometer_loss' => ['nullable', 'string'],
            ],
            [
                'route_start_soc_percent.numeric' => 'Battery Percentage is invalid.',
                'route_start_soc_percent.min' => 'Battery Percentage must be at least 0.',
                'route_start_soc_percent.max' => 'Battery Percentage must not be greater than 100.',
                'route_end_soc_percent.numeric' => 'Battery Percentage is invalid.',
                'route_end_soc_percent.min' => 'Battery Percentage must be at least 0.',
                'route_end_soc_percent.max' => 'Battery Percentage must not be greater than 100.',
            ]
        );

        $controllerProfileId = $request->user()->controllerProfile?->id;

        abort_if(! $controllerProfileId, 404);

        $record = $this->tripQuery($controllerProfileId)
            ->with([
                'dor',
                'driverProfile.user',
                'vehicle',
                'sheet.trip.route',
                'sheet.trip.assignments.driverProfile.user',
                'sheet.trip.assignments.vehicle',
            ])
            ->whereKey($validated['trip_id'])
            ->firstOrFail();

        DB::transaction(function () use ($request, $record, $validated): void {
            $record->forceFill([
                'actual_start_time' => $validated['actual_start_time'] ?? $record->actual_start_time,
                'actual_reach_time' => $validated['actual_end_time'] ?? $record->actual_reach_time,
                'vehicle_condition' => array_key_exists('remarks', $validated) ? $validated['remarks'] : $record->vehicle_condition,
                'is_vehicle_verified' => $this->requestBoolean($validated, 'is_vehical_verified', 'is_vehicle_verified', true),
                'vehicle_verified_by' => (string) $request->user()->name,
                'vehicle_verified_at' => now(),
                // 'is_driver_verified' => true,
                // 'driver_verified_by' => (string) $request->user()->name,
                // 'driver_verified_at' => now(),
                'is_verified_by_controller' => true,
                'verified_by_controller' => (string) $request->user()->name,
                'verified_by_controller_at' => now(),
            ])->save();

            $record->refresh()->load([
                'dor',
                'driverProfile.user',
                'vehicle',
                'sheet.trip.route',
                'sheet.trip.depot',
                'sheet.trip.assignments.driverProfile.user',
                'sheet.trip.assignments.vehicle',
                'rosters.driverProfile.user',
                'rosters.vehicle',
            ]);

            $dor = $record->dor;
            $payload = $this->apiDorPayload($record, $validated, $dor)
                + $this->apiDorImagePayload($request, $record, $dor);

            if ($dor) {
                $dor->update($payload + ['updated_by' => $request->user()->id]);
            } else {
                $record->dor()->create($payload + [
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);
            }
        });

        $record->load([
            'dor',
            'driverProfile.user',
            'vehicle',
            'sheet.trip.route.startPoint',
            'sheet.trip.route.endPoint',
            'sheet.trip.depot',
            'driverVerifiedBy',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip verification saved successfully.',
            'data' => (new TripResource($record))->withDetails()->resolve($request),
        ]);
    }

    private function tripQuery(int $controllerProfileId): Builder
    {
        return TripSheetEntry::query()
            ->with([
                'driverProfile.user',
                'vehicle',
                'sheet.trip.route.startPoint',
                'sheet.trip.route.endPoint',
                'sheet.trip.depot',
                'rosters' => fn($rosterQuery) => $rosterQuery->where('controller_profile_id', $controllerProfileId),
            ])
            ->whereHas('rosters', function (Builder $query) use ($controllerProfileId): void {
                $query->where('controller_profile_id', $controllerProfileId);
            });
    }

    private function driverTripQuery(int $driverProfileId): Builder
    {
        return TripSheetEntry::query()
            ->with([
                'driverProfile.user',
                'vehicle',
                'sheet.trip.route.startPoint',
                'sheet.trip.route.endPoint',
                'sheet.trip.depot',
                'rosters' => fn($rosterQuery) => $rosterQuery->where('driver_profile_id', $driverProfileId),
            ])
            ->whereHas('rosters', function (Builder $query) use ($driverProfileId): void {
                $query->where('driver_profile_id', $driverProfileId);
            });
    }

    private function applyTodayTripOrder(Builder $query): Builder
    {
        return $query
            ->orderByRaw('trip_order_sequence_no IS NULL')
            ->orderBy('trip_order_sequence_no')
            ->orderByDesc('id');
    }

    private function driverBelongsToTrip(TripSheetEntry $record, int $driverProfileId): bool
    {
        if ((int) $record->driver_profile_id === $driverProfileId) {
            return true;
        }

        if ($record->rosters?->contains(fn($roster) => (int) $roster->driver_profile_id === $driverProfileId)) {
            return true;
        }

        $tripDate = $record->sheet?->date;

        if (! $tripDate) {
            return false;
        }

        $assignment = $record->sheet?->trip?->assignments
            ?->first(fn($assignment) => $assignment->from_date?->lte($tripDate) && $assignment->to_date?->gte($tripDate));

        return (int) $assignment?->driver_profile_id === $driverProfileId;
    }

    private function invalidDriverQr(): never
    {
        throw ValidationException::withMessages([
            'driver_code' => 'Invalid driver QR.',
        ]);
    }

    private function apiDorPayload(TripSheetEntry $entry, array $data, ?TripSheetEntryDor $dor): array
    {
        $assignment = $this->assignmentForEntry($entry);
        $trip = $entry->sheet?->trip;
        $driver = $entry->driverProfile ?: $assignment?->driverProfile ?: $entry->rosters?->first()?->driverProfile;
        $vehicle = $entry->vehicle ?: $assignment?->vehicle ?: $entry->rosters?->first()?->vehicle;
        $scheduleKm = $this->nullableFloat($trip?->schedule_km);
        $routeKmLoss = $this->nullableFloat($dor?->route_km_loss);
        $actualRouteKm = $this->calculatedActualRouteKm($scheduleKm, $routeKmLoss, $dor?->actual_route_km);
        $scheduleTrip = $this->nullableInt($dor?->schedule_trip);
        $actualTrip = $this->nullableInt($dor?->actual_trip);
        $odometerStart = $this->nullableFloat($data['odometer_start_reading'] ?? $dor?->odometer_start_reading);
        $odometerEnd = $this->nullableFloat($data['odometer_end_reading'] ?? $dor?->odometer_end_reading);
        $odometerDiff = $this->calculatedOdometerDiff($odometerStart, $odometerEnd, $dor?->odometer_diff_km);
        $routeStartSoc = $this->nullableFloat($data['route_start_soc_percent'] ?? $dor?->route_start_soc_percent);
        $routeEndSoc = $this->nullableFloat($data['route_end_soc_percent'] ?? $dor?->route_end_soc_percent);
        $socConsumption = $this->calculatedSocConsumption($routeStartSoc, $routeEndSoc, $dor?->soc_consumption_on_route_percent);
        $dcrKwh = $this->nullableFloat($dor?->dcr_kwh);
        $dcrChargedSoc = $this->nullableFloat($dor?->dcr_charged_soc);
        $batterySizeKwh = $this->nullableFloat($dor?->battery_size_kwh);

        return [
            'depot_name' => $trip?->depot?->name,
            'dor_date' => $entry->sheet?->date?->format('Y-m-d'),
            'bus_no' => $vehicle?->vehicle_no,
            'route_no' => $trip?->route?->route_code ?: $trip?->route?->code,
            'duty' => $trip?->trip_title,
            'shift' => ucfirst((string) $entry->side),
            'driver_badge_no' => $driver?->badge_number ?: $driver?->user?->code,
            'schedule_start_time' => $this->formatSheetTime($entry->departure_time),
            'schedule_end_time' => $this->formatSheetTime($entry->arrival_time),
            'actual_start_time' => $this->formatSheetTime($entry->actual_start_time),
            'actual_end_time' => $this->formatSheetTime($entry->actual_reach_time),
            'start_punc' => $data['start_punc'] ?? $this->sheetStartDelay($entry->departure_time, $entry->actual_start_time),
            'route_completion_time' => $this->formatSheetTime($entry->actual_reach_time ?: $entry->arrival_time),
            'schedule_km' => $scheduleKm,
            'route_km_loss' => $routeKmLoss,
            'actual_route_km' => $actualRouteKm,
            'schedule_trip' => $scheduleTrip,
            'actual_trip' => $actualTrip,
            'miss_trip' => $this->calculatedMissTrip($scheduleTrip, $actualTrip, $dor?->miss_trip),
            'odometer_start_reading' => $odometerStart,
            'odometer_end_reading' => $odometerEnd,
            'odometer_diff_km' => $odometerDiff,
            'difference' => $this->calculatedDifference($actualRouteKm, $odometerDiff, $dor?->difference),
            'dor_account_responsible_id' => $dor?->dor_account_responsible_id,
            'account_responsible' => $dor?->account_responsible,
            'dor_kilometer_loss_reason_id' => $dor?->dor_kilometer_loss_reason_id,
            'reason_for_kilometer_loss' => $data['reason_for_kilometer_loss'] ?? $dor?->reason_for_kilometer_loss,
            'after_sales_reason' => $dor?->after_sales_reason,
            'penalty_infraction' => $dor?->penalty_infraction,
            'remarks' => $data['remarks'] ?? $dor?->remarks,
            'route_start_soc_percent' => $routeStartSoc,
            'route_end_soc_percent' => $routeEndSoc,
            'soc_consumption_on_route_percent' => $socConsumption,
            'soc_per_km' => $this->calculatedSocPerKm($socConsumption, $actualRouteKm, $dor?->soc_per_km),
            'run_kilometer_per_soc' => $this->calculatedRunKmPerSoc($actualRouteKm, $socConsumption, $dor?->run_kilometer_per_soc),
            'dor_kwh_per_km_odo' => $this->calculatedDorKwhPerKmOdo($dcrChargedSoc, $socConsumption, $odometerDiff),
            'dor_kwh_per_km_act' => $this->calculatedDorKwhPerKmAct($dcrKwh, $actualRouteKm),
            'dor_kwh' => $this->calculatedDorKwh($socConsumption, $batterySizeKwh),
            'dcr_kwh_per_km_odo' => $this->nullableFloat($dor?->dcr_kwh_per_km_odo),
            'dcr_kwh_per_km_act' => $this->nullableFloat($dor?->dcr_kwh_per_km_act),
            'dcr_kwh' => $dcrKwh,
            'dcr_charged_soc' => $dcrChargedSoc,
            'energy_absorption' => $this->nullableFloat($dor?->energy_absorption),
            'battery_size_kwh' => $batterySizeKwh,
            'vp1' => $this->nullableFloat($dor?->vp1),
            'vp2' => $this->nullableFloat($dor?->vp2),
            'dp' => $this->nullableFloat($dor?->dp),
            'penalty' => $this->nullableFloat($dor?->penalty),
            'model_9m_12m' => $dor?->model_9m_12m,
        ];
    }

    private function apiDorImagePayload(Request $request, TripSheetEntry $entry, ?TripSheetEntryDor $dor): array
    {
        $payload = [];
        $directory = 'trip-dor-verification/' . $entry->id;

        foreach (
            [
                'odometer_start_image_path',
                'odometer_end_image_path',
                'route_start_soc_percent_image',
                'route_end_soc_percent_image',
            ] as $column
        ) {
            $file = $request->file($column);

            if (! $file instanceof UploadedFile) {
                continue;
            }

            if ($dor?->{$column}) {
                Storage::disk('public')->delete($dor->{$column});
            }

            $payload[$column] = $file->store($directory, 'public');
        }

        return $payload;
    }

    private function requestBoolean(array $data, string $primary, string $fallback, bool $default): bool
    {
        $value = $data[$primary] ?? $data[$fallback] ?? $default;

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function assignmentForEntry(TripSheetEntry $entry)
    {
        $trip = $entry->sheet?->trip;
        $date = $entry->sheet?->date;

        if (! $trip || ! $date) {
            return null;
        }

        return $trip->assignments
            ->first(fn($assignment) => $assignment->from_date?->lte($date) && $assignment->to_date?->gte($date));
    }

    private function formatSheetTime(?string $value): ?string
    {
        return $value ? substr($value, 0, 5) : null;
    }

    private function sheetStartDelay(?string $startTime, ?string $actualStartTime): ?string
    {
        if (! $startTime || ! $actualStartTime) {
            return null;
        }

        $scheduled = Carbon::createFromFormat('H:i', $this->formatSheetTime($startTime));
        $actual = Carbon::createFromFormat('H:i', $this->formatSheetTime($actualStartTime));
        $minutes = (int) round($scheduled->diffInMinutes($actual, false));
        $label = abs($minutes) === 1 ? 'min' : 'mins';

        return $minutes >= 0
            ? "{$minutes} {$label}"
            : abs($minutes) . " {$label} early";
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function calculatedActualRouteKm(?float $scheduleKm, ?float $routeKmLoss, mixed $fallback): ?float
    {
        if ($scheduleKm !== null && $routeKmLoss !== null) {
            return max(0, $scheduleKm - $routeKmLoss);
        }

        return $this->nullableFloat($fallback);
    }

    private function calculatedMissTrip(?int $scheduleTrip, ?int $actualTrip, mixed $fallback): ?int
    {
        if ($scheduleTrip !== null && $actualTrip !== null) {
            return max(0, $scheduleTrip - $actualTrip);
        }

        return $this->nullableInt($fallback);
    }

    private function calculatedOdometerDiff(?float $start, ?float $end, mixed $fallback): ?float
    {
        if ($start !== null && $end !== null) {
            return max(0, $end - $start);
        }

        return $this->nullableFloat($fallback);
    }

    private function calculatedDifference(?float $actualRouteKm, ?float $odometerDiff, mixed $fallback): ?float
    {
        if ($actualRouteKm !== null && $odometerDiff !== null) {
            return $actualRouteKm - $odometerDiff;
        }

        return $this->nullableFloat($fallback);
    }

    private function calculatedSocConsumption(?float $startSoc, ?float $endSoc, mixed $fallback): ?float
    {
        if ($startSoc !== null && $endSoc !== null) {
            return max(0, $startSoc - $endSoc);
        }

        return $this->nullableFloat($fallback);
    }

    private function calculatedSocPerKm(?float $socConsumption, ?float $actualRouteKm, mixed $fallback): ?float
    {
        if ($socConsumption !== null && $actualRouteKm && $actualRouteKm > 0) {
            return $socConsumption / $actualRouteKm;
        }

        return $this->nullableFloat($fallback);
    }

    private function calculatedRunKmPerSoc(?float $actualRouteKm, ?float $socConsumption, mixed $fallback): ?float
    {
        if ($actualRouteKm !== null && $socConsumption && $socConsumption > 0) {
            return $actualRouteKm / $socConsumption;
        }

        return $this->nullableFloat($fallback);
    }

    private function calculatedDorKwhPerKmOdo(?float $dcrChargedSoc, ?float $socConsumption, ?float $odometerDiff): ?float
    {
        if ($dcrChargedSoc !== null && $socConsumption !== null && $odometerDiff !== null && $odometerDiff > 0) {
            return ($dcrChargedSoc * $socConsumption) / $odometerDiff / 100;
        }

        return null;
    }

    private function calculatedDorKwhPerKmAct(?float $dcrKwh, ?float $actualRouteKm): ?float
    {
        if ($dcrKwh !== null && $actualRouteKm !== null && $actualRouteKm > 0) {
            return $dcrKwh / $actualRouteKm;
        }

        return null;
    }

    private function calculatedDorKwh(?float $socConsumption, ?float $batterySizeKwh): ?float
    {
        if ($socConsumption !== null && $batterySizeKwh !== null) {
            return ($socConsumption * $batterySizeKwh) / 100;
        }

        return null;
    }
}
