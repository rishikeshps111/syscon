<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripRequest extends FormRequest
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
            'service_type_id' => ['required', 'integer', 'exists:service_types,id'],
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'depot_id' => ['nullable', 'integer', 'exists:depots,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'schedule_type' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'halt_time' => ['nullable', 'integer', 'min:0', 'max:1439'],
            'trip_side' => ['required', Rule::in(['up', 'down', 'both'])],
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'status' => ['nullable', Rule::in(['Active', 'Inactive', 'Cancelled'])],
            'notes' => ['nullable', 'string'],
            'cancellation_reason' => ['nullable', 'required_if:status,Cancelled', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'schedule_type' => $this->input('schedule_type', 'daily'),
            'status' => $this->input('status', $this->boolean('is_active', true) ? 'Active' : 'Inactive'),
            'is_active' => $this->input('is_active', true),
        ]);
    }
}
