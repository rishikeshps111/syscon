<?php

namespace Database\Seeders;

use App\Models\ShiftSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            [
                'shift_name' => 'Morning Shift',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'break_duration_minutes' => 60,
                'total_working_hours' => 7,
                'grace_time_minutes' => 10,
                'minimum_working_hours' => 7,
                'check_in_window_start' => true,
                'check_in_window_end' => true,
                'check_out_flexibility' => true,
                'enable_overtime' => false,
                'is_active' => true,
            ],
            [
                'shift_name' => 'Evening Shift',
                'start_time' => '17:30',
                'end_time' => '02:00',
                'break_duration_minutes' => 45,
                'total_working_hours' => 7.75,
                'grace_time_minutes' => 10,
                'minimum_working_hours' => 7.75,
                'check_in_window_start' => true,
                'check_in_window_end' => true,
                'check_out_flexibility' => true,
                'enable_overtime' => false,
                'is_active' => true,
            ],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $shiftSetting = ShiftSetting::firstOrCreate(
                    ['shift_name' => $record['shift_name']],
                    array_merge(['number_of_shifts_per_day' => 2], $record)
                );

                if (! $shiftSetting->code) {
                    $shiftSetting->code = 'SH' . str_pad((string) $shiftSetting->id, 3, '0', STR_PAD_LEFT);
                    $shiftSetting->save();
                }
            }
        });
    }
}
