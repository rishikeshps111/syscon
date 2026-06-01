<?php

namespace App\Exports;

use App\Models\Trip;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TripExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: Trip::with(['serviceType', 'route.startPoint', 'route.endPoint', 'depot'])
            ->select('service_type_id', 'route_id', 'depot_id', 'code', 'title', 'schedule_type', 'start_time', 'end_time', 'halt_time', 'trip_side', 'is_active', 'status', 'from_date', 'to_date', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($trip) {
            return [
                'Code' => $trip->code,
                'Trip Title' => $trip->trip_title,
                'Service Type' => $trip->serviceType?->name,
                'Route' => $trip->route?->route_name,
                'From Location' => $trip->route?->startPoint?->name,
                'To Location' => $trip->route?->endPoint?->name,
                'Depot' => $trip->depot?->name,
                'From Date' => $trip->from_date?->format('d M Y'),
                'To Date' => $trip->to_date?->format('d M Y'),
                'Actual Start Time' => $trip->start_time ? substr($trip->start_time, 0, 5) : '',
                'Actual Reach Time' => $trip->end_time ? substr($trip->end_time, 0, 5) : '',
                'Halt Time (Minutes)' => $this->timeToMinutes($trip->halt_time) ?? '',
                'Trip Side' => Trip::TRIP_SIDES[$trip->trip_side] ?? '',
                'Status' => $trip->status ?: ($trip->is_active ? 'Active' : 'Inactive'),
                'Created At' => $trip->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Trip Title',
            'Service Type',
            'Route',
            'From Location',
            'To Location',
            'Depot',
            'From Date',
            'To Date',
            'Actual Start Time',
            'Actual Reach Time',
            'Halt Time (Minutes)',
            'Trip Side',
            'Status',
            'Created At',
        ];
    }

    private function timeToMinutes(?string $time): ?int
    {
        if (! $time) {
            return null;
        }

        $parts = explode(':', $time);

        return ((int) ($parts[0] ?? 0) * 60) + (int) ($parts[1] ?? 0);
    }
}
