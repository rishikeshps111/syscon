<?php

namespace App\Exports;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DepartmentExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: Department::select('code', 'name', 'is_active', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($department) {
            return [
                'Code' => $department->code,
                'Name' => $department->name,
                'Status' => $department->is_active ? 'Active' : 'Inactive',
                'Created At' => $department->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Status',
            'Created At',
        ];
    }
}
