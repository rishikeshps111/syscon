<?php

namespace App\Exports;

use App\Models\DorAccountResponsible;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DorAccountResponsibleExport implements FromCollection, WithHeadings
{
    public function __construct(private $query = null) {}

    public function collection()
    {
        return ($this->query ?: DorAccountResponsible::select('code', 'name', 'is_active', 'created_at'))
            ->get()
            ->map(fn (DorAccountResponsible $record) => [
                'Code' => $record->code,
                'Name' => $record->name,
                'Status' => $record->is_active ? 'Active' : 'Inactive',
                'Created At' => $record->created_at?->format('d M Y'),
            ]);
    }

    public function headings(): array
    {
        return ['Code', 'Name', 'Status', 'Created At'];
    }
}
