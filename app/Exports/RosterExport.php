<?php

namespace App\Exports;

use App\Models\Roster;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RosterExport implements FromCollection, WithHeadings
{
    public function __construct(private $query = null)
    {
        $this->query ??= Roster::query();
    }

    public function collection()
    {
        return $this->query->get()->map(function (Roster $roster) {
            $entries = $roster->tripSheetEntries;

            return [
                'Roster Code' => $roster->code,
                'Date' => $roster->duty_date?->format('d M Y'),
                'Driver Name' => $roster->driverProfile?->user?->name,
                'Vehicle' => $roster->vehicle?->vehicle_no,
                'Trip Code' => $entries->map(fn($entry) => $entry->sheet?->code)->filter()->implode(', '),
                'Trip Title' => $entries->map(fn($entry) => $entry->sheet?->trip?->trip_title)->filter()->implode(', '),
                'State' => $roster->state?->name,
                'Vendor' => $roster->oem?->oem_name,
                'Depot' => $roster->depot?->name,
                'Shift Type' => Roster::SHIFT_TYPES[$roster->shift_type] ?? $roster->shift_type,
                'Shift Start Time' => $this->time($roster->shift_start_time),
                'Shift End Time' => $this->time($roster->shift_end_time),
                'Reporting Time' => $this->time($roster->reporting_time),
                // 'Status' => Roster::STATUSES[$roster->status] ?? $roster->status,
                // 'Attendance Status' => $roster->attendance_status ? (Roster::ATTENDANCE_STATUSES[$roster->attendance_status] ?? $roster->attendance_status) : '',
                'Remarks' => $roster->remarks,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Roster Code',
            'Date',
            'Driver Name',
            'Vehicle',
            'Trip Code',
            'Trip Title',
            'State',
            'Vendor',
            'Depot',
            'Shift Type',
            'Shift Start Time',
            'Shift End Time',
            'Reporting Time',
            // 'Status',
            // 'Attendance Status',
            'Remarks',
        ];
    }

    private function time(?string $value): string
    {
        return $value ? substr($value, 0, 5) : '';
    }
}
