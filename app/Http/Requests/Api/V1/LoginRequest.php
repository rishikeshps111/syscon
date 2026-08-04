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

    public function messages(): array
    {
        $type = match ($this->input('type')) {
            'driver' => 'Driver',
            'controller' => 'Controller',
            'supervisor' => 'Supervisor',
            default => 'User',
        };

        return [
            'phone.required' => "{$type} code is required.",
            'phone.string' => "{$type} code must be a string.",
            'phone.max' => "{$type} code must not exceed 255 characters.",

            'passcode.required' => 'Passcode is required.',
            'passcode.digits' => 'Passcode must contain exactly 6 digits.',

            'type.required' => 'User type is required.',
            'type.in' => 'The selected user type is invalid.',
        ];
    }
}
