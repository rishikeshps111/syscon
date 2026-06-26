<?php

namespace App\Exports;

use App\Http\Controllers\LicenseExpiryReportController;
use App\Models\DriverProfile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LicenseExpiryReportExport implements FromCollection, WithHeadings
{
    public function __construct(private $query)
    {
    }

    public function collection(): Collection
    {
        $controller = app(LicenseExpiryReportController::class);

        return $this->query->get()->values()->map(function (DriverProfile $driver, int $index) use ($controller) {
            return array_values($controller->rowData($driver, $index));
        });
    }

    public function headings(): array
    {
        return array_values(app(LicenseExpiryReportController::class)->columns());
    }
}
