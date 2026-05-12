<?php

namespace Database\Seeders;

use App\Models\Depot;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            'Kakkanad' => ['Kakkanad Main Depot', 'Kakkanad North Depot'],
            'Aluva' => ['Aluva Depot', 'Aluva Logistics Hub'],
            'Fort Kochi' => ['Fort Kochi Depot'],
            'Koyilandy' => ['Koyilandy Depot'],
            'Vadakara' => ['Vadakara Depot'],
            'Neyyattinkara' => ['Neyyattinkara Depot'],
            'Kazhakkoottam' => ['Kazhakkoottam Depot'],
            'T. Nagar' => ['T. Nagar Depot', 'T. Nagar Central Depot'],
            'Anna Nagar' => ['Anna Nagar Depot'],
            'Velachery' => ['Velachery Depot'],
            'Gandhipuram' => ['Gandhipuram Depot'],
            'Peelamedu' => ['Peelamedu Depot'],
            'Colaba' => ['Colaba Depot'],
            'Dadar' => ['Dadar Depot'],
            'Shivajinagar' => ['Shivajinagar Depot'],
            'Kothrud' => ['Kothrud Depot'],
            'Koramangala' => ['Koramangala Depot', 'Koramangala South Depot'],
            'Indiranagar' => ['Indiranagar Depot'],
            'Whitefield' => ['Whitefield Depot'],
            'Secunderabad' => ['Secunderabad Depot'],
            'Banjara Hills' => ['Banjara Hills Depot'],
            'Connaught Place' => ['Connaught Place Depot'],
            'Dwarka' => ['Dwarka Depot'],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $locationName => $depots) {
                $location = Location::where('name', $locationName)->first();

                if (! $location) {
                    continue;
                }

                foreach ($depots as $depotName) {
                    $depot = Depot::firstOrCreate(
                        [
                            'location_id' => $location->id,
                            'name' => $depotName,
                        ],
                        [
                            'is_active' => true,
                        ]
                    );

                    if (! $depot->code) {
                        $depot->code = generate_code('Depot Module', $depot->id, 3, 'DPM');
                        $depot->save();
                    }
                }
            }
        });
    }
}
