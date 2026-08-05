<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepotRequest extends FormRequest
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
            'district_id' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')->where(fn ($query) => $query->where('state_id', $this->state_id)),
            ],
            'location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where(fn ($query) => $query
                    ->where('state_id', $this->state_id)
                    ->where('district_id', $this->district_id)),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('depots', 'name')
                    ->where(fn ($query) => $query
                        ->where('state_id', $this->state_id)
                        ->where('district_id', $this->district_id)
                        ->where('location_id', $this->location_id))
                    ->ignore($this->route('depot')),
            ],
            'short_name' => ['required', 'string', 'max:50'],
            'branch_location_ids' => ['nullable', 'array'],
            'branch_location_ids.*' => [
                'integer',
                'distinct',
                'exists:branch_locations,id',
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
