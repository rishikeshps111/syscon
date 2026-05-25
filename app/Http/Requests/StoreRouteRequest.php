<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Location;

class StoreRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function prepareForValidation(): void
    {
        $startLocation = $this->filled('start_point_id')
            ? Location::find($this->input('start_point_id'))
            : null;

        $routeType = $this->input('route_type');

        $this->merge([
            'district_id' => $this->input('district_id') ?: $startLocation?->district_id,
            'route_name' => $this->input('route_name') ?: $this->input('name'),
            'total_distance_km' => $this->input('total_distance_km') ?: $this->input('distance'),
            'route_type' => strtolower((string) $routeType) === 'intercity' ? 'Intercity' : $routeType,
            'route_category' => $this->input('route_category') ?: 'Passenger',
            'status' => $this->input('status') ?: ($this->has('is_active') ? ($this->boolean('is_active') ? 'Active' : 'Inactive') : null),
        ]);
    }

    public function rules(): array
    {
        return [
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'route_name' => ['required', 'string', 'max:255'],
            'start_point_id' => ['required', 'integer', 'exists:locations,id', 'different:end_point_id'],
            'end_point_id' => ['required', 'integer', 'exists:locations,id'],
            'total_distance_km' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'estimated_duration' => ['required', 'date_format:H:i'],
            'route_type' => ['required', Rule::in(['Intercity', 'Intracity'])],
            'route_category' => ['required', Rule::in(['Passenger', 'Cargo'])],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
