<?php

namespace App\Exports;

use App\Models\OemType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OemTypeExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: OemType::select('name', 'is_active', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($oemType) {
            return [
                'Name' => $oemType->name,
                'Status' => $oemType->is_active ? 'Active' : 'Inactive',
                'Created At' => $oemType->created_at?->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Name',
            'Status',
            'Created At',
        ];
    }
}
