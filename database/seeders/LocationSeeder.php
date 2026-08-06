<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            'Hyderabad' => [
                'Secunderabad' => '500003',
                'Banjara Hills' => '500034',
                'Madhapur' => '500081',
            ],
            'Rangareddy' => [
                'Gachibowli' => '500032',
                'Shamshabad' => '501218',
                'Ibrahimpatnam' => '501506',
            ],
            'Warangal' => [
                'Hanamkonda' => '506001',
                'Kazipet' => '506003',
                'Warangal Fort' => '506002',
            ],
            'Medchal-Malkajgiri' => [
                'Kompally' => '500014',
                'Malkajgiri' => '500047',
                'Uppal' => '500039',
            ],
            'Nizamabad' => [
                'Bodhan' => '503185',
                'Armoor' => '503224',
                'Dichpally' => '503175',
            ],
            'Karimnagar' => [
                'Huzurabad' => '505468',
                'Jammikunta' => '505122',
                'Manakondur' => '505469',
            ],
        ];

        DB::transaction(function () use ($records) {
            $hasDefault = Location::where('is_default', true)->exists();

            foreach ($records as $districtName => $locations) {
                $district = District::with('state')
                    ->where('name', $districtName)
                    ->first();

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
                            'short_name' => Str::upper(
                                Str::substr(
                                    str_replace(' ', '', $locationName),
                                    0,
                                    10
                                )
                            ),
                            'pincode' => $pincode,
                            'is_active' => true,
                            'is_default' => ! $hasDefault,
                        ]
                    );

                    if (! $hasDefault && $location->is_default) {
                        $hasDefault = true;
                    }

                    if (! $location->code) {
                        $location->code = generate_code(
                            'Location Module',
                            $location->id,
                            3,
                            'LOC'
                        );

                        $location->save();
                    }

                    // Update old records that have no values.
                    $location->update([
                        'short_name' => $location->short_name
                            ?: Str::upper(Str::substr(str_replace(' ', '', $locationName), 0, 10)),

                        'pincode' => $location->pincode ?: $pincode,
                    ]);
                }
            }
        });
    }
}
