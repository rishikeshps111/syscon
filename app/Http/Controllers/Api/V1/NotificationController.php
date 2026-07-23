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
        $controllerProfileId = $user->controllerProfile?->id;
        $driverProfileId = $user->driverProfile?->id;

        $notifications = TripSheetEntry::query()
            ->with([
                'sheet.trip.route.startPoint',
                'sheet.trip.route.endPoint',
                'vehicle',
            ])
            ->whereHas('rosters', function (Builder $query) use ($controllerProfileId, $driverProfileId): void {
                if ($driverProfileId) {
                    $query->where('driver_profile_id', $driverProfileId);
                } else {
                    $query->where('controller_profile_id', $controllerProfileId ?? 0);
                }
            })
            ->whereHas('sheet', fn(Builder $query) => $query
                ->whereDate('date', today()))
            ->latest('id')
            ->paginate($data['per_page'] ?? 20);

        return NotificationResource::collection($notifications);
    }
}
