<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Models\TripSheetEntry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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

        $totalCount = (clone $query)->count();
        $controllerUnverifiedCount = (clone $query)
            ->where(function (Builder $query): void {
                $query->where('is_verified_by_controller', false)
                    ->orWhereNull('is_verified_by_controller');
            })
            ->count();

        $records = $query->get();

        return TripResource::collection($records)->additional([
            'meta' => [
                'date' => Carbon::parse($today)->format('d M Y'),
                'total_count' => $totalCount,
                'is_verified_by_controller_false_count' => $controllerUnverifiedCount,
            ],
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
}
