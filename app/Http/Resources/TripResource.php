<?php

namespace App\Http\Resources;

use App\Models\Roster;
use App\Models\Trip;
use App\Models\TripSheet;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TripResource extends JsonResource
{
    private bool $includeDetails = false;

    public function withDetails(): self
    {
        $this->includeDetails = true;

        return $this;
    }

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

        $data = [
            'id' => $this->id,
            'trip_sheet_id' => $sheet?->id,
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
            'halt_time' => $trip?->halt_time
                ? \Carbon\CarbonInterval::seconds(
                    \Carbon\Carbon::parse($trip->halt_time)->diffInSeconds(\Carbon\Carbon::parse('00:00:00'))
                )->cascade()->forHumans([
                    'parts' => 2,
                ])
                : null,
            'is_driver_verified' => $this->is_driver_verified,
            'driver_verified_by' => $this->driverVerifiedBy?->name,
            'driver_verified_at' => $this->formatDateTime($this->driver_verified_at),
            'is_vehicle_verified' => $this->is_vehicle_verified,
            'vehicle_verified_by' => $this->vehicle_verified_by,
            'vehicle_verified_at' => $this->formatDateTime($this->vehicle_verified_at),
            'is_verified_by_supervisor' => $this->is_verified_by_supervisor,
            'verified_by_supervisor' => $this->verified_by_supervisor,
            'verified_by_supervisor_at' => $this->formatDateTime($this->verified_by_supervisor_at),
            'is_verified_by_controller' => $this->is_verified_by_controller,
            'verified_by_controller' => $this->verified_by_controller,
            'verified_by_controller_at' => $this->formatDateTime($this->verified_by_controller_at),
            'notes' => $this->notes,
        ];

        if ($this->includeDetails) {
            $data += [
                'trip_details' => $this->tripDetails(),
                'dor_details' => $this->dorDetails(),
                'driver_details' => $this->driverDetails(),
                'vehicle_details' => $this->vehicleDetails(),
            ];
        }

        return $data;
    }

    private function tripDetails(): array
    {
        $sheet = $this->sheet;
        $trip = $sheet?->trip;
        $route = $trip?->route;
        $side = strtolower((string) $this->side);

        return [
            'trip_sheet_entry_id' => $this->id,
            'trip_sheet_id' => $sheet?->id,
            'trip_sheet_code' => $sheet?->code,
            'trip_sheet_status' => $sheet?->status,
            'trip_sheet_status_label' => $this->tripSheetStatusLabel($sheet?->status),
            'trip_date' => $this->formatDate($sheet?->date),
            'trip_id' => $trip?->id,
            'trip_code' => $trip?->code,
            'trip_title' => $trip?->trip_title,
            'service_type' => $trip?->serviceType?->name,
            'route_id' => $route?->id,
            'route_name' => $route?->route_name,
            'starting_point' => $side === 'down' ? $route?->endPoint?->name : $route?->startPoint?->name,
            'ending_point' => $side === 'down' ? $route?->startPoint?->name : $route?->endPoint?->name,
            'depot_id' => $trip?->depot?->id,
            'depot_name' => $trip?->depot?->name,
            'side' => $this->side,
            'side_label' => ucfirst((string) $this->side),
            'scheduled_start_time' => $this->formatTime($trip?->start_time),
            'scheduled_end_time' => $this->formatTime($trip?->end_time),
            'departure_time' => $this->formatTime($this->departure_time),
            'arrival_time' => $this->formatTime($this->arrival_time),
            'actual_start_time' => $this->formatTime($this->actual_start_time),
            'actual_end_time' => $this->formatTime($this->actual_reach_time),
            'trip_side' => $trip?->trip_side,
            'trip_side_label' => $trip ? (Trip::TRIP_SIDES[$trip->trip_side] ?? $trip->trip_side) : null,
            'from_date' => $this->formatDate($trip?->from_date),
            'to_date' => $this->formatDate($trip?->to_date),
            'status' => $trip?->status,
            'notes' => $this->notes,
        ];
    }

    private function dorDetails(): ?array
    {
        $dor = $this->dor;

        if (! $dor) {
            return null;
        }

        return [
            'id' => $dor->id,
            'depot_name' => $dor->depot_name,
            'dor_date' => $this->formatDate($dor->dor_date),
            'bus_no' => $dor->bus_no,
            'route_no' => $dor->route_no,
            'duty' => $dor->duty,
            'shift' => $dor->shift,
            'driver_badge_no' => $dor->driver_badge_no,
            'schedule_start_time' => $this->formatTime($dor->schedule_start_time),
            'schedule_end_time' => $this->formatTime($dor->schedule_end_time),
            'actual_start_time' => $this->formatTime($dor->actual_start_time),
            'actual_end_time' => $this->formatTime($dor->actual_end_time),
            'start_punc' => $dor->start_punc,
            'route_completion_time' => $dor->route_completion_time,
            'schedule_km' => $dor->schedule_km,
            'route_km_loss' => $dor->route_km_loss,
            'actual_route_km' => $dor->actual_route_km,
            'schedule_trip' => $dor->schedule_trip,
            'actual_trip' => $dor->actual_trip,
            'miss_trip' => $dor->miss_trip,
            'odometer_start_reading' => $dor->odometer_start_reading,
            'odometer_start_image_url' => $this->storageUrl($dor->odometer_start_image_path),
            'odometer_end_reading' => $dor->odometer_end_reading,
            'odometer_end_image_url' => $this->storageUrl($dor->odometer_end_image_path),
            'odometer_diff_km' => $dor->odometer_diff_km,
            'difference' => $dor->difference,
            'account_responsible' => $dor->account_responsible,
            'reason_for_kilometer_loss' => $dor->reason_for_kilometer_loss,
            'after_sales_reason' => $dor->after_sales_reason,
            'penalty_infraction' => $dor->penalty_infraction,
            'remarks' => $dor->remarks,
            'route_start_soc_percent' => $dor->route_start_soc_percent,
            'route_end_soc_percent' => $dor->route_end_soc_percent,
            'soc_consumption_on_route_percent' => $dor->soc_consumption_on_route_percent,
            'soc_per_km' => $dor->soc_per_km,
            'run_kilometer_per_soc' => $dor->run_kilometer_per_soc,
            'dor_kwh_per_km_odo' => $dor->dor_kwh_per_km_odo,
            'dor_kwh_per_km_act' => $dor->dor_kwh_per_km_act,
            'dcr_kwh_per_km_odo' => $dor->dcr_kwh_per_km_odo,
            'dcr_kwh_per_km_act' => $dor->dcr_kwh_per_km_act,
            'dor_kwh' => $dor->dor_kwh,
            'dcr_kwh' => $dor->dcr_kwh,
            'dcr_charged_soc' => $dor->dcr_charged_soc,
            'energy_absorption' => $dor->energy_absorption,
            'battery_size_kwh' => $dor->battery_size_kwh,
            'vp1' => $dor->vp1,
            'vp2' => $dor->vp2,
            'dp' => $dor->dp,
            'penalty' => $dor->penalty,
            'model_9m_12m' => $dor->model_9m_12m,
        ];
    }

    private function driverDetails(): ?array
    {
        $driver = $this->driverProfile;
        $user = $driver?->user;

        if (! $driver) {
            return null;
        }

        return [
            'id' => $driver->id,
            'user_id' => $user?->id,
            'code' => $user?->code,
            'name' => $user?->name,
            'email' => $user?->email,
            'phone' => $user?->full_phone,
            'avatar_url' => $user?->avatar_url,
            'alternate_phone' => trim(($driver->alternate_country_code ?? '') . ' ' . ($driver->alternate_phone ?? '')) ?: null,
            'aadhaar_number' => $driver->aadhaar_number,
            'license_number' => $driver->license_number,
            'license_type' => $driver->license_type,
            'license_type_label' => $driver->license_type_label,
            'license_issue_date' => $this->formatDate($driver->issue_date),
            'license_expiry_date' => $this->formatDate($driver->expiry_date),
            'badge_number' => $driver->badge_number,
            'badge_expiry_date' => $this->formatDate($driver->badge_expiry_date),
            'employment_type' => $driver->employment_type,
            'employment_type_label' => $driver->employment_type_label,
            'joining_date' => $this->formatDate($driver->joining_date),
            'depot_name' => $driver->depot?->name,
            'branch_location_name' => $driver->branchLocation?->name,
            'state_name' => $driver->state?->name,
            'district_name' => $driver->district?->name,
            'location_name' => $driver->location?->name,
            'pincode' => $driver->pincode,
            'address' => $driver->address,
            'emergency_contact_name' => $driver->emergency_contact_name,
            'emergency_contact_no' => trim(($driver->emergency_country_code ?? '') . ' ' . ($driver->emergency_contact_no ?? '')) ?: null,
            'medical_fitness_expiry' => $this->formatDate($driver->medical_fitness_expiry),
            'police_verification_status' => $driver->police_verification_status,
            'police_verification_status_label' => $driver->police_verification_status_label,
            'verification_status' => $driver->verification_status,
            'verification_status_label' => $driver->verification_status_label,
            'entry_is_driver_verified' => $this->is_driver_verified,
            'entry_driver_verified_by' => $this->driver_verified_by,
            'entry_driver_verified_at' => $this->formatDateTime($this->driver_verified_at),
        ];
    }

    private function vehicleDetails(): ?array
    {
        $vehicle = $this->vehicle;

        if (! $vehicle) {
            return null;
        }

        return [
            'id' => $vehicle->id,
            'vehicle_code' => $vehicle->vehicle_code,
            'vehicle_no' => $vehicle->vehicle_no,
            'vehicle_type' => $vehicle->vehicle_type,
            'fuel_type' => $vehicle->fuel_type,
            'vehicle_category' => $vehicle->vehicle_category,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'variant' => $vehicle->variant,
            'capacity_seating' => $vehicle->capacity_seating,
            'capacity_load' => $vehicle->capacity_load,
            'battery_capacity' => $vehicle->battery_capacity,
            'range_km' => $vehicle->range_km,
            'engine_no' => $vehicle->engine_no,
            'chassis_no' => $vehicle->chassis_no,
            'registration_date' => $this->formatDate($vehicle->registration_date),
            'registration_valid_upto' => $this->formatDate($vehicle->registration_valid_upto),
            'fitness_expiry' => $this->formatDate($vehicle->fitness_expiry),
            'permit_expiry' => $this->formatDate($vehicle->permit_expiry),
            'insurance_expiry' => $this->formatDate($vehicle->insurance_expiry),
            'pollution_expiry' => $this->formatDate($vehicle->pollution_expiry),
            'gps_enabled' => $vehicle->gps_enabled,
            'gps_imei' => $vehicle->gps_imei,
            'state_name' => $vehicle->state?->name,
            'oem_name' => $vehicle->oem?->oem_name,
            'depot_name' => $vehicle->depot?->name,
            'branch_name' => $vehicle->branch?->name,
            'status' => $vehicle->status,
            'is_verified' => $vehicle->is_verified,
            'remarks' => $vehicle->remarks,
            'entry_starting_km' => $this->starting_km,
            'entry_starting_electric_charge' => $this->starting_electric_charge,
            'entry_vehicle_condition' => $this->vehicle_condition,
            'entry_is_vehicle_verified' => $this->is_vehicle_verified,
            'entry_vehicle_verified_by' => $this->vehicle_verified_by,
            'entry_vehicle_verified_at' => $this->formatDateTime($this->vehicle_verified_at),
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

    private function formatDateTime($value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('d M Y h:i a');
        }

        return Carbon::parse($value)->format('d M Y h:i a');
    }

    private function storageUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
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
