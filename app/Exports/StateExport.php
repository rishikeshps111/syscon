<?php

namespace App\Exports;

use App\Models\State;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StateExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: State::select('code', 'name', 'is_default', 'is_active', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($state) {
            return [
                'Code' => $state->code,
                'Name' => $state->name,
                'Default' => $state->is_default ? 'Yes' : 'No',
                'Status' => $state->is_active ? 'Active' : 'Inactive',
                'Created At' => $state->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Default',
            'Status',
            'Created At',
        ];
    }
}
