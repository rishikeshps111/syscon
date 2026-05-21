<?php

namespace App\Http\Requests;

use App\Models\Oem;
use App\Models\OemAddress;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOemRequest extends FormRequest
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
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'oem_name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:100'],
            'oem_type' => [
                'required',
                Rule::exists('oem_types', 'name')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'registration_type' => ['required', Rule::in(array_keys(Oem::REGISTRATION_TYPES))],
            'gst_number' => ['required', 'string', 'max:20'],
            'pan_number' => ['required', 'string', 'max:15'],
            'cin_number' => ['nullable', 'string', 'max:25'],
            'remarks' => ['nullable', 'string'],
            'contacts' => ['required', 'array', 'min:1'],
            'contacts.*.contact_person' => ['required', 'string', 'max:255'],
            'contacts.*.designation' => ['nullable', 'string', 'max:255'],
            'contacts.*.phone_country_code' => ['nullable', 'string', 'max:5'],
            'contacts.*.phone' => ['required', 'string', 'max:20'],
            'contacts.*.alternate_phone_country_code' => ['nullable', 'string', 'max:5'],
            'contacts.*.alternate_phone' => ['nullable', 'string', 'max:20'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'addresses' => ['required', 'array', 'min:1'],
            'addresses.*.address_type' => ['required', Rule::in(array_keys(OemAddress::ADDRESS_TYPES))],
            'addresses.*.state_id' => ['nullable', 'integer', 'exists:states,id'],
            'addresses.*.district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'addresses.*.city_id' => ['nullable', 'integer', 'exists:locations,id'],
            'addresses.*.address_line1' => ['required', 'string', 'max:255'],
            'addresses.*.address_line2' => ['nullable', 'string', 'max:255'],
            'addresses.*.pincode' => ['nullable', 'string', 'max:10'],
            'addresses.*.latitude' => ['nullable', 'string', 'max:20'],
            'addresses.*.longitude' => ['nullable', 'string', 'max:20'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $contacts = collect($this->input('contacts', []))
            ->map(function ($contact, $index) {
                $contact['is_primary'] = (string) $index === (string) $this->input('primary_contact_index');

                return $contact;
            })
            ->all();

        $this->merge([
            'contacts' => $contacts,
        ]);
    }
}
