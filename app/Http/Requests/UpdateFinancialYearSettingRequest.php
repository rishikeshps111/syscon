<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateFinancialYearSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'financial_year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'financial_year_from_month' => ['required', 'integer', 'between:1,12'],
            'financial_year_to_year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'financial_year_to_month' => ['required', 'integer', 'between:1,12'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $from = ((int) $this->input('financial_year') * 100)
                + (int) $this->input('financial_year_from_month');
            $to = ((int) $this->input('financial_year_to_year') * 100)
                + (int) $this->input('financial_year_to_month');

            if ($to < $from) {
                $validator->errors()->add('financial_year_to_month', 'The to year and month must be after the from year and month.');
            }
        });
    }
}
