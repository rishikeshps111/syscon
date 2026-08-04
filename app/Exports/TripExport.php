<?php

namespace App\Exports;

use App\Models\Trip;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TripExport implements FromCollection, WithHeadings
{
    public function __construct(private $query = null) {}

    public function collection()
    {
        $query = $this->query ?: Trip::with([
            'route.startPoint', 'route.endPoint', 'state', 'depot', 'vehicleClassification', 'tripNature',
        ])->select('trips.*');

        return $query->get()->map(fn (Trip $trip) => [
            'Trip Code' => $trip->code,
            'Trip Title' => $trip->trip_title,
            'Route' => $trip->route?->route_name,
            'State' => $trip->state?->name,
            'Depot' => $trip->depot?->name,
            'Start Point' => $trip->route?->startPoint?->name,
            'End Point' => $trip->route?->endPoint?->name,
            'Vehicle Classification' => $trip->vehicleClassification?->title,
            'Trip Nature' => $trip->tripNature?->title,
            'Rounds per Trip' => $trip->rounds_per_trip,
            'Schedule Km' => $trip->schedule_km,
            'Total Trips' => $trip->total_trips,
            'From Date' => $trip->from_date?->format('d M Y'),
            'To Date' => $trip->to_date?->format('d M Y'),
            'Status' => $trip->status,
            'Trip Notes' => $trip->notes,
            'Created At' => $trip->created_at->format('d M Y'),
        ]);
    }

    public function headings(): array
    {
        return [
            'Trip Code', 'Trip Title', 'Route', 'State', 'Depot', 'Start Point', 'End Point',
            'Vehicle Classification', 'Trip Nature', 'Rounds per Trip', 'Schedule Km', 'Total Trips',
            'From Date', 'To Date', 'Status', 'Trip Notes', 'Created At',
        ];
    }
}
