<?php

namespace App\Http\Requests;

use App\Models\Roster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRosterRequest extends FormRequest
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
            'duty_date' => ['required', 'date'],
            'shift_type' => ['required', Rule::in(array_keys(Roster::SHIFT_TYPES))],
            'shift_start_time' => ['required', 'date_format:H:i'],
            'shift_end_time' => ['required', 'date_format:H:i'],
            'trip_sheet_entry_id' => ['required_without:trip_sheet_entry_ids', 'nullable', 'integer', 'exists:trip_sheet_entries,id'],
            'trip_sheet_entry_ids' => ['required_without:trip_sheet_entry_id', 'nullable', 'array', 'min:1'],
            'trip_sheet_entry_ids.*' => ['integer', 'distinct', 'exists:trip_sheet_entries,id'],
            'driver_profile_id' => ['nullable', 'integer', 'exists:driver_profiles,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'supervisor_profile_id' => ['nullable', 'integer', 'exists:supervisor_profiles,id'],
            'controller_profile_id' => ['nullable', 'integer', 'exists:controller_profiles,id'],
            'reporting_time' => ['nullable', 'date_format:H:i'],
            'reporting_to_time' => ['nullable', 'date_format:H:i'],
            'remarks' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(array_keys(Roster::STATUSES))],
            'attendance_status' => ['nullable', Rule::in(array_keys(Roster::ATTENDANCE_STATUSES))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status', 'assigned'),
        ]);
    }
}
