<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class VehicleResource extends JsonResource
{
    private ?Collection $todayTrips = null;

    private array $todayTripsMeta = [];

    public function withTodayTrips(Collection $trips, array $meta = []): self
    {
        $this->todayTrips = $trips;
        $this->todayTripsMeta = $meta;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'vehicle_code' => $this->vehicle_code,
            'vehicle_no' => $this->vehicle_no,
            'vehicle_type' => $this->vehicle_type,
            'fuel_type' => $this->fuel_type,
            'vehicle_category' => $this->vehicle_category,
            'make' => $this->make,
            'model' => $this->model,
            'variant' => $this->variant,
            'capacity_seating' => $this->capacity_seating,
            'capacity_load' => $this->capacity_load,
            'battery_capacity' => $this->battery_capacity,
            'range_km' => $this->range_km,
            'engine_no' => $this->engine_no,
            'chassis_no' => $this->chassis_no,
            'registration_date' => $this->formatDate($this->registration_date),
            'registration_valid_upto' => $this->formatDate($this->registration_valid_upto),
            'fitness_expiry' => $this->formatDate($this->fitness_expiry),
            'permit_expiry' => $this->formatDate($this->permit_expiry),
            'insurance_expiry' => $this->formatDate($this->insurance_expiry),
            'pollution_expiry' => $this->formatDate($this->pollution_expiry),
            'gps_enabled' => $this->gps_enabled,
            'gps_imei' => $this->gps_imei,
            'state_name' => $this->state?->name,
            'oem_name' => $this->oem?->oem_name,
            'depot_name' => $this->depot?->name,
            'branch_name' => $this->branch?->name,
            'status' => $this->status,
            'is_verified' => $this->is_verified,
            'remarks' => $this->remarks,
        ];

        if ($this->todayTrips !== null) {
            $data['today_trips'] = TripResource::collection($this->todayTrips)->resolve($request);
            $data['today_trips_meta'] = $this->todayTripsMeta;
        }

        return $data;
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
}
