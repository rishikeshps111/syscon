<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:document_types,name'],
            'applicable_for' => ['nullable', Rule::in(['driver', 'vehicle', 'oem', 'supervisor', 'controller'])],
            'is_active' => ['required', 'boolean'],
            'is_mandatory' => ['required', 'boolean'],
            'is_expiry_required' => ['required', 'boolean'],
        ];
    }
}
