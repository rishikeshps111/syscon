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
            $roleIds = collect($this->input('role_ids', []))->filter()->map(fn ($id) => (int) $id);
            $roleId = (int) $roleIds->first();
            $role = Role::whereKey($roleId)->where('guard_name', 'web')->first();
            $designationIds = collect($this->input('designation_ids', []))->filter()->map(fn ($id) => (int) $id);

            if ($role && $designationIds->isEmpty()) {
                $validator->errors()->add('designation_ids', 'Select a designation for the selected role.');
            }

            if ($role && $designationIds->isNotEmpty()) {
                $matchingCount = \App\Models\Designation::query()
                    ->whereIn('id', $designationIds)
                    ->where('role_type', $role->name)
                    ->count();

                if ($matchingCount !== $designationIds->count()) {
                    $validator->errors()->add('designation_ids', 'The selected designation must belong to the selected role.');
                }
            }

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
                Rule::exists('roles', 'id')->where(fn ($query) => $query->whereIn('name', ['Staff', 'Driver', 'Controller', 'Supervisor', 'Housekeeping'])),
            ],
            'designation_ids' => ['required', 'array', 'size:1'],
            'designation_ids.*' => ['integer', 'exists:designations,id'],
            'component_name' => [
                'required',
                'string',
                'max:255',
            ],
            'type' => ['required', Rule::in(['earning', 'deduction'])],
        ];
    }
}
