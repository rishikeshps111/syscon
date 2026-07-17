<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Models\TripSheetEntry;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{
    public function detail(Request $request)
    {
        $vehicleCode = trim((string) ($request->input('vehicle_code') ?? $request->input('vehical_code') ?? ''));

        if ($vehicleCode === '') {
            $this->invalidVehicle();
        }

        $vehicle = Vehicle::with(['state', 'oem', 'depot', 'branch'])
            ->where('vehicle_code', $vehicleCode)
            ->first();

        if (! $vehicle) {
            $this->invalidVehicle();
        }

        $today = Carbon::today()->toDateString();
        $todayTrips = collect();
        $controllerUnverifiedCount = 0;
        $depotId = $this->userDepotId($request);

        if ($depotId) {
            $todayQuery = $this->todayTripsForVehicleQuery($depotId, $vehicleCode, $today);
            $controllerUnverifiedCount = (clone $todayQuery)
                ->where(function (Builder $query): void {
                    $query->where('is_verified_by_controller', false)
                        ->orWhereNull('is_verified_by_controller');
                })
                ->count();
            $todayTrips = $todayQuery->latest()->get();
        }

        return (new VehicleResource($vehicle))->withTodayTrips($todayTrips, [
            'date' => Carbon::parse($today)->format('d M Y'),
            'total_count' => $todayTrips->count(),
            'is_verified_by_controller_false_count' => $controllerUnverifiedCount,
        ]);
    }

    private function todayTripsForVehicleQuery(int $depotId, string $vehicleCode, string $today): Builder
    {
        return TripSheetEntry::query()
            ->with([
                'driverProfile.user',
                'vehicle',
                'sheet.trip.route.startPoint',
                'sheet.trip.route.endPoint',
                'sheet.trip.depot',
                'rosters',
            ])
            ->whereRelation('sheet.trip', 'depot_id', $depotId)
            ->whereHas('sheet', fn(Builder $sheetQuery) => $sheetQuery->whereDate('date', $today))
            ->where(function (Builder $query) use ($vehicleCode): void {
                $query->whereHas('vehicle', fn(Builder $vehicleQuery) => $vehicleQuery->where('vehicle_code', $vehicleCode))
                    ->orWhereHas('rosters.vehicle', fn(Builder $vehicleQuery) => $vehicleQuery->where('vehicle_code', $vehicleCode))
                    ->orWhereHas('sheet.trip.assignments.vehicle', fn(Builder $vehicleQuery) => $vehicleQuery->where('vehicle_code', $vehicleCode));
            });
    }

    private function userDepotId(Request $request): ?int
    {
        $user = $request->user();
        $depotId = $user->hasRole('Controller')
            ? $user->controllerProfile?->depot_id
            : ($user->hasRole('Supervisor') ? $user->supervisorProfile?->depot_id : null);

        return $depotId ? (int) $depotId : null;
    }

    private function invalidVehicle(): never
    {
        throw ValidationException::withMessages([
            'vehicle_code' => 'invalid vehical',
        ]);
    }
}
