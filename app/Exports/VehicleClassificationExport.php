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
        $this->query = $query ?: VehicleClassification::select('code', 'name', 'capacity', 'fuel_type', 'is_active', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($vehicleClassification) {
            return [
                'Code' => $vehicleClassification->code,
                'Vehicle Type' => $vehicleClassification->name,
                'Capacity' => $vehicleClassification->capacity,
                'Fuel' => $this->formatFuelType($vehicleClassification->fuel_type),
                'Status' => $vehicleClassification->is_active ? 'Active' : 'Inactive',
                'Created At' => $vehicleClassification->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Vehicle Type',
            'Capacity',
            'Fuel',
            'Status',
            'Created At',
        ];
    }

    private function formatFuelType(?string $fuelType): string
    {
        return match ($fuelType) {
            'petrol' => 'Petrol',
            'diesel' => 'Diesel',
            'ev' => 'EV',
            'hybrid' => 'Hybrid',
            default => '',
        };
    }
}
