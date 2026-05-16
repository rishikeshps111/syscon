<?php

namespace App\Exports;

use App\Models\HrmsDocumentType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class HrmsDocumentTypeExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: HrmsDocumentType::select(
            'code',
            'name',
            'category',
            'applicable_for',
            'allowed_file_types',
            'is_mandatory',
            'is_expiry_required',
            'is_active',
            'description',
            'created_at'
        );
    }

    public function collection()
    {
        return $this->query->get()->map(function ($documentType) {
            return [
                'Code' => $documentType->code,
                'Document Type' => $documentType->name,
                'Category' => $documentType->category,
                'Mandatory' => $documentType->is_mandatory ? 'Yes' : 'No',
                'Expiry' => $documentType->is_expiry_required ? 'Yes' : 'No',
                'Applicable For' => HrmsDocumentType::APPLICABLE_FOR[$documentType->applicable_for] ?? '',
                'Allowed File Type' => HrmsDocumentType::ALLOWED_FILE_TYPES[$documentType->allowed_file_types] ?? '',
                'Status' => $documentType->is_active ? 'Active' : 'Inactive',
                'Description' => $documentType->description,
                'Created At' => $documentType->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Document Type',
            'Category',
            'Mandatory',
            'Expiry',
            'Applicable For',
            'Allowed File Type',
            'Status',
            'Description',
            'Created At',
        ];
    }
}
