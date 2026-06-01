<?php

namespace App\Exports;

use App\Http\Controllers\TripController;
use App\Models\TripSheetEntry;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompletedTripSheetExport implements FromCollection, WithHeadings
{
    public function __construct(private $query)
    {
    }

    public function collection(): Collection
    {
        return $this->query->get()->values()->map(function (TripSheetEntry $entry, int $index) {
            $assignment = TripController::assignmentForCompletedEntry($entry);
            $trip = $entry->sheet?->trip;

            return [
                'SL No' => $index + 1,
                'Trip Code' => $entry->sheet?->code,
                'Title' => $trip?->trip_title,
                'Date' => $entry->sheet?->date?->format('d M Y'),
                'From' => $trip?->route?->startPoint?->name,
                'To' => $trip?->route?->endPoint?->name,
                'Driver Name' => $assignment?->driverProfile?->user?->name,
                'Vehicle No' => $assignment?->vehicle?->vehicle_no,
                'Status' => $entry->sheet?->status ? ucfirst($entry->sheet->status) : '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'SL No',
            'Trip Code',
            'Title',
            'Date',
            'From',
            'To',
            'Driver Name',
            'Vehicle No',
            'Status',
        ];
    }
}
