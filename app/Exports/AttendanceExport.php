<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceExport implements FromCollection, WithHeadings
{
    public function __construct(private $query) {}

    public function collection()
    {
        return $this->query->get()->map(function (Attendance $attendance) {
            return [
                'Date' => $attendance->attendance_date?->format('d-m-Y'),
                'Month' => $attendance->attendance_date?->format('F'),
                'Year' => $attendance->year,
                'User' => trim(($attendance->user?->code ? $attendance->user->code . ' - ' : '') . ($attendance->user?->name ?? '')),
                'Role' => $attendance->user?->roles?->pluck('name')->implode(', '),
                'Status' => Attendance::STATUSES[$attendance->status] ?? $attendance->status,
                'Half Day' => $attendance->half_day_period ? (Attendance::HALF_DAY_PERIODS[$attendance->half_day_period] ?? $attendance->half_day_period) : null,
                'Shift' => $attendance->shift,
                'Duty Type' => $attendance->duty_type,
                'Leave Application' => $attendance->leave
                    ? trim(($attendance->leave->code ?: '#' . $attendance->leave->id) . ' - ' . ($attendance->leave->leave_for === 'driver' ? $attendance->leave->driver_leave_type : $attendance->leave->leaveType?->leave_name))
                    : null,
                'Remarks' => $attendance->remarks,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Date',
            'Month',
            'Year',
            'User',
            'Role',
            'Status',
            'Half Day',
            'Shift',
            'Duty Type',
            'Leave Application',
            'Remarks',
        ];
    }
}
