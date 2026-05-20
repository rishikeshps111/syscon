<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
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
            'complaint_date' => ['required', 'date'],
            'reported_by_role' => ['required', Rule::in(['controller', 'supervisor'])],
            'reported_by_user_id' => ['required', 'integer', 'exists:users,id'],
            'against_role' => ['required', Rule::in(['driver', 'controller'])],
            'against_user_id' => ['required', 'integer', 'exists:users,id'],
            'complaint_category_id' => ['required', 'integer', 'exists:complaint_categories,id'],
            'description' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'],
            'severity' => ['required', Rule::in(['low', 'medium', 'high'])],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateHierarchy($validator);
            $this->validateUserRole($validator, 'reported_by_user_id', $this->input('reported_by_role'));
            $this->validateUserRole($validator, 'against_user_id', $this->input('against_role'));
        });
    }

    private function validateHierarchy($validator): void
    {
        if ($this->input('reported_by_role') === 'controller' && $this->input('against_role') !== 'driver') {
            $validator->errors()->add('against_role', 'Controller can raise complaints only against Driver.');
        }
    }

    private function validateUserRole($validator, string $field, ?string $role): void
    {
        if (! $this->filled($field) || ! $role) {
            return;
        }

        $roleName = ucfirst($role);
        $hasRole = User::whereKey($this->input($field))->role($roleName)->exists();

        if (! $hasRole) {
            $validator->errors()->add($field, 'Selected user does not match the selected role.');
        }
    }
}
