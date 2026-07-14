<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sheet = $this->sheet;
        $trip = $sheet?->trip;
        $route = $trip?->route;
        $side = strtolower((string) $this->side);
        $start = $side === 'down' ? $route?->endPoint?->name : $route?->startPoint?->name;
        $end = $side === 'down' ? $route?->startPoint?->name : $route?->endPoint?->name;
        $routeText = $start && $end ? "{$start} to {$end}" : ($trip?->trip_title ?: 'Assigned trip');
        $vehicleNumber = $this->vehicle?->vehicle_no;

        return [
            'id' => $this->id,
            'type' => 'trip_assigned',
            'title' => 'Trip assigned: ' . ($trip?->trip_title ?: $sheet?->code ?: "#{$this->id}"),
            'body' => $vehicleNumber ? "{$routeText} - Vehicle {$vehicleNumber}" : $routeText,
            'trip_sheet_entry_id' => $this->id,
            'trip_id' => $trip?->id,
            'trip_title' => $trip?->trip_title,
            'trip_date' => $sheet?->date?->toDateString(),
            'starting_point' => $start,
            'ending_point' => $end,
            'vehicle_number' => $vehicleNumber,
        ];
    }
}
