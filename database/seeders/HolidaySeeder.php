<?php

namespace Database\Seeders;

use App\Models\Holiday;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kerala = State::where('name', 'Kerala')->first();

        $records = [
            [
                'holiday_name' => 'New Year',
                'holiday_date' => now()->year . '-01-01',
                'holiday_type' => 'national',
                'applicable_location' => 'all',
                'is_recurring_yearly' => true,
            ],
            [
                'holiday_name' => 'Republic Day',
                'holiday_date' => now()->year . '-01-26',
                'holiday_type' => 'national',
                'applicable_location' => 'all',
                'is_recurring_yearly' => true,
            ],
            [
                'holiday_name' => 'Independence Day',
                'holiday_date' => now()->year . '-08-15',
                'holiday_type' => 'national',
                'applicable_location' => 'all',
                'is_recurring_yearly' => true,
            ],
            [
                'holiday_name' => 'Onam',
                'holiday_date' => now()->year . '-09-05',
                'holiday_type' => 'state',
                'applicable_location' => $kerala ? 'state' : 'all',
                'state_id' => $kerala?->id,
                'is_recurring_yearly' => true,
            ],
            [
                'holiday_name' => 'Christmas',
                'holiday_date' => now()->year . '-12-25',
                'holiday_type' => 'national',
                'applicable_location' => 'all',
                'is_recurring_yearly' => true,
            ],
            [
                'holiday_name' => 'Company Foundation Day',
                'holiday_date' => now()->year . '-04-10',
                'holiday_type' => 'company',
                'applicable_location' => 'all',
                'is_recurring_yearly' => true,
            ],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $holiday = Holiday::firstOrCreate(
                    ['holiday_name' => $record['holiday_name']],
                    array_merge([
                        'applicable_for' => 'all_employees',
                        'holiday_duration' => 'full_day',
                        'is_active' => true,
                    ], $record)
                );

                if (! $holiday->code) {
                    $holiday->code = generate_code('Holiday Module', $holiday->id, 3, 'HOL');
                    $holiday->save();
                }
            }
        });
    }
}
