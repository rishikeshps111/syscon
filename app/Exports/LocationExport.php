<?php

namespace App\Exports;

use App\Models\Location;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LocationExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: Location::with(['state', 'district'])
            ->select('state_id', 'district_id', 'code', 'name', 'pincode', 'is_default', 'is_active', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($location) {
            return [
                'Code' => $location->code,
                'Location' => $location->name,
                'Pincode' => $location->pincode,
                'State' => $location->state?->name,
                'District' => $location->district?->name,
                'Default' => $location->is_default ? 'Yes' : 'No',
                'Status' => $location->is_active ? 'Active' : 'Inactive',
                'Created At' => $location->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Location',
            'Pincode',
            'State',
            'District',
            'Default',
            'Status',
            'Created At',
        ];
    }
}
