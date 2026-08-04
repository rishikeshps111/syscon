<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('locations', 'name')
                    ->where(fn ($query) => $query
                        ->where('state_id', $this->state_id)
                        ->where('district_id', $this->district_id)),
            ],
            'short_name' => ['required', 'string', 'max:50'],
            'pincode' => ['nullable', 'string', 'max:10', 'regex:/^[0-9]+$/'],
            'is_active' => ['required', 'boolean'],
            'is_default' => ['required', 'boolean'],
        ];
    }
}
