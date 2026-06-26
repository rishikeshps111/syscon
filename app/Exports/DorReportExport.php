<?php

namespace App\Exports;

use App\Http\Controllers\DorReportController;
use App\Models\TripSheetEntryDor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DorReportExport implements FromCollection, WithHeadings
{
    public function __construct(private $query, private array $columns)
    {
    }

    public function collection(): Collection
    {
        $controller = app(DorReportController::class);

        return $this->query->get()
            ->values()
            ->map(fn (TripSheetEntryDor $dor, int $index) => array_values($controller->rowData($dor, $index, $this->columns)));
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }
}
