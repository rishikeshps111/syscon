<?php

namespace Database\Seeders;

use App\Models\BranchLocation;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            'Secunderabad' => [
                ['name' => 'Secunderabad Branch', 'remarks' => 'Hyderabad north branch', 'status' => 'active'],
            ],
            'Banjara Hills' => [
                ['name' => 'Banjara Hills Branch', 'remarks' => 'Hyderabad central branch', 'status' => 'active'],
            ],
            'Madhapur' => [
                ['name' => 'Madhapur Branch', 'remarks' => 'Hyderabad technology corridor branch', 'status' => 'active'],
            ],
            'Gachibowli' => [
                ['name' => 'Gachibowli Branch', 'remarks' => 'Rangareddy corporate corridor branch', 'status' => 'active'],
            ],
            'Hanamkonda' => [
                ['name' => 'Hanamkonda Branch', 'remarks' => 'Warangal operations branch', 'status' => 'active'],
            ],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $locationName => $branchLocations) {
                $location = Location::where('name', $locationName)->first();

                if (! $location) {
                    continue;
                }

                foreach ($branchLocations as $branchLocationData) {
                    $branchLocation = BranchLocation::firstOrCreate(
                        [
                            'state_id' => $location->state_id,
                            'district_id' => $location->district_id,
                            'location_id' => $location->id,
                            'name' => $branchLocationData['name'],
                        ],
                        [
                            'remarks' => $branchLocationData['remarks'],
                            'status' => $branchLocationData['status'],
                        ]
                    );

                    if (! $branchLocation->code) {
                        $branchLocation->code = generate_code('Branch Location Module', $branchLocation->id, 3, 'BL');
                        $branchLocation->save();
                    }
                }
            }
        });
    }
}
