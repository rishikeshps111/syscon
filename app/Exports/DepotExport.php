<?php

namespace App\Exports;

use App\Models\Depot;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DepotExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: Depot::with('location')
            ->select('location_id', 'code', 'name', 'is_active', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($depot) {
            return [
                'Code' => $depot->code,
                'Depot' => $depot->name,
                'Location' => $depot->location?->name,
                'Status' => $depot->is_active ? 'Active' : 'Inactive',
                'Created At' => $depot->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Depot',
            'Location',
            'Status',
            'Created At',
        ];
    }
}
