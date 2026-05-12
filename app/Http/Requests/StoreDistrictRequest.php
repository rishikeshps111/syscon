<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDistrictRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('districts', 'name')->where(fn ($query) => $query->where('state_id', $this->state_id)),
            ],
            'is_active' => ['required', 'boolean'],
            'is_default' => ['required', 'boolean'],
        ];
    }
}
