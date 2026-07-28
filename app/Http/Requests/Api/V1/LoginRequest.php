<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $merge = [];

        if (! $this->filled('passcode') && $this->filled('password')) {
            $merge['passcode'] = $this->input('password');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:255'],
            'passcode' => ['required', 'digits:6'],
            'type' => ['required', 'string', Rule::in(['driver', 'controller', 'supervisor'])],
            'device_name' => ['nullable', 'string', 'max:255'],
            'fcm_token' => ['nullable', 'string', 'max:4096'],
            'platform' => ['nullable', 'string', Rule::in(['android', 'ios', 'web'])],
        ];
    }
}
