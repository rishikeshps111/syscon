<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateSupervisorManagementRequest extends StoreSupervisorManagementRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $userId = $this->route('supervisor_management')?->id;

        $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)];
        $rules['password'] = ['nullable', 'string', 'min:8'];

        return $rules;
    }
}

