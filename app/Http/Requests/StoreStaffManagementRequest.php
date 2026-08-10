<?php

namespace App\Http\Requests;

use App\Models\StaffProfile;
use App\Support\StaffReportingManagers;
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
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'country_code' => ['required', 'string', 'max:10'],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'password' => ['required', 'string', 'min:8'],
            'depot_id' => ['required', 'integer', 'exists:depots,id'],
            'designation_id' => ['required', 'integer', 'exists:designations,id'],
            'reporting_to' => [
                'nullable',
                'integer',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! $value) {
                        return;
                    }

                    $isEligible = StaffReportingManagers::query(
                        (int) $this->input('designation_id'),
                        (int) $this->input('depot_id'),
                        $this->route('staff_management')?->id,
                    )->whereKey($value)->exists();

                    if (! $isEligible) {
                        $fail('The selected reporting manager does not match the designation and depot.');
                    }
                },
            ],
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
            'bank_account_number' => ['required', 'string', 'max:50'],
            'ifsc_code' => ['required', 'string', 'max:20'],
            'salary_components' => ['nullable', 'array'],
            'salary_components.*' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
