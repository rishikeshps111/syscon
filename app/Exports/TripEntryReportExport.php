<?php

namespace App\Exports;

use App\Http\Controllers\TripEntryReportController;
use App\Models\TripSheetEntry;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TripEntryReportExport implements FromCollection, WithHeadings
{
    public function __construct(private $query, private array $columns)
    {
    }

    public function collection(): Collection
    {
        $controller = app(TripEntryReportController::class);

        return $this->query->get()->values()
            ->map(fn (TripSheetEntry $entry, int $index) => array_values($controller->rowData($entry, $index, $this->columns)));
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }
}
