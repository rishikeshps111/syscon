<?php

namespace App\Exports;

use App\Models\ShiftSetting;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ShiftSettingExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: ShiftSetting::select(
            'code',
            'shift_name',
            'number_of_shifts_per_day',
            'start_time',
            'end_time',
            'break_duration_minutes',
            'total_working_hours',
            'grace_time_minutes',
            'minimum_working_hours',
            'check_in_window_start',
            'check_in_window_end',
            'check_out_flexibility',
            'enable_overtime',
            'is_active',
            'created_at'
        );
    }

    public function collection()
    {
        return $this->query->get()->map(function ($shiftSetting) {
            return [
                'Code' => $shiftSetting->code,
                'Shift Name' => $shiftSetting->shift_name,
                'Number of Shifts per Day' => $shiftSetting->number_of_shifts_per_day,
                'Start Time' => $shiftSetting->formatted_start_time,
                'End Time' => $shiftSetting->formatted_end_time,
                'Break Duration' => $shiftSetting->break_duration_minutes . ' mins',
                'Total Working Hours' => $shiftSetting->total_working_hours,
                'Grace Time' => $shiftSetting->grace_time_minutes . ' mins',
                'Minimum Working Hours' => $shiftSetting->minimum_working_hours,
                'Check-in Window Start' => $shiftSetting->check_in_window_start ? 'Yes' : 'No',
                'Check-in Window End' => $shiftSetting->check_in_window_end ? 'Yes' : 'No',
                'Check-out Flexibility' => $shiftSetting->check_out_flexibility ? 'Yes' : 'No',
                'Enable Overtime' => $shiftSetting->enable_overtime ? 'Yes' : 'No',
                'Status' => $shiftSetting->is_active ? 'Active' : 'Inactive',
                'Created At' => $shiftSetting->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Shift Name',
            'Number of Shifts per Day',
            'Start Time',
            'End Time',
            'Break Duration',
            'Total Working Hours',
            'Grace Time',
            'Minimum Working Hours',
            'Check-in Window Start',
            'Check-in Window End',
            'Check-out Flexibility',
            'Enable Overtime',
            'Status',
            'Created At',
        ];
    }
}
