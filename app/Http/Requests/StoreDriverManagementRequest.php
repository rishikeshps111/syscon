<?php

namespace App\Http\Requests;

use App\Models\DriverProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDriverManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'max:10'],
            'phone' => ['required', 'string', 'max:30'],
            'alternate_country_code' => ['nullable', 'string', 'max:10'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['required', 'boolean'],

            'aadhaar_number' => ['required', 'string', 'max:20', 'unique:driver_profiles,aadhaar_number'],
            'country' => ['required', 'string', 'max:100'],
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'district_id' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')->where(fn ($query) => $query->where('state_id', $this->input('state_id'))),
            ],
            'location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where(fn ($query) => $query
                    ->where('state_id', $this->input('state_id'))
                    ->where('district_id', $this->input('district_id'))),
            ],
            'pincode' => ['required', 'string', 'max:10'],
            'address' => ['required', 'string', 'max:1000'],

            'license_number' => ['required', 'string', 'max:50', 'unique:driver_profiles,license_number'],
            'license_type' => ['required', Rule::in(array_keys(DriverProfile::LICENSE_TYPES))],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'badge_number' => ['nullable', 'string', 'max:50'],
            'badge_expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],

            'employment_type' => ['required', Rule::in(array_keys(DriverProfile::EMPLOYMENT_TYPES))],
            'joining_date' => ['required', 'date'],
            'salary' => ['required', 'numeric', 'min:0'],
            'depot_id' => ['required', 'integer', 'exists:depots,id'],
            'branch_location_id' => ['required', 'integer', 'exists:branch_locations,id'],

            'account_number' => ['required', 'string', 'max:50'],
            'ifsc_code' => ['required', 'string', 'max:20'],

            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_country_code' => ['required', 'string', 'max:10'],
            'emergency_contact_no' => ['required', 'string', 'max:30'],
            'medical_fitness_expiry' => ['required', 'date'],

            'police_verification_status' => ['required', Rule::in(array_keys(DriverProfile::VERIFICATION_STATUSES))],
            'verification_status' => ['required', Rule::in(array_keys(DriverProfile::VERIFICATION_STATUSES))],
        ];
    }
}
