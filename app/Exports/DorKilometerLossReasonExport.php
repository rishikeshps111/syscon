<?php

namespace App\Exports;

use App\Models\DorKilometerLossReason;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DorKilometerLossReasonExport implements FromCollection, WithHeadings
{
    public function __construct(private $query = null) {}

    public function collection()
    {
        return ($this->query ?: DorKilometerLossReason::with('accountResponsible')->select('*'))
            ->get()
            ->map(fn (DorKilometerLossReason $record) => [
                'Code' => $record->code,
                'Account Responsible' => $record->accountResponsible?->name,
                'Reason' => $record->name,
                'Status' => $record->is_active ? 'Active' : 'Inactive',
                'Created At' => $record->created_at?->format('d M Y'),
            ]);
    }

    public function headings(): array
    {
        return ['Code', 'Account Responsible', 'Reason', 'Status', 'Created At'];
    }
}
