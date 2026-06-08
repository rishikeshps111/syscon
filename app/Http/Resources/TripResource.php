<?php

namespace App\Http\Resources;

use App\Models\Roster;
use App\Models\TripSheet;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sheet = $this->sheet;
        $trip = $sheet?->trip;
        $route = $trip?->route;
        $side = strtolower((string) $this->side);
        $roster = $this->rosters?->first();

        return [
            'trip_sheet_code' => $sheet?->code,
            'side' => ucfirst((string) $this->side),
            'trip_title' => $trip?->trip_title,
            'starting_point' => $side === 'down' ? $route?->endPoint?->name : $route?->startPoint?->name,
            'ending_point' => $side === 'down' ? $route?->startPoint?->name : $route?->endPoint?->name,
            'driver_name' => $this->driverProfile?->user?->name,
            'depot_name' => $trip?->depot?->name,
            'vehicle_number' => $this->vehicle?->vehicle_no,
            'date' => $this->formatDate($sheet?->date),
            'trip_sheet_status' => $sheet?->status,
            'trip_sheet_status_label' => $this->tripSheetStatusLabel($sheet?->status),
            'roster_status' => $roster?->status,
            'roster_status_label' => $this->rosterStatusLabel($roster?->status),
            'actual_start_time' => $this->formatTime($this->actual_start_time),
            'actual_end_time' => $this->formatTime($this->actual_reach_time),
        ];
    }

    private function formatDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('d M Y');
        }

        return Carbon::parse($value)->format('d M Y');
    }

    private function formatTime($value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('h:i a');
        }

        return Carbon::parse($value)->format('h:i a');
    }

    private function tripSheetStatusLabel(?string $status): ?string
    {
        if (! $status) {
            return null;
        }

        return TripSheet::STATUSES[$status] ?? $status;
    }

    private function rosterStatusLabel(?string $status): ?string
    {
        if (! $status) {
            return null;
        }

        return Roster::STATUSES[$status] ?? $status;
    }
}
