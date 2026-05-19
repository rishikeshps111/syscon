<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateControllerManagementRequest extends StoreControllerManagementRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $userId = $this->route('controller_management')?->id;

        $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)];
        $rules['password'] = ['nullable', 'string', 'min:8'];

        return $rules;
    }
}
