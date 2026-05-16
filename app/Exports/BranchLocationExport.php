<?php

namespace App\Exports;

use App\Models\BranchLocation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BranchLocationExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: BranchLocation::with(['state', 'district', 'location'])
            ->select('state_id', 'district_id', 'location_id', 'code', 'name', 'remarks', 'status');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($branchLocation) {
            return [
                'Code' => $branchLocation->code,
                'Name' => $branchLocation->name,
                'State' => $branchLocation->state?->name,
                'District' => $branchLocation->district?->name,
                'Location' => $branchLocation->location?->name,
                'Remarks' => $branchLocation->remarks,
                'Status' => ucfirst($branchLocation->status),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'State',
            'District',
            'Location',
            'Remarks',
            'Status',
        ];
    }
}
