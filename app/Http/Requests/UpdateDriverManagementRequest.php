<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateDriverManagementRequest extends StoreDriverManagementRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $userId = $this->route('driver_management')?->id;
        $profileId = $this->route('driver_management')?->driverProfile?->id;

        $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)];
        $rules['passcode'] = ['nullable', 'digits:6'];
        $rules['aadhaar_number'] = ['required', 'string', 'max:20', Rule::unique('driver_profiles', 'aadhaar_number')->ignore($profileId)];
        $rules['license_number'] = ['required', 'string', 'max:50', Rule::unique('driver_profiles', 'license_number')->ignore($profileId)];

        return $rules;
    }
}
