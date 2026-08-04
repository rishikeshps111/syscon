<?php

namespace App\Exports;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VehicleExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: Vehicle::with(['state', 'oem', 'depot', 'branch', 'vehicleClassification']);
    }

    public function collection()
    {
        return $this->query->get()->map(function (Vehicle $vehicle) {
            return [
                'Vehicle Code' => $vehicle->vehicle_code,
                'Vehicle No' => $vehicle->vehicle_no,
                'Type' => $vehicle->vehicle_type,
                'Fuel Type' => $vehicle->fuel_type,
                'Vehicle Classification' => $vehicle->vehicleClassification?->title,
                'OEM' => $vehicle->oem?->oem_name,
                'State' => $vehicle->state?->name,
                'Depot' => $vehicle->depot?->name,
                'Branch' => $vehicle->branch?->name,
                'Seating Capacity' => $vehicle->capacity_seating,
                'Load Capacity' => $vehicle->capacity_load,
                'Insurance Expiry' => $vehicle->insurance_expiry?->format('d M Y'),
                'Fitness Expiry' => $vehicle->fitness_expiry?->format('d M Y'),
                'GPS Status' => $vehicle->gps_enabled ? 'Enabled' : 'Disabled',
                'Status' => $vehicle->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Vehicle Code',
            'Vehicle No',
            'Type',
            'Fuel Type',
            'Vehicle Classification',
            'OEM',
            'State',
            'Depot',
            'Branch',
            'Seating Capacity',
            'Load Capacity',
            'Insurance Expiry',
            'Fitness Expiry',
            'GPS Status',
            'Status',
        ];
    }
}
