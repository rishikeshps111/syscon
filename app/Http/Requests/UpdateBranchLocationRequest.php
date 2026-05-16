<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchLocationRequest extends FormRequest
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
                Rule::unique('branch_locations', 'name')
                    ->where(fn ($query) => $query
                        ->where('state_id', $this->state_id)
                        ->where('district_id', $this->district_id)
                        ->where('location_id', $this->location_id))
                    ->ignore($this->route('branch_location')),
            ],
            'remarks' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ];
    }
}
