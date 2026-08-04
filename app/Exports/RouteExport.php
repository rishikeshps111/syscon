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
        $this->query = $query ?: RouteModel::with(['state', 'district', 'startPoint', 'endPoint', 'activeRouteAssignments.vehicle', 'activeRouteAssignments.driver'])
            ->select('routes.*');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($route) {
            return [
                'Route Code' => $route->route_code,
                'Route Name' => $route->route_name,
                'State' => $route->state?->name,
                'District' => $route->district?->name,
                'Starting Depot' => $route->startPoint?->name,
                'Ending Depot' => $route->endPoint?->name,
                'Approximate Distance' => $route->total_distance_km,
                'Route Type' => $route->route_type,
                'Route Category' => $route->route_category,
                'Status' => $route->status,
                'Created At' => $route->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Route Code',
            'Route Name',
            'State',
            'District',
            'Starting Depot',
            'Ending Depot',
            'Approximate Distance',
            'Route Type',
            'Route Category',
            'Status',
            'Created At',
        ];
    }
}
