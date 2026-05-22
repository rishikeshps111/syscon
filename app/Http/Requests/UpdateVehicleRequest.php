<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends StoreVehicleRequest
{
    public function rules(): array
    {
        $vehicle = $this->route('vehicle');
        $rules = parent::rules();

        $rules['vehicle_no'] = [
            'required',
            'string',
            'max:20',
            Rule::unique('vehicles', 'vehicle_no')->ignore($vehicle),
        ];
        $rules['chassis_no'] = [
            'required',
            'string',
            'max:255',
            Rule::unique('vehicles', 'chassis_no')->ignore($vehicle),
        ];

        return $rules;
    }
}
