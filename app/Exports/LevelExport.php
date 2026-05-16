<?php

namespace App\Exports;

use App\Models\Level;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LevelExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: Level::select('code', 'name', 'is_active', 'created_at');
    }

    public function collection()
    {
        return $this->query->get()->map(function ($level) {
            return [
                'Code' => $level->code,
                'Name' => $level->name,
                'Status' => $level->is_active ? 'Active' : 'Inactive',
                'Created At' => $level->created_at->format('d M Y'),
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
