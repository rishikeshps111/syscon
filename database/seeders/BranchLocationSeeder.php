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
            'Kakkanad' => [
                ['name' => 'Kakkanad Corporate Branch', 'remarks' => 'Primary HRMS branch location', 'status' => 'active'],
                ['name' => 'Kakkanad Operations Branch', 'remarks' => 'Operations support branch', 'status' => 'active'],
            ],
            'Aluva' => [
                ['name' => 'Aluva Branch', 'remarks' => 'Regional branch location', 'status' => 'active'],
            ],
            'Fort Kochi' => [
                ['name' => 'Fort Kochi Branch', 'remarks' => 'Coastal service branch', 'status' => 'active'],
            ],
            'Koyilandy' => [
                ['name' => 'Koyilandy Branch', 'remarks' => 'North Kerala branch', 'status' => 'active'],
            ],
            'Vadakara' => [
                ['name' => 'Vadakara Branch', 'remarks' => 'North zone branch', 'status' => 'inactive'],
            ],
            'Neyyattinkara' => [
                ['name' => 'Neyyattinkara Branch', 'remarks' => 'South Kerala branch', 'status' => 'active'],
            ],
            'Kazhakkoottam' => [
                ['name' => 'Kazhakkoottam Branch', 'remarks' => 'Technology park branch', 'status' => 'active'],
            ],
            'T. Nagar' => [
                ['name' => 'T. Nagar Branch', 'remarks' => 'Chennai central branch', 'status' => 'active'],
            ],
            'Anna Nagar' => [
                ['name' => 'Anna Nagar Branch', 'remarks' => 'Chennai north branch', 'status' => 'active'],
            ],
            'Velachery' => [
                ['name' => 'Velachery Branch', 'remarks' => 'Chennai south branch', 'status' => 'active'],
            ],
            'Gandhipuram' => [
                ['name' => 'Gandhipuram Branch', 'remarks' => 'Coimbatore city branch', 'status' => 'active'],
            ],
            'Peelamedu' => [
                ['name' => 'Peelamedu Branch', 'remarks' => 'Coimbatore east branch', 'status' => 'inactive'],
            ],
            'Colaba' => [
                ['name' => 'Colaba Branch', 'remarks' => 'Mumbai south branch', 'status' => 'active'],
            ],
            'Dadar' => [
                ['name' => 'Dadar Branch', 'remarks' => 'Mumbai central branch', 'status' => 'active'],
            ],
            'Shivajinagar' => [
                ['name' => 'Shivajinagar Branch', 'remarks' => 'Pune central branch', 'status' => 'active'],
            ],
            'Kothrud' => [
                ['name' => 'Kothrud Branch', 'remarks' => 'Pune west branch', 'status' => 'active'],
            ],
            'Koramangala' => [
                ['name' => 'Koramangala Branch', 'remarks' => 'Bengaluru central branch', 'status' => 'active'],
            ],
            'Indiranagar' => [
                ['name' => 'Indiranagar Branch', 'remarks' => 'Bengaluru east branch', 'status' => 'active'],
            ],
            'Whitefield' => [
                ['name' => 'Whitefield Branch', 'remarks' => 'Bengaluru technology branch', 'status' => 'suspended'],
            ],
            'Secunderabad' => [
                ['name' => 'Secunderabad Branch', 'remarks' => 'Hyderabad north branch', 'status' => 'active'],
            ],
            'Banjara Hills' => [
                ['name' => 'Banjara Hills Branch', 'remarks' => 'Hyderabad central branch', 'status' => 'active'],
            ],
            'Connaught Place' => [
                ['name' => 'Connaught Place Branch', 'remarks' => 'Delhi central branch', 'status' => 'active'],
            ],
            'Dwarka' => [
                ['name' => 'Dwarka Branch', 'remarks' => 'Delhi west branch', 'status' => 'inactive'],
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
