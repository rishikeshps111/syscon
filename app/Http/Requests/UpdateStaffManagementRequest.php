<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateStaffManagementRequest extends StoreStaffManagementRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $userId = $this->route('staff_management')?->id;

        $rules['email'] = ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)];
        $rules['phone'] = ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($userId)];
        $rules['password'] = ['nullable', 'string', 'min:8'];
        $rules['reporting_to'][] = Rule::notIn([$userId]);

        return $rules;
    }
}
