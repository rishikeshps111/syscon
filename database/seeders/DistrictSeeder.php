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
            'Andhra Pradesh' => ['Visakhapatnam', 'Vijayawada', 'Guntur'],
            'Arunachal Pradesh' => ['Itanagar Capital Complex', 'Tawang'],
            'Assam' => ['Kamrup Metropolitan', 'Dibrugarh', 'Cachar'],
            'Bihar' => ['Patna', 'Gaya', 'Muzaffarpur'],
            'Chhattisgarh' => ['Raipur', 'Bilaspur', 'Durg'],
            'Goa' => ['North Goa', 'South Goa'],
            'Gujarat' => ['Ahmedabad', 'Surat', 'Vadodara'],
            'Haryana' => ['Gurugram', 'Faridabad', 'Panchkula'],
            'Himachal Pradesh' => ['Shimla', 'Kangra', 'Mandi'],
            'Jharkhand' => ['Ranchi', 'Dhanbad', 'East Singhbhum'],
            'Karnataka' => ['Bengaluru Urban', 'Mysuru', 'Mangaluru'],
            'Kerala' => ['Thiruvananthapuram', 'Ernakulam', 'Kozhikode'],
            'Madhya Pradesh' => ['Bhopal', 'Indore', 'Jabalpur'],
            'Maharashtra' => ['Mumbai City', 'Pune', 'Nagpur'],
            'Manipur' => ['Imphal West', 'Imphal East'],
            'Meghalaya' => ['East Khasi Hills', 'West Garo Hills'],
            'Mizoram' => ['Aizawl', 'Lunglei'],
            'Nagaland' => ['Kohima', 'Dimapur'],
            'Odisha' => ['Khordha', 'Cuttack', 'Ganjam'],
            'Punjab' => ['Ludhiana', 'Amritsar', 'Jalandhar'],
            'Rajasthan' => ['Jaipur', 'Jodhpur', 'Udaipur'],
            'Sikkim' => ['Gangtok', 'Namchi'],
            'Tamil Nadu' => ['Chennai', 'Coimbatore', 'Madurai'],
            'Telangana' => ['Hyderabad', 'Rangareddy', 'Warangal'],
            'Tripura' => ['West Tripura', 'South Tripura'],
            'Uttar Pradesh' => ['Lucknow', 'Kanpur Nagar', 'Varanasi'],
            'Uttarakhand' => ['Dehradun', 'Haridwar', 'Nainital'],
            'West Bengal' => ['Kolkata', 'Howrah', 'Darjeeling'],
            'Andaman and Nicobar Islands' => ['South Andaman', 'North and Middle Andaman'],
            'Chandigarh' => ['Chandigarh'],
            'Dadra and Nagar Haveli and Daman and Diu' => ['Daman', 'Diu', 'Dadra and Nagar Haveli'],
            'Delhi' => ['New Delhi', 'Central Delhi', 'South Delhi'],
            'Jammu and Kashmir' => ['Srinagar', 'Jammu', 'Baramulla'],
            'Ladakh' => ['Leh', 'Kargil'],
            'Lakshadweep' => ['Lakshadweep'],
            'Puducherry' => ['Puducherry', 'Karaikal'],
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
