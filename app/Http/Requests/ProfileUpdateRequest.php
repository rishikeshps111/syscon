<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'country_code' => [
                'nullable',
                'string',
                Rule::in(['+91', '+1', '+44', '+61', '+971', '+65', '+60', '+81', '+49', '+33']),
            ],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\s\-()]+$/'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
