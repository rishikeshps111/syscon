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
        return $this->query->get()->values()->map(function (DriverProfile $driver, int $index) {
            return [
                'SL No' => $index + 1,
                'Driver Name' => $driver->user?->name,
                'Assigned' => LicenseExpiryReportController::assignmentLabel($driver),
                'Depot' => $driver->depot?->name,
                'License No' => $driver->license_number,
                'Badge No' => $driver->badge_number,
                'License Expiry Date' => $driver->expiry_date?->format('d-m-Y'),
                'Badge Expiry Date' => $driver->badge_expiry_date?->format('d-m-Y'),
                'Phone No' => $driver->user?->full_phone,
                'Action' => 'Send Reminder',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'SL No',
            'Driver Name',
            'Assigned',
            'Depot',
            'License No',
            'Badge No',
            'License Expiry Date',
            'Badge Expiry Date',
            'Phone No',
            'Action',
        ];
    }
}
