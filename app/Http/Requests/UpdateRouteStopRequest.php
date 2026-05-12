<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRouteStopRequest extends FormRequest
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
        $routeStop = $this->route('route_stop');

        return [
            'name' => ['required', 'string', 'max:255'],
            'expected_reach_time' => ['nullable', 'date_format:H:i'],
            'position' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('route_stops', 'position')
                    ->where('route_id', $routeStop->route_id)
                    ->ignore($routeStop),
            ],
        ];
    }
}
