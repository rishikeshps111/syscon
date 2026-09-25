<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDesignationRequest extends FormRequest
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
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'level_id' => ['required', 'integer', 'exists:levels,id'],
            'reporting_to' => [
                'nullable',
                'integer',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->whereIn('name', ['Staff', 'Driver', 'Controller', 'Supervisor'])),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('designations', 'name')->ignore($this->route('designation')),
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query->where('guard_name', 'web'))
                    ->ignore($this->route('designation')?->role_id),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
