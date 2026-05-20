<?php

namespace App\Exports;

use App\Models\ComplaintCategory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ComplaintCategoryExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: ComplaintCategory::select('code', 'name', 'is_active', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($complaintCategory) {
            return [
                'Code' => $complaintCategory->code,
                'Name' => $complaintCategory->name,
                'Status' => $complaintCategory->is_active ? 'Active' : 'Inactive',
                'Created At' => $complaintCategory->created_at->format('d M Y'),
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
