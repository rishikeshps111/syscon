<?php

namespace App\Http\Requests;

use App\Models\HousekeepingProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHousekeepingManagementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'country_code' => ['required', 'string', 'max:10'], 'phone' => ['required', 'string', 'max:30'],
            'alternate_country_code' => ['nullable', 'string', 'max:10'], 'alternate_phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'image', 'max:2048'], 'is_active' => ['required', 'boolean'],
            'aadhaar_number' => ['required', 'string', 'max:20', 'unique:housekeeping_profiles,aadhaar_number'],
            'country' => ['required', 'string', 'max:100'], 'state_id' => ['required', 'integer', 'exists:states,id'],
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')->where(fn ($q) => $q->where('state_id', $this->input('state_id')))],
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')->where(fn ($q) => $q->where('state_id', $this->input('state_id'))->where('district_id', $this->input('district_id')))],
            'pincode' => ['required', 'string', 'max:10'], 'address' => ['required', 'string', 'max:1000'],
            'employment_type' => ['required', Rule::in(array_keys(HousekeepingProfile::EMPLOYMENT_TYPES))],
            'joining_date' => ['required', 'date'], 'depot_id' => ['required', 'integer', 'exists:depots,id'],
            'branch_location_id' => ['required', 'integer', 'exists:branch_locations,id'],
            'salary_components' => ['nullable', 'array'], 'salary_components.*' => ['nullable', 'numeric', 'min:0'],
            'account_number' => ['required', 'string', 'max:50'], 'ifsc_code' => ['required', 'string', 'max:20'],
            'emergency_contact_name' => ['required', 'string', 'max:255'], 'emergency_country_code' => ['required', 'string', 'max:10'],
            'emergency_contact_no' => ['required', 'string', 'max:30'], 'medical_fitness_expiry' => ['required', 'date'],
            'police_verification_status' => ['required', Rule::in(array_keys(HousekeepingProfile::VERIFICATION_STATUSES))],
            'verification_status' => ['required', Rule::in(array_keys(HousekeepingProfile::VERIFICATION_STATUSES))],
        ];
    }
}
