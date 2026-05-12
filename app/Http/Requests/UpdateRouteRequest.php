<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRouteRequest extends FormRequest
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
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'start_point_id' => ['required', 'integer', 'exists:depots,id', 'different:end_point_id'],
            'end_point_id' => ['required', 'integer', 'exists:depots,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('routes', 'name')->ignore($this->route('route')),
            ],
            'distance' => ['required', 'integer', 'min:0'],
            'estimated_duration' => ['required', 'date_format:H:i'],
            'route_type' => ['required', Rule::in(['Intracity', 'intercity'])],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
