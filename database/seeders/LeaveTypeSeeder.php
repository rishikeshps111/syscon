<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            [
                'leave_name' => 'Casual Leave',
                'short_name' => 'CL',
                'leave_category' => 'Paid Leave',
                'max_leaves_per_year' => 12,
                'carry_forward_allowed' => false,
                'max_carry_forward_limit' => null,
                'encashment_allowed' => false,
                'applicable_for' => 'all_employees',
                'gender_specific' => 'all',
                'minimum_service_required' => null,
                'minimum_leave_days' => 0.5,
                'maximum_leave_days_per_request' => 3,
                'advance_notice_days' => 1,
                'allow_half_day' => true,
                'requires_approval' => true,
                'description' => 'Short-term leave for personal needs',
                'remarks' => null,
                'is_active' => true,
            ],
            [
                'leave_name' => 'Sick Leave',
                'short_name' => 'SL',
                'leave_category' => 'Paid Leave',
                'max_leaves_per_year' => 10,
                'carry_forward_allowed' => false,
                'max_carry_forward_limit' => null,
                'encashment_allowed' => false,
                'applicable_for' => 'all_employees',
                'gender_specific' => 'all',
                'minimum_service_required' => null,
                'minimum_leave_days' => 0.5,
                'maximum_leave_days_per_request' => 5,
                'advance_notice_days' => 0,
                'allow_half_day' => true,
                'requires_approval' => true,
                'description' => 'Medical reason leave',
                'remarks' => null,
                'is_active' => true,
            ],
            [
                'leave_name' => 'Earned Leave',
                'short_name' => 'EL',
                'leave_category' => 'Paid Leave',
                'max_leaves_per_year' => 24,
                'carry_forward_allowed' => true,
                'max_carry_forward_limit' => 30,
                'encashment_allowed' => true,
                'applicable_for' => 'all_employees',
                'gender_specific' => 'all',
                'minimum_service_required' => '1 Year',
                'minimum_leave_days' => 1,
                'maximum_leave_days_per_request' => 15,
                'advance_notice_days' => 7,
                'allow_half_day' => false,
                'requires_approval' => true,
                'description' => 'Accrual-based leave',
                'remarks' => null,
                'is_active' => true,
            ],
            [
                'leave_name' => 'Maternity Leave',
                'short_name' => 'ML',
                'leave_category' => 'Paid Leave',
                'max_leaves_per_year' => 180,
                'carry_forward_allowed' => false,
                'max_carry_forward_limit' => null,
                'encashment_allowed' => false,
                'applicable_for' => 'all_employees',
                'gender_specific' => 'female',
                'minimum_service_required' => '6 Months',
                'minimum_leave_days' => 1,
                'maximum_leave_days_per_request' => 180,
                'advance_notice_days' => 30,
                'allow_half_day' => false,
                'requires_approval' => true,
                'description' => 'Female only maternity benefit',
                'remarks' => null,
                'is_active' => true,
            ],
            [
                'leave_name' => 'Paternity Leave',
                'short_name' => 'PL',
                'leave_category' => 'Paid Leave',
                'max_leaves_per_year' => 10,
                'carry_forward_allowed' => false,
                'max_carry_forward_limit' => null,
                'encashment_allowed' => false,
                'applicable_for' => 'all_employees',
                'gender_specific' => 'male',
                'minimum_service_required' => '6 Months',
                'minimum_leave_days' => 1,
                'maximum_leave_days_per_request' => 10,
                'advance_notice_days' => 15,
                'allow_half_day' => false,
                'requires_approval' => true,
                'description' => 'Male only paternity benefit',
                'remarks' => null,
                'is_active' => true,
            ],
            [
                'leave_name' => 'Loss of Pay',
                'short_name' => 'LOP',
                'leave_category' => 'Unpaid Leave',
                'max_leaves_per_year' => null,
                'carry_forward_allowed' => false,
                'max_carry_forward_limit' => null,
                'encashment_allowed' => false,
                'applicable_for' => 'all_employees',
                'gender_specific' => 'all',
                'minimum_service_required' => null,
                'minimum_leave_days' => 0.5,
                'maximum_leave_days_per_request' => null,
                'advance_notice_days' => 0,
                'allow_half_day' => true,
                'requires_approval' => true,
                'description' => 'Salary deduction leave',
                'remarks' => 'Use when paid leave balance is exhausted',
                'is_active' => true,
            ],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $leaveType = LeaveType::firstOrCreate(
                    ['leave_name' => $record['leave_name']],
                    $record
                );

                if (! $leaveType->code) {
                    $leaveType->code = generate_code('Leave Type Module', $leaveType->id, 3, 'LV');
                    $leaveType->save();
                }
            }
        });
    }
}
