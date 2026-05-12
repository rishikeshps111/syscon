<?php

namespace App\Exports;

use App\Models\District;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DistrictExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: District::with('state')
            ->select('state_id', 'code', 'name', 'is_default', 'is_active', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($district) {
            return [
                'Code' => $district->code,
                'District' => $district->name,
                'State' => $district->state?->name,
                'Default' => $district->is_default ? 'Yes' : 'No',
                'Status' => $district->is_active ? 'Active' : 'Inactive',
                'Created At' => $district->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'District',
            'State',
            'Default',
            'Status',
            'Created At',
        ];
    }
}
