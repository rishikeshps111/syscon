<?php

namespace App\Exports;

use App\Models\VehicleClassification;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VehicleClassificationExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: VehicleClassification::select('title', 'description', 'is_active', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($vehicleClassification) {
            return [
                'Title' => $vehicleClassification->title,
                'Description' => $vehicleClassification->description,
                'Status' => $vehicleClassification->is_active ? 'Active' : 'Inactive',
                'Created At' => $vehicleClassification->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Title',
            'Description',
            'Status',
            'Created At',
        ];
    }
}
