<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'depot_id' => ['required', 'integer', 'exists:depots,id'],
            'vehicle_classification_id' => ['required', 'integer', 'exists:vehicle_classifications,id'],
            'trip_nature_id' => ['required', 'integer', 'exists:trip_natures,id'],
            'rounds_per_trip' => ['required', 'integer', 'min:1'],
            'schedule_km' => ['required', 'numeric', 'min:0'],
            'total_trips' => ['required', 'integer', 'min:1'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
