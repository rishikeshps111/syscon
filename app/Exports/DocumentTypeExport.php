<?php

namespace App\Exports;

use App\Models\DocumentType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DocumentTypeExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: DocumentType::select('code', 'name', 'applicable_for', 'is_expiry_required', 'is_active', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($documentType) {
            return [
                'Code' => $documentType->code,
                'Document' => $documentType->name,
                'Applies To' => $this->formatApplicableFor($documentType->applicable_for),
                'Expiry' => $documentType->is_expiry_required ? 'Required' : 'Not Required',
                'Status' => $documentType->is_active ? 'Active' : 'Inactive',
                'Created At' => $documentType->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Document',
            'Applies To',
            'Expiry',
            'Status',
            'Created At',
        ];
    }

    private function formatApplicableFor(?string $applicableFor): string
    {
        return match ($applicableFor) {
            'driver' => 'Driver',
            'vehicle' => 'Vehicle',
            'oem' => 'OEM',
            'supervisor' => 'Supervisor',
            'controller' => 'Controller',
            default => '',
        };
    }
}
