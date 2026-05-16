<?php

namespace App\Http\Requests;

use App\Models\HrmsDocumentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHrmsDocumentTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:hrms_document_types,name'],
            'category' => ['nullable', Rule::in(HrmsDocumentType::CATEGORIES)],
            'applicable_for' => ['nullable', Rule::in(array_keys(HrmsDocumentType::APPLICABLE_FOR))],
            'allowed_file_types' => ['nullable', Rule::in(array_keys(HrmsDocumentType::ALLOWED_FILE_TYPES))],
            'is_active' => ['required', 'boolean'],
            'is_mandatory' => ['required', 'boolean'],
            'is_expiry_required' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}
