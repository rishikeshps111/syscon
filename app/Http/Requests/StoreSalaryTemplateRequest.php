<?php

namespace App\Http\Requests;

use App\Models\SalaryComponent;
use App\Models\SalaryTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StoreSalaryTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->whereIn('name', ['Staff', 'Driver', 'Controller', 'Supervisor', 'Housekeeping'])),
            ],
            'designation_id' => ['required', 'integer', 'exists:designations,id'],
            'components' => ['required', 'array', 'min:1'],
            'components.*' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $role = Role::find($this->input('role_id'));
            $designationId = $this->integer('designation_id') ?: null;

            if ($role && $designationId && ! \App\Models\Designation::whereKey($designationId)->where('role_type', $role->name)->exists()) {
                $validator->errors()->add('designation_id', 'The selected designation must belong to the selected role.');
                return;
            }

            if (! $role) {
                return;
            }

            $duplicate = SalaryTemplate::query()
                ->where('role_id', $role->id)
                ->when($designationId, fn ($query) => $query->where('designation_id', $designationId), fn ($query) => $query->whereNull('designation_id'))
                ->when($this->route('salary_template'), fn ($query, $template) => $query->whereKeyNot($template->id))
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('role_id', 'A salary template already exists for this role and designation.');
            }

            $eligibleIds = SalaryComponent::query()
                ->whereHas('assignments', function ($query) use ($role, $designationId) {
                    $query->where('role_id', $role->id);
                    $query->where('designation_id', $designationId);
                })
                ->pluck('id');

            $invalid = collect(array_keys($this->input('components', [])))
                ->map(fn ($id) => (int) $id)
                ->diff($eligibleIds);

            if ($invalid->isNotEmpty()) {
                $validator->errors()->add('components', 'One or more selected salary components are not valid for this role and designation.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $role = Role::find($this->input('role_id'));
    }
}
