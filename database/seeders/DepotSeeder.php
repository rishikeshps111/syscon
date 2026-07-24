<?php

namespace Database\Seeders;

use App\Models\Depot;
use App\Models\BranchLocation;
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
            'Secunderabad' => ['Secunderabad Depot'],
            'Banjara Hills' => ['Banjara Hills Depot'],
            'Madhapur' => ['Madhapur Depot'],
            'Gachibowli' => ['Gachibowli Depot'],
            'Shamshabad' => ['Shamshabad Depot'],
            'Hanamkonda' => ['Hanamkonda Depot'],
            'Kazipet' => ['Kazipet Depot'],
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
                            'state_id' => $location->state_id,
                            'district_id' => $location->district_id,
                            'is_active' => true,
                            'short_name' => $this->shortName($depotName),
                        ]
                    );

                    $depot->fill([
                        'state_id' => $location->state_id,
                        'district_id' => $location->district_id,
                        'short_name' => $depot->short_name ?: $this->shortName($depotName),
                    ])->save();

                    if (! $depot->code) {
                        $depot->code = generate_code('Depot Module', $depot->id, 3, 'DPM');
                        $depot->save();
                    }

                    $branchIds = BranchLocation::where('location_id', $location->id)->pluck('id')->all();
                    $depot->branchLocations()->sync($branchIds);
                }
            }
        });
    }

    private function shortName(string $name): string
    {
        return collect(preg_split('/\s+/', $name))
            ->filter()
            ->map(fn (string $word) => strtoupper(substr($word, 0, 1)))
            ->implode('');
    }
}
