<?php

namespace App\Http\Requests;

use App\Models\LeaveType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'leave_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('leave_types', 'leave_name')->ignore($this->route('leave_type')),
            ],
            'short_name' => [
                'required',
                'string',
                'max:20',
                Rule::unique('leave_types', 'short_name')->ignore($this->route('leave_type')),
            ],
            'leave_category' => ['required', Rule::in(LeaveType::CATEGORIES)],
            'max_leaves_per_year' => ['nullable', 'numeric', 'min:0'],
            'carry_forward_allowed' => ['required', 'boolean'],
            'max_carry_forward_limit' => ['nullable', 'required_if:carry_forward_allowed,1', 'numeric', 'min:0'],
            'encashment_allowed' => ['required', 'boolean'],
            'applicable_for' => ['required', Rule::in(array_keys(LeaveType::APPLICABLE_FOR))],
            'gender_specific' => ['required', Rule::in(array_keys(LeaveType::GENDERS))],
            'minimum_service_required' => ['nullable', 'string', 'max:255'],
            'minimum_leave_days' => ['required', 'numeric', 'min:0.5'],
            'maximum_leave_days_per_request' => ['nullable', 'numeric', 'min:0.5'],
            'advance_notice_days' => ['nullable', 'integer', 'min:0'],
            'allow_half_day' => ['required', 'boolean'],
            'requires_approval' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
