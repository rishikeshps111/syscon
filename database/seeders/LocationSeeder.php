<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            'Ernakulam' => ['Kakkanad' => '682030', 'Aluva' => '683101', 'Fort Kochi' => '682001'],
            'Kozhikode' => ['Koyilandy' => '673305', 'Vadakara' => '673101', 'Feroke' => '673631'],
            'Thiruvananthapuram' => ['Neyyattinkara' => '695121', 'Kazhakkoottam' => '695582', 'Varkala' => '695141'],
            'Chennai' => ['T. Nagar' => '600017', 'Anna Nagar' => '600040', 'Velachery' => '600042'],
            'Coimbatore' => ['Gandhipuram' => '641012', 'Peelamedu' => '641004', 'Singanallur' => '641005'],
            'Madurai' => ['Anna Nagar' => '625020', 'Tallakulam' => '625002', 'Thiruppalai' => '625014'],
            'Mumbai City' => ['Colaba' => '400005', 'Dadar' => '400014', 'Byculla' => '400027'],
            'Pune' => ['Shivajinagar' => '411005', 'Kothrud' => '411038', 'Hadapsar' => '411028'],
            'Bengaluru Urban' => ['Koramangala' => '560034', 'Indiranagar' => '560038', 'Whitefield' => '560066'],
            'Mysuru' => ['Vijayanagar' => '570017', 'Nazarbad' => '570010', 'Kuvempunagar' => '570023'],
            'Hyderabad' => ['Secunderabad' => '500003', 'Banjara Hills' => '500034', 'Madhapur' => '500081'],
            'New Delhi' => ['Connaught Place' => '110001', 'Karol Bagh' => '110005', 'Dwarka' => '110075'],
        ];

        DB::transaction(function () use ($records) {
            $hasDefault = Location::where('is_default', true)->exists();

            foreach ($records as $districtName => $locations) {
                $district = District::with('state')->where('name', $districtName)->first();

                if (! $district || ! $district->state) {
                    continue;
                }

                foreach ($locations as $locationName => $pincode) {
                    $location = Location::firstOrCreate(
                        [
                            'state_id' => $district->state_id,
                            'district_id' => $district->id,
                            'name' => $locationName,
                        ],
                        [
                            'is_active' => true,
                            'is_default' => ! $hasDefault,
                        ]
                    );

                    if (! $hasDefault && $location->is_default) {
                        $hasDefault = true;
                    }

                    if (! $location->code) {
                        $location->code = generate_code('Location Module', $location->id, 3, 'LOC');
                        $location->save();
                    }

                    if (! $location->pincode) {
                        $location->pincode = $pincode;
                        $location->save();
                    }
                }
            }
        });
    }
}
