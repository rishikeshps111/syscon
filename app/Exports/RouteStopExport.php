<?php

namespace App\Exports;

use App\Models\Route as RouteModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RouteStopExport implements FromCollection, WithHeadings
{
    protected $query;

    protected RouteModel $route;

    public function __construct($query, RouteModel $route)
    {
        $this->query = $query;
        $this->route = $route;
    }

    public function collection()
    {
        return $this->query->get()->map(function ($routeStop) {
            return [
                'Route Code' => $this->route->code,
                'Route Name' => $this->route->name,
                'Place Name' => $routeStop->name,
                'Expected Reach Time' => $routeStop->expected_reach_time ? substr($routeStop->expected_reach_time, 0, 5) : '',
                'Position' => $routeStop->position,
                'Created At' => $routeStop->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Route Code',
            'Route Name',
            'Place Name',
            'Expected Reach Time',
            'Position',
            'Created At',
        ];
    }
}
