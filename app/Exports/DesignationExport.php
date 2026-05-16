<?php

namespace App\Exports;

use App\Models\Designation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DesignationExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: Designation::with(['department', 'level', 'reportingRole'])
            ->select('department_id', 'level_id', 'reporting_to', 'code', 'name', 'description', 'is_active', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($designation) {
            return [
                'Code' => $designation->code,
                'Designation' => $designation->name,
                'Department' => $designation->department?->name ?? '',
                'Level' => $designation->level?->name ?? '',
                'Reporting To' => $designation->reportingRole?->name ?? '',
                'Description' => $designation->description,
                'Status' => $designation->is_active ? 'Active' : 'Inactive',
                'Created At' => $designation->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Designation',
            'Department',
            'Level',
            'Reporting To',
            'Description',
            'Status',
            'Created At',
        ];
    }
}
