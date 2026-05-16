<?php

namespace App\Exports;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Holiday;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class HolidayExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: Holiday::query();
    }

    public function collection()
    {
        return $this->query->with(['state', 'branchLocation'])->get()->map(function ($holiday) {
            return [
                'Code' => $holiday->code,
                'Holiday Name' => $holiday->holiday_name,
                'Holiday Date' => $holiday->holiday_date->format('d M Y'),
                'Holiday Type' => $holiday->holiday_type_label,
                'Location' => $holiday->applicable_location_label,
                'Applicable For' => $this->applicableFor($holiday),
                'Holiday Duration' => $holiday->holiday_duration_label,
                'Recurring Yearly' => $holiday->is_recurring_yearly ? 'Yes' : 'No',
                'Status' => $holiday->is_active ? 'Active' : 'Inactive',
                'Description' => $holiday->description,
                'Remarks' => $holiday->remarks,
                'Created At' => $holiday->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Holiday Name',
            'Holiday Date',
            'Holiday Type',
            'Location',
            'Applicable For',
            'Holiday Duration',
            'Recurring Yearly',
            'Status',
            'Description',
            'Remarks',
            'Created At',
        ];
    }

    private function applicableFor(Holiday $holiday): string
    {
        if ($holiday->applicable_for === 'specific_departments') {
            return Department::whereIn('id', $holiday->department_ids ?? [])->pluck('name')->implode(', ');
        }

        if ($holiday->applicable_for === 'specific_designations') {
            return Designation::whereIn('id', $holiday->designation_ids ?? [])->pluck('name')->implode(', ');
        }

        return $holiday->applicable_for_label;
    }
}
