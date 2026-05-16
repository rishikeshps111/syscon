<?php

namespace App\Http\Requests;

use App\Models\StaffProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'country_code' => ['required', 'string', 'max:10'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'designation_id' => ['required', 'integer', 'exists:designations,id'],
            'category' => ['required', Rule::in(array_keys(StaffProfile::CATEGORIES))],
            'employment_type' => ['required', Rule::in(array_keys(StaffProfile::EMPLOYMENT_TYPES))],
            'father_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'aadhaar_number' => ['required', 'string', 'max:20'],
            'pan_number' => ['required', 'string', 'max:20'],
            'date_of_joining' => ['required', 'date'],
            'uan' => ['required', 'string', 'max:50'],
            'esic_wc' => ['required', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:100'],
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'bank_account_number' => ['required', 'string', 'max:50'],
            'ifsc_code' => ['required', 'string', 'max:20'],
            'basic' => ['required', 'numeric', 'min:0'],
            'vda' => ['required', 'numeric', 'min:0'],
            'basic_vda' => ['required', 'numeric', 'min:0'],
            'hra' => ['required', 'numeric', 'min:0'],
            'special_allowance' => ['required', 'numeric', 'min:0'],
            'conveyance_allowance' => ['required', 'numeric', 'min:0'],
            'bonus' => ['required', 'numeric', 'min:0'],
            'gross_salary' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
