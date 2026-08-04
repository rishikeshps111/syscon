<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'oem_id' => ['required', 'integer', 'exists:oems,id'],
            'depot_id' => ['required', 'integer', 'exists:depots,id'],
            'branch_id' => ['required', 'integer', 'exists:branch_locations,id'],
            'vehicle_no' => ['required', 'string', 'max:20', 'unique:vehicles,vehicle_no'],
            'vehicle_type' => ['required', Rule::in(array_keys(Vehicle::TYPES))],
            'fuel_type' => ['required', Rule::in(array_keys(Vehicle::FUEL_TYPES))],
            'vehicle_classification_id' => ['required', 'integer', 'exists:vehicle_classifications,id'],
            'vehicle_category' => ['required', Rule::in(array_keys(Vehicle::CATEGORIES))],
            'make' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'variant' => ['nullable', 'string', 'max:255'],
            'capacity_seating' => ['nullable', 'integer', 'min:0'],
            'capacity_load' => ['nullable', 'numeric', 'min:0'],
            'battery_capacity' => ['nullable', 'numeric', 'min:0', 'required_if:fuel_type,ELECTRIC'],
            'range_km' => ['nullable', 'integer', 'min:0', 'required_if:fuel_type,ELECTRIC'],
            'engine_no' => ['nullable', 'string', 'max:255'],
            'chassis_no' => ['required', 'string', 'max:255', 'unique:vehicles,chassis_no'],
            'registration_date' => ['nullable', 'date'],
            'registration_valid_upto' => ['nullable', 'date', 'after_or_equal:registration_date'],
            'fitness_expiry' => ['nullable', 'date'],
            'permit_expiry' => ['nullable', 'date'],
            'insurance_expiry' => ['nullable', 'date'],
            'pollution_expiry' => ['nullable', 'date'],
            'gps_enabled' => ['nullable', 'boolean'],
            'gps_imei' => ['nullable', 'string', 'max:255', 'required_if:gps_enabled,1'],
            'status' => ['required', Rule::in(array_keys(Vehicle::STATUSES))],
            'remarks' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'gps_enabled' => $this->boolean('gps_enabled'),
            'vehicle_no' => strtoupper((string) $this->input('vehicle_no')),
            'chassis_no' => strtoupper((string) $this->input('chassis_no')),
            'engine_no' => $this->input('engine_no') ? strtoupper((string) $this->input('engine_no')) : null,
        ]);
    }
}
