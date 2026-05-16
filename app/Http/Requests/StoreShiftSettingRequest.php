<?php

namespace App\Http\Requests;

use App\Models\ShiftSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number_of_shifts_per_day' => ['nullable', 'integer', 'in:2'],
            'shift_name' => ['required', Rule::in(ShiftSetting::SHIFT_NAMES)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_duration_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'total_working_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'grace_time_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'minimum_working_hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'check_in_window_start' => ['nullable', 'boolean'],
            'check_in_window_end' => ['nullable', 'boolean'],
            'check_out_flexibility' => ['nullable', 'boolean'],
            'enable_overtime' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'number_of_shifts_per_day' => 2,
            'check_in_window_start' => $this->boolean('check_in_window_start'),
            'check_in_window_end' => $this->boolean('check_in_window_end'),
            'check_out_flexibility' => $this->boolean('check_out_flexibility'),
        ]);
    }
}
