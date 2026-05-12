<?php

namespace App\Exports;

use App\Models\Route as RouteModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RouteExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: RouteModel::with(['state', 'startPoint', 'endPoint'])
            ->select('state_id', 'start_point_id', 'end_point_id', 'code', 'name', 'distance', 'estimated_duration', 'route_type', 'is_active', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($route) {
            return [
                'Route Code' => $route->code,
                'Route Name' => $route->name,
                'Start Point' => $route->startPoint?->name,
                'End Point' => $route->endPoint?->name,
                'Distance' => $route->distance,
                'Estimated Duration' => $route->estimated_duration ? substr($route->estimated_duration, 0, 5) : '',
                'Route Type' => $route->route_type,
                'State' => $route->state?->name,
                'Status' => $route->is_active ? 'Active' : 'Inactive',
                'Created At' => $route->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Route Code',
            'Route Name',
            'Start Point',
            'End Point',
            'Distance',
            'Estimated Duration',
            'Route Type',
            'State',
            'Status',
            'Created At',
        ];
    }
}
