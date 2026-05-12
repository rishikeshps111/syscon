<?php

namespace App\Exports;

use App\Models\ServiceType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ServiceTypeExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: ServiceType::select('code', 'name', 'description', 'is_active', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($serviceType) {
            return [
                'Code' => $serviceType->code,
                'Name' => $serviceType->name,
                'Description' => $serviceType->description,
                'Status' => $serviceType->is_active ? 'Active' : 'Inactive',
                'Created At' => $serviceType->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Description',
            'Status',
            'Created At',
        ];
    }
}
