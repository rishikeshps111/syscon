<?php

namespace App\Exports;

use App\Models\Leave;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LeaveExport implements FromCollection, WithHeadings
{
    public function __construct(private $query = null) {}

    public function collection()
    {
        return ($this->query ?: Leave::with(['user.roles', 'leaveType'])->latest())->get()->map(function (Leave $leave) {
            return [
                'Leave Code' => $leave->code,
                'Employee' => $leave->user?->name,
                'Role' => $leave->user?->roles?->pluck('name')->implode(', '),
                'Leave For' => Leave::TYPES[$leave->leave_for] ?? $leave->leave_for,
                'Leave Type' => $leave->leave_for === 'driver'
                    ? $leave->driver_leave_type
                    : $leave->leaveType?->short_name ?? $leave->leaveType?->leave_name,
                'From' => $leave->from_date?->format('d M Y'),
                'To' => $leave->to_date?->format('d M Y'),
                'Date' => $leave->leave_date?->format('d M Y'),
                'Days' => $leave->number_of_days,
                'Shift' => $leave->shift,
                'Assigned Vehicle / Route' => $leave->assigned_vehicle_route,
                'Status' => $leave->status,
                'Reason' => $leave->reason,
                'Remarks' => $leave->remarks,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Leave Code',
            'Employee',
            'Role',
            'Leave For',
            'Leave Type',
            'From',
            'To',
            'Date',
            'Days',
            'Shift',
            'Assigned Vehicle / Route',
            'Status',
            'Reason',
            'Remarks',
        ];
    }
}
