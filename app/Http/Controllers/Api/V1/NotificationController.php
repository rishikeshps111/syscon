<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\TripSheetEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        $depotId = $user->hasRole('Controller')
            ? $user->controllerProfile?->depot_id
            : ($user->hasRole('Supervisor') ? $user->supervisorProfile?->depot_id : null);
        $driverProfileId = $depotId ? null : $user->driverProfile?->id;

        $notifications = TripSheetEntry::query()
            ->with([
                'sheet.trip.route.startPoint',
                'sheet.trip.route.endPoint',
                'vehicle',
            ])
            ->when(
                $driverProfileId,
                fn (Builder $query) => $query->whereHas(
                    'rosters',
                    fn (Builder $rosterQuery) => $rosterQuery->where('driver_profile_id', $driverProfileId)
                ),
                fn (Builder $query) => $depotId
                    ? $query->forDepot((int) $depotId)
                    : $query->whereRaw('1 = 0')
            )
            ->whereHas('sheet', fn (Builder $query) => $query
                ->whereDate('date', today()))
            ->latest('id')
            ->paginate($data['per_page'] ?? 20);

        return NotificationResource::collection($notifications);
    }
}
