<?php

namespace App\Http\Requests;

use App\Models\HousekeepingProfile;
use App\Models\StaffProfile;
use App\Models\User;
use App\Support\StaffReportingManagers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUnifiedStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->filled('email') ? $this->input('email') : null,
            'reporting_to' => $this->filled('reporting_to') ? $this->input('reporting_to') : null,
        ]);
    }

    public function rules(): array
    {
        $user = $this->route('staff_management');
        $userId = $user instanceof User ? $user->id : null;
        $creating = $this->isMethod('post');
        $role = (string) $this->input('role');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'country_code' => ['required', 'string', 'max:10'],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($userId)],
            'ref_code' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::in(['Staff', 'Housekeeping', 'Controller', 'Supervisor'])],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'password' => [Rule::requiredIf($creating && $role === 'Staff'), 'nullable', 'string', 'min:8'],
            'passcode' => [Rule::requiredIf($creating && in_array($role, ['Controller', 'Supervisor'], true)), 'nullable', 'digits:6'],
            'depot_id' => ['required', 'integer', 'exists:depots,id'],
            'designation_id' => [Rule::requiredIf($role === 'Staff'), 'nullable', 'integer', 'exists:designations,id'],
            'reporting_to' => [
                'nullable',
                'integer',
                'exists:users,id',
                Rule::notIn(array_filter([$userId])),
                function (string $attribute, mixed $value, \Closure $fail) use ($role, $userId): void {
                    if (! $value || ! $this->filled('depot_id')) {
                        return;
                    }

                    $query = match ($role) {
                        'Staff' => $this->filled('designation_id')
                            ? StaffReportingManagers::query((int) $this->designation_id, (int) $this->depot_id, $userId)
                            : User::query()->whereRaw('1 = 0'),
                        'Controller', 'Housekeeping' => User::role('Supervisor')->where('users.is_active', true)->whereHas(
                            'supervisorProfile',
                            fn ($profile) => $profile->where('depot_id', $this->depot_id)
                        ),
                        'Supervisor' => User::query()->whereRaw('1 = 0'),
                        default => User::query()->whereRaw('1 = 0'),
                    };

                    if (! $query->whereKey($value)->exists()) {
                        $fail('The selected reporting user is not eligible for this role, depot, and designation.');
                    }
                },
            ],
            'category' => ['nullable', Rule::in(array_keys(StaffProfile::CATEGORIES))],
            'employment_type' => [
                'required',
                Rule::in(array_keys($role === 'Housekeeping' ? HousekeepingProfile::EMPLOYMENT_TYPES : StaffProfile::EMPLOYMENT_TYPES)),
            ],
            'father_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'aadhaar_number' => ['required', 'string', 'max:20'],
            'pan_number' => ['required', 'string', 'max:20'],
            'date_of_joining' => ['required', 'date'],
            'uan' => ['required', 'string', 'max:50'],
            'esic_wc' => ['required', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:100'],
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')->where(fn ($query) => $query->where('state_id', $this->input('state_id')))],
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')->where(fn ($query) => $query->where('state_id', $this->input('state_id'))->where('district_id', $this->input('district_id')))],
            'bank_account_number' => ['required', 'string', 'max:50'],
            'ifsc_code' => ['required', 'string', 'max:20'],
            'salary_components' => ['nullable', 'array'],
            'salary_components.*' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
