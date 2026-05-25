<?php

namespace App\Exports;

use App\Models\TripSetup;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TripSetupExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: TripSetup::with(['serviceType', 'route'])
            ->select('service_type_id', 'route_id', 'code', 'schedule_type', 'start_time', 'end_time', 'is_active', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($tripSetup) {
            return [
                'Code' => $tripSetup->code,
                'Service Type' => $tripSetup->serviceType?->name,
                'Route' => $tripSetup->route?->route_name,
                'Schedule Type' => ucfirst($tripSetup->schedule_type),
                'Start Time' => $tripSetup->start_time ? substr($tripSetup->start_time, 0, 5) : '',
                'End Time' => $tripSetup->end_time ? substr($tripSetup->end_time, 0, 5) : '',
                'Status' => $tripSetup->is_active ? 'Active' : 'Inactive',
                'Created At' => $tripSetup->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Service Type',
            'Route',
            'Schedule Type',
            'Start Time',
            'End Time',
            'Status',
            'Created At',
        ];
    }
}
