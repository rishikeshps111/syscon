<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StoreSalaryComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->baseRules();
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $staffRole = Role::where('name', 'Staff')->where('guard_name', 'web')->first();

            if ($staffRole && in_array($staffRole->id, $this->input('role_ids', [])) && empty($this->input('designation_ids', []))) {
                $validator->errors()->add('designation_ids', 'Select at least one designation when Staff role is selected.');
            }

            $roleId = (int) collect($this->input('role_ids', []))->first();
            $designationId = (int) collect($this->input('designation_ids', []))->first() ?: null;

            if (! $roleId || ! $this->filled('component_name')) {
                return;
            }

            $duplicateExists = \App\Models\SalaryComponent::query()
                ->where('component_name', $this->input('component_name'))
                ->when($this->route('salary_component'), fn ($query, $component) => $query->whereKeyNot($component->getKey()))
                ->whereHas('assignments', function ($query) use ($roleId, $designationId) {
                    $query->where('role_id', $roleId)
                        ->where('designation_id', $designationId);
                })
                ->exists();

            if ($duplicateExists) {
                $validator->errors()->add('component_name', 'This salary component already exists for the selected role and designation.');
            }
        });
    }

    protected function baseRules(): array
    {
        return [
            'role_ids' => ['required', 'array', 'size:1'],
            'role_ids.*' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->whereIn('name', ['Staff', 'Driver', 'Controller', 'Supervisor'])),
            ],
            'designation_ids' => ['nullable', 'array', 'max:1'],
            'designation_ids.*' => ['integer', 'exists:designations,id'],
            'component_name' => [
                'required',
                'string',
                'max:255',
            ],
            'type' => ['required', Rule::in(['earning', 'deduction'])],
            'is_applicable' => ['required', 'boolean'],
            'calculation_type' => ['required', Rule::in(['fixed', 'percentage', 'per_shift', 'per_trip', 'formula'])],
            'default_value' => ['required', 'numeric', 'min:0'],
            'is_editable_in_payroll' => ['required', 'boolean'],
            'is_mandatory' => ['required', 'boolean'],
        ];
    }
}
