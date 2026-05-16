<?php

namespace App\Http\Requests;

use App\Models\Holiday;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'holiday_name' => ['required', 'string', 'max:255'],
            'holiday_date' => ['required', 'date'],
            'holiday_type' => ['required', Rule::in(array_keys(Holiday::TYPES))],
            'applicable_location' => ['required', Rule::in(array_keys(Holiday::LOCATIONS))],
            'state_id' => ['nullable', 'required_if:applicable_location,state', 'integer', 'exists:states,id'],
            'branch_location_id' => ['nullable', 'required_if:applicable_location,branch', 'integer', 'exists:branch_locations,id'],
            'applicable_for' => ['required', Rule::in(array_keys(Holiday::APPLICABLE_FOR))],
            'department_ids' => ['nullable', 'required_if:applicable_for,specific_departments', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'designation_ids' => ['nullable', 'required_if:applicable_for,specific_designations', 'array'],
            'designation_ids.*' => ['integer', 'exists:designations,id'],
            'holiday_duration' => ['required', Rule::in(array_keys(Holiday::DURATIONS))],
            'is_recurring_yearly' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'state_id' => $this->applicable_location === 'state' ? $this->state_id : null,
            'branch_location_id' => $this->applicable_location === 'branch' ? $this->branch_location_id : null,
            'department_ids' => $this->applicable_for === 'specific_departments' ? ($this->department_ids ?? []) : null,
            'designation_ids' => $this->applicable_for === 'specific_designations' ? ($this->designation_ids ?? []) : null,
        ]);
    }
}
