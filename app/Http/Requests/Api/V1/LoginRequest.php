<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('code') && $this->filled('user_id')) {
            $this->merge([
                'code' => $this->input('user_id'),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required_without:user_id', 'string', 'max:255'],
            'user_id' => ['required_without:code', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'type' => ['required', 'string', Rule::in(['driver', 'controller', 'supervisor'])],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
