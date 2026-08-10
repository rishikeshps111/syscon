<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\TripVerificationCompleted;
use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Models\TripVerificationCompletedAlert;
use App\Models\TripSheet;
use App\Models\TripSheetEntry;
use App\Models\TripSheetEntryDor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

class TripController extends Controller
{
    public function controllers(Request $request)
    {
        $depotId = $this->userDepotId($request);

        if (! $depotId) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $controllers = User::role('Controller')
            ->where('is_active', true)
            ->whereHas('controllerProfile', fn(Builder $query) => $query->where('depot_id', $depotId))
            ->with('controllerProfile:id,user_id,depot_id')
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn(User $controller): array => [
                'id' => $controller->id,
                'controller_profile_id' => $controller->controllerProfile?->id,
                'code' => $controller->code,
                'name' => $controller->name,
            ]);

        return response()->json([
            'success' => true,
            'data' => $controllers,
        ]);
    }

    public function index(Request $request)
    {
        $depotId = $this->userDepotId($request);

        if (! $depotId) {
            return TripResource::collection(collect());
        }

        $query = $this->depotTripQuery($depotId);
        if ($request->user()->hasRole('Controller')) {
            $userName = (string) $request->user()->name;

            $query->where(function (Builder $query) use ($userName): void {
                $query->Where(function (Builder $query) use ($userName): void {
                    $query->where('is_initial_verified', true)
                        ->where('initial_verification_by', $userName);
                })->orWhere(function (Builder $query) use ($userName): void {
                    $query->where('is_final_verified', true)
                        ->where('final_verification_by', $userName);
                });
            });
        }
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
                AllowedFilter::callback('controller', function (Builder $query, $value): void {
                    $this->filterByController($query, $value);
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
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:' . implode(',', array_keys(TripSheet::STATUSES))],
        ]);

        $depotId = $this->userDepotId($request);
        $today = Carbon::today()->toDateString();

        if (! $depotId) {
            return TripResource::collection(collect())->additional([
                'meta' => [
                    'date' => Carbon::parse($today)->format('d M Y'),
                    'total_count' => 0,
                    'is_final_verified_false_count' => 0,
                ],
            ]);
        }

        $query = $this->depotTripQuery($depotId)
            ->whereHas('sheet', fn(Builder $sheetQuery) => $sheetQuery->whereDate('date', $today));

        $totalCount = (clone $query)->count();
        $controllerUnverifiedCount = (clone $query)
            ->where(function (Builder $query): void {
                $query->where('is_final_verified', false)
                    ->orWhereNull('is_final_verified');
            })
            ->count();

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $vehicleCode = trim((string) ($request->input('vehicle_code') ?? $request->input('vehical_code') ?? ''));

        if ($vehicleCode !== '') {
            $query->where(function (Builder $query) use ($vehicleCode): void {
                $query->whereHas('vehicle', fn(Builder $vehicleQuery) => $vehicleQuery->where('vehicle_code', $vehicleCode))
                    ->orWhereHas('rosters.vehicle', fn(Builder $vehicleQuery) => $vehicleQuery->where('vehicle_code', $vehicleCode))
                    ->orWhereHas('sheet.trip.assignments.vehicle', fn(Builder $vehicleQuery) => $vehicleQuery->where('vehicle_code', $vehicleCode));
            });
        }

        $records = $this->applyTodayTripOrder($query)->get();

        return TripResource::collection($records)->additional([
            'meta' => [
                'date' => Carbon::parse($today)->format('d M Y'),
                'total_count' => $totalCount,
                'is_final_verified_false_count' => $controllerUnverifiedCount,
            ],
        ]);
    }

    public function show(Request $request, TripSheetEntry $tripSheetEntry)
    {
        $depotId = $this->userDepotId($request);

        abort_if(! $depotId, 404);

        $record = $this->depotTripQuery($depotId)
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
                    'is_final_verified_false_count' => 0,
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
                $query->where('is_final_verified', false)
                    ->orWhereNull('is_final_verified');
            })
            ->count();

        $records = $this->applyTodayTripOrder($query)->get();

        return TripResource::collection($records)->additional([
            'meta' => [
                'date' => Carbon::parse($today)->format('d M Y'),
                'total_count' => $totalCount,
                'is_final_verified_false_count' => $controllerUnverifiedCount,
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
            'is_driver_verified' => ['required', 'boolean'],
        ]);

        $isDriverVerified = $request->boolean('is_driver_verified');

        $depotId = $this->userDepotId($request);

        abort_if(! $depotId, 404);

        $driver = User::role('Driver')
            ->with('driverProfile')
            ->where('code', trim($validated['driver_code']))
            ->first();

        if (! $driver?->driverProfile) {
            $this->invalidDriverQr();
        }

        $record = $this->depotTripQuery($depotId)
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
            'is_driver_verified' => $isDriverVerified,
            'driver_verified_by' => $isDriverVerified ? (string) $request->user()->name : null,
            'driver_verified_at' => $isDriverVerified ? now() : null,
        ])->save();

        $record->load([
            'driverVerifiedBy',
            'driverProfile.user',
            'vehicle',
            'sheet.trip.route.startPoint',
            'sheet.trip.route.endPoint',
            'sheet.trip.route.stops',
            'sheet.trip.depot',
        ]);

        return response()->json([
            'success' => true,
            'message' => $isDriverVerified
                ? 'Driver verified successfully.'
                : 'Driver verification removed successfully.',
            'data' => (new TripResource($record))->resolve($request),
        ]);
    }

    public function startVerification(Request $request)
    {
        $tripId = $request->validate([
            'trip_id' => ['required', 'integer', 'exists:trip_sheet_entries,id'],
        ])['trip_id'];

        $depotId = $this->userDepotId($request);

        abort_unless($request->user()->hasRole(['Controller', 'Supervisor']) && $depotId, 403);

        $record = TripSheetEntry::query()
            ->with([
                'dor',
                'driverProfile.user',
                'vehicle',
                'sheet.trip.route',
                'sheet.trip.depot',
                'sheet.trip.fromDepot',
                'sheet.trip.toDepot',
                'sheet.trip.assignments.driverProfile.user',
                'sheet.trip.assignments.vehicle',
            ])
            ->whereKey($tripId)
            ->firstOrFail();

        $stage = $record->status;
        $this->authorizeVerificationDepot($record, $depotId, $stage);

        $commonRules = [
            'trip_id' => ['required', 'integer'],
            'remarks' => ['nullable', 'string'],
        ];
        $imageRule = ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];

        $rules = match ($stage) {
            'pending' => $commonRules + [
                'actual_start_time' => ['required', 'date_format:H:i'],
                'odometer_start_reading' => ['required', 'numeric', 'min:0'],
                'odometer_start_image_path' => $imageRule,
                'route_start_soc_percent' => ['required', 'numeric', 'min:0', 'max:100'],
                'route_start_soc_percent_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'is_vehicle_verified' => ['required', 'boolean'],
            ],
            'initial_verification_completed' => $commonRules + [
                'actual_end_time' => ['required', 'date_format:H:i'],
                'odometer_end_reading' => ['required', 'numeric', 'min:0'],
                'odometer_end_image_path' => $imageRule,
                'route_end_soc_percent' => ['required', 'numeric', 'min:0', 'max:100'],
                'route_end_soc_percent_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'start_punc' => ['nullable', 'string', 'max:255'],
                'reason_for_kilometer_loss' => ['nullable', 'string'],
            ],
            default => throw ValidationException::withMessages([
                'trip_id' => 'This trip sheet entry is not available for verification.',
            ]),
        };

        $validated = $request->validate($rules, [
            // Common fields
            'trip_id.required' => 'Trip ID is required.',
            'trip_id.integer' => 'Trip ID must be a valid number.',
            'trip_id.exists' => 'The selected trip does not exist.',

            // Start details
            'actual_start_time.required' => 'Actual start time is required.',
            'actual_start_time.date_format' => 'Actual start time must be in HH:MM format.',

            'odometer_start_reading.required' => 'Starting KM reading is required.',
            'odometer_start_reading.numeric' => 'Starting KM reading must be a valid number.',
            'odometer_start_reading.min' => 'Starting KM reading cannot be less than 0.',

            'odometer_start_image_path.required' => 'Starting KM image is required.',
            'odometer_start_image_path.image' => 'Starting KM file must be an image.',
            'odometer_start_image_path.mimes' => 'Starting KM image must be a JPG, JPEG, PNG, or WEBP file.',
            'odometer_start_image_path.max' => 'Starting KM image must not be larger than 4 MB.',

            'route_start_soc_percent.required' => 'Initial battery percentage is required.',
            'route_start_soc_percent.numeric' => 'Initial battery percentage must be a valid number.',
            'route_start_soc_percent.min' => 'Initial battery percentage must be at least 0.',
            'route_start_soc_percent.max' => 'Initial battery percentage must not be greater than 100.',

            'route_start_soc_percent_image.required' => 'Initial battery percentage image is required.',
            'route_start_soc_percent_image.image' => 'Initial battery percentage file must be an image.',
            'route_start_soc_percent_image.mimes' => 'Initial battery image must be a JPG, JPEG, PNG, or WEBP file.',
            'route_start_soc_percent_image.max' => 'Initial battery image must not be larger than 4 MB.',

            'is_vehicle_verified.required' => 'Vehicle verification status is required.',
            'is_vehicle_verified.boolean' => 'Vehicle verification status must be Yes.',

            // End details
            'actual_end_time.required' => 'Actual end time is required.',
            'actual_end_time.date_format' => 'Actual end time must be in HH:MM format.',

            'odometer_end_reading.required' => 'Ending Km reading is required.',
            'odometer_end_reading.numeric' => 'Ending Km reading must be a valid number.',
            'odometer_end_reading.min' => 'Ending Km reading cannot be less than 0.',

            'odometer_end_image_path.required' => 'Ending Km image is required.',
            'odometer_end_image_path.image' => 'Ending Km file must be an image.',
            'odometer_end_image_path.mimes' => 'Ending Km image must be a JPG, JPEG, PNG, or WEBP file.',
            'odometer_end_image_path.max' => 'Ending Km image must not be larger than 4 MB.',

            'route_end_soc_percent.required' => 'Final battery percentage is required.',
            'route_end_soc_percent.numeric' => 'Final battery percentage must be a valid number.',
            'route_end_soc_percent.min' => 'Final battery percentage must be at least 0.',
            'route_end_soc_percent.max' => 'Final battery percentage must not be greater than 100.',

            'route_end_soc_percent_image.required' => 'Final battery percentage image is required.',
            'route_end_soc_percent_image.image' => 'Final battery percentage file must be an image.',
            'route_end_soc_percent_image.mimes' => 'Final battery image must be a JPG, JPEG, PNG, or WEBP file.',
            'route_end_soc_percent_image.max' => 'Final battery image must not be larger than 4 MB.',

            // Other details
            'start_punc.string' => 'Start punctuality must be valid text.',
            'start_punc.max' => 'Start punctuality must not exceed 255 characters.',

            'reason_for_kilometer_loss.string' => 'Reason for kilometer loss must be valid text.',
        ]);

        DB::transaction(function () use ($request, $record, $validated, $stage): void {
            $record = TripSheetEntry::query()->lockForUpdate()->findOrFail($record->id);
            $record->load(['sheet.trip.route', 'sheet.trip.depot', 'sheet.trip.fromDepot', 'sheet.trip.toDepot']);

            if ($record->status !== $stage) {
                throw ValidationException::withMessages([
                    'trip_id' => 'The verification stage has already changed. Please refresh and try again.',
                ]);
            }

            $userName = (string) $request->user()->name;
            $isVehicleVerified = $this->requestBoolean(
                $validated,
                'is_vehicle_verified',
                'is_vehicle_verified',
                false
            );
            // $isVehicleVerified = true;
            $entryPayload = $stage === 'pending'
                ? [
                    'status' => 'initial_verification_completed',
                    'actual_start_time' => $validated['actual_start_time'],
                    'starting_km' => $validated['odometer_start_reading'],
                    'starting_electric_charge' => $validated['route_start_soc_percent'],
                    'vehicle_condition' => $validated['remarks'] ?? $record->vehicle_condition,
                    'is_vehicle_verified' => $isVehicleVerified,
                    'vehicle_verified_by' => $isVehicleVerified ? $userName : null,
                    'vehicle_verified_at' => $isVehicleVerified ? now() : null,

                    'is_initial_verified' => true,
                    'initial_verification_by' => $userName,
                    'initial_verification_at' => now(),
                ]
                : [
                    'status' => 'verification_completed',
                    'actual_reach_time' => $validated['actual_end_time'],
                    'ending_km' => $validated['odometer_end_reading'],
                    'ending_electric_charge' => $validated['route_end_soc_percent'],
                    'is_final_verified' => true,
                    'final_verification_by' => $userName,
                    'final_verification_at' => now(),
                ];

            $record->forceFill($entryPayload)->save();

            $record->refresh()->load([
                'dor',
                'driverProfile.user',
                'vehicle',
                'sheet.trip.route',
                'sheet.trip.depot',
                'sheet.trip.fromDepot',
                'sheet.trip.toDepot',
                'sheet.trip.assignments.driverProfile.user',
                'sheet.trip.assignments.vehicle',
                'rosters.driverProfile.user',
                'rosters.vehicle',
            ]);

            $dor = $record->dor;
            $payload = $this->apiDorPayload($record, $validated, $dor)
                + $this->apiDorImagePayload(
                    $request,
                    $record,
                    $dor,
                    $stage === 'pending'
                        ? ['odometer_start_image_path', 'route_start_soc_percent_image']
                        : ['odometer_end_image_path', 'route_end_soc_percent_image']
                );

            if ($dor) {
                $dor->update($payload + ['updated_by' => $request->user()->id]);
            } else {
                $record->dor()->create($payload + [
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);
            }

            $this->syncSheetVerificationStatus($record->sheet);
        });

        $record->refresh()->load([
            'dor',
            'driverProfile.user',
            'vehicle',
            'sheet.trip.route.startPoint',
            'sheet.trip.route.endPoint',
            'sheet.trip.depot',
            'sheet.trip.fromDepot',
            'sheet.trip.toDepot',
            'driverVerifiedBy',
        ]);

        if ($stage === 'pending' && $record->is_initial_verified) {
            $this->notifySuperAdminsOfCompletedVerification($record, 'initial');
        } elseif ($stage === 'initial_verification_completed' && $record->is_final_verified) {
            $this->notifySuperAdminsOfCompletedVerification($record, 'final');
        }

        return response()->json([
            'success' => true,
            'message' => $stage === 'pending'
                ? 'Initial verification completed successfully.'
                : 'Final verification completed successfully.',
            'data' => (new TripResource($record))->withDetails()->resolve($request),
        ]);
    }

    private function notifySuperAdminsOfCompletedVerification(TripSheetEntry $record, string $verificationStage): void
    {
        User::role('Super Admin')
            ->where('is_active', true)
            ->orderBy('id')
            ->each(function (User $admin) use ($record, $verificationStage): void {
                $alert = TripVerificationCompletedAlert::firstOrCreate(
                    [
                        'user_id' => $admin->id,
                        'trip_sheet_entry_id' => $record->id,
                        'verification_stage' => $verificationStage,
                    ],
                    ['notified_at' => now()],
                );

                if (! $alert->wasRecentlyCreated) {
                    return;
                }

                try {
                    broadcast(new TripVerificationCompleted($alert));
                } catch (Throwable $exception) {
                    report($exception);
                }
            });
    }

    private function depotTripQuery(int $depotId): Builder
    {
        return TripSheetEntry::query()
            ->with([
                'driverProfile.user',
                'vehicle',
                'sheet.trip.route.startPoint',
                'sheet.trip.route.endPoint',
                'sheet.trip.route.stops',
                'sheet.trip.depot',
            ])
            ->forDepot($depotId);
    }

    private function syncSheetVerificationStatus(TripSheet $sheet): void
    {
        $statuses = $sheet->entries()->pluck('status');

        $status = match (true) {
            $statuses->isNotEmpty() && $statuses->every(
                fn(string $status): bool => $status === 'verification_completed'
            ) => 'verification_completed',
            $statuses->isNotEmpty() && $statuses->every(
                fn(string $status): bool => in_array(
                    $status,
                    ['initial_verification_completed', 'verification_completed'],
                    true
                )
            ) => 'initial_verification_completed',
            default => 'pending',
        };

        $sheet->update(['status' => $status]);
    }

    private function userDepotId(Request $request): ?int
    {
        $user = $request->user();
        $depotId = $user->hasRole('Controller')
            ? $user->controllerProfile?->depot_id
            : ($user->hasRole('Supervisor') ? $user->supervisorProfile?->depot_id : null);

        return $depotId ? (int) $depotId : null;
    }

    private function authorizeVerificationDepot(
        TripSheetEntry $entry,
        int $userDepotId,
        ?string $stage
    ): void {
        $trip = $entry->sheet?->trip;

        if (! $trip) {
            abort(404, 'Trip details were not found for this trip sheet entry.');
        }

        $requiredDepotId = match (true) {
            $trip->trip_side === 'both' => $trip->depot_id,
            $stage === 'pending' => $trip->from_depot_id,
            $stage === 'initial_verification_completed' => $trip->to_depot_id,
            default => null,
        };

        if (! $requiredDepotId) {
            throw ValidationException::withMessages([
                'trip_id' => 'This trip is not currently available for verification.',
            ]);
        }

        if ((int) $requiredDepotId !== $userDepotId) {
            $message = match ($stage) {
                'pending' => 'You cannot perform the initial verification because this trip does not belong to your departure depot.',
                'initial_verification_completed' => 'You cannot perform the final verification because this trip does not belong to your destination depot.',
                default => 'You are not authorized to verify this trip for your depot.',
            };

            abort(403, $message);
        }
    }

    private function filterByController(Builder $query, mixed $value): void
    {
        $value = trim((string) $value);

        if ($value === '') {
            return;
        }

        $controllerNames = User::role('Controller')
            ->where(function (Builder $userQuery) use ($value): void {
                $userQuery->where('name', $value)
                    ->orWhere('code', $value);

                if (ctype_digit($value)) {
                    $userQuery->orWhereKey((int) $value);
                }
            })
            ->pluck('name');

        $query->where('is_final_verified', true)
            ->whereIn('final_verification_by', $controllerNames);
    }

    private function driverTripQuery(int $driverProfileId): Builder
    {
        return TripSheetEntry::query()
            ->with([
                'driverProfile.user',
                'vehicle',
                'sheet.trip.route.startPoint',
                'sheet.trip.route.endPoint',
                'sheet.trip.route.stops',
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
            ->orderByRaw('actual_start_time IS NULL')
            ->orderBy('actual_start_time')
            ->orderBy('id');
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
        $isInitialVerification = array_key_exists('actual_start_time', $data);
        $verificationDepot = $trip?->trip_side === 'both'
            ? $trip?->depot
            : ($isInitialVerification ? $trip?->fromDepot : $trip?->toDepot);

        return [
            'depot_name' => $verificationDepot?->name,
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

    private function apiDorImagePayload(
        Request $request,
        TripSheetEntry $entry,
        ?TripSheetEntryDor $dor,
        array $columns = [
            'odometer_start_image_path',
            'odometer_end_image_path',
            'route_start_soc_percent_image',
            'route_end_soc_percent_image',
        ]
    ): array {
        $payload = [];
        $directory = 'trip-dor-verification/' . $entry->id;

        foreach ($columns as $column) {
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
