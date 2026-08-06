<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateHousekeepingManagementRequest extends StoreHousekeepingManagementRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $user = $this->route('housekeeping_management');
        $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)];
        $rules['aadhaar_number'] = ['required', 'string', 'max:20', Rule::unique('housekeeping_profiles', 'aadhaar_number')->ignore($user?->housekeepingProfile?->id)];
        return $rules;
    }
}
