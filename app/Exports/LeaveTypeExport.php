<?php

namespace App\Exports;

use App\Models\LeaveType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LeaveTypeExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: LeaveType::select(
            'code',
            'leave_name',
            'short_name',
            'leave_category',
            'max_leaves_per_year',
            'carry_forward_allowed',
            'max_carry_forward_limit',
            'encashment_allowed',
            'applicable_for',
            'gender_specific',
            'minimum_service_required',
            'minimum_leave_days',
            'maximum_leave_days_per_request',
            'advance_notice_days',
            'allow_half_day',
            'requires_approval',
            'is_active',
            'description',
            'remarks',
            'created_at'
        );
    }

    public function collection()
    {
        return $this->query->get()->map(function ($leaveType) {
            return [
                'Code' => $leaveType->code,
                'Leave Name' => $leaveType->leave_name,
                'Short Name' => $leaveType->short_name,
                'Category' => $leaveType->leave_category,
                'Max/Year' => $leaveType->max_leaves_per_year ?? 'Unlimited',
                'Carry Forward' => $leaveType->carry_forward_allowed ? 'Yes' : 'No',
                'Max Carry Forward Limit' => $leaveType->max_carry_forward_limit,
                'Encashment' => $leaveType->encashment_allowed ? 'Yes' : 'No',
                'Applicable For' => LeaveType::APPLICABLE_FOR[$leaveType->applicable_for] ?? '',
                'Gender Specific' => LeaveType::GENDERS[$leaveType->gender_specific] ?? '',
                'Minimum Service Required' => $leaveType->minimum_service_required,
                'Minimum Leave Days' => $leaveType->minimum_leave_days,
                'Maximum Leave Days Per Request' => $leaveType->maximum_leave_days_per_request,
                'Advance Notice Days' => $leaveType->advance_notice_days,
                'Allow Half Day' => $leaveType->allow_half_day ? 'Yes' : 'No',
                'Requires Approval' => $leaveType->requires_approval ? 'Yes' : 'No',
                'Status' => $leaveType->is_active ? 'Active' : 'Inactive',
                'Description' => $leaveType->description,
                'Remarks' => $leaveType->remarks,
                'Created At' => $leaveType->created_at->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Leave Name',
            'Short Name',
            'Category',
            'Max/Year',
            'Carry Forward',
            'Max Carry Forward Limit',
            'Encashment',
            'Applicable For',
            'Gender Specific',
            'Minimum Service Required',
            'Minimum Leave Days',
            'Maximum Leave Days Per Request',
            'Advance Notice Days',
            'Allow Half Day',
            'Requires Approval',
            'Status',
            'Description',
            'Remarks',
            'Created At',
        ];
    }
}
