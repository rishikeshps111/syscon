<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateSalaryComponentRequest extends StoreSalaryComponentRequest
{
    protected function baseRules(): array
    {
        $salaryComponent = $this->route('salary_component');

        $rules = parent::baseRules();
        $rules['component_name'] = [
            'required',
            'string',
            'max:255',
            Rule::unique('salary_components', 'component_name')->ignore($salaryComponent),
        ];

        return $rules;
    }
}
