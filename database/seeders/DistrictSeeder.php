<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            'Telangana' => ['Hyderabad', 'Rangareddy', 'Warangal', 'Medchal-Malkajgiri', 'Nizamabad', 'Karimnagar'],
        ];

        DB::transaction(function () use ($records) {
            $hasDefault = District::where('is_default', true)->exists();

            foreach ($records as $stateName => $districts) {
                $state = State::where('name', $stateName)->first();

                if (! $state) {
                    continue;
                }

                foreach ($districts as $districtName) {
                    $district = District::firstOrCreate(
                        [
                            'state_id' => $state->id,
                            'name' => $districtName,
                        ],
                        [
                            'is_active' => true,
                            'is_default' => ! $hasDefault,
                        ]
                    );

                    if (! $hasDefault && $district->is_default) {
                        $hasDefault = true;
                    }

                    if (! $district->code) {
                        $district->code = generate_code('District Module', $district->id, 3, 'DS');
                        $district->save();
                    }
                }
            }
        });
    }
}
