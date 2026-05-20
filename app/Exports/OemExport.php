<?php

namespace App\Exports;

use App\Models\Oem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OemExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: Oem::with('state');
    }

    public function collection()
    {
        return $this->query->get()->map(function (Oem $oem) {
            return [
                'OEM Code' => $oem->oem_code,
                'OEM Name' => $oem->oem_name,
                'Type' => $oem->oem_type,
                'State' => $oem->state?->name,
                'Verification Status' => $oem->is_verified ? 'Verified' : 'Pending',
                'Last Updated' => $oem->updated_at?->format('d M Y'),
                'Status' => $oem->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'OEM Code',
            'OEM Name',
            'Type',
            'State',
            'Verification Status',
            'Last Updated',
            'Status',
        ];
    }
}
