<?php

namespace App\Exports;

use App\Models\Prefix;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PrefixExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: Prefix::select('prefix', 'module', 'is_active', 'created_at');
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->query->get()->map(function ($prefix) {
            return [
                'Prefix' => $prefix->prefix,
                'Module' => $prefix->module,
                'Status' => $prefix->is_active ? 'Active' : 'Inactive',
                'Created At' => $prefix->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Prefix',
            'Module',
            'Status',
            'Created At',
        ];
    }
}
