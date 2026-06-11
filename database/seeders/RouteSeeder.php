<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Location;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            [
                'state' => 'Telangana',
                'district' => 'Hyderabad',
                'start' => 'Secunderabad',
                'end' => 'Banjara Hills',
                'name' => 'Secunderabad to Banjara Hills',
                'distance' => 13.50,
                'estimated_duration' => '00:50',
                'route_type' => 'Intracity',
                'route_category' => 'Passenger',
                'stops' => [
                    ['name' => 'Begumpet', 'position' => 1, 'expected_reach_time' => '00:15'],
                    ['name' => 'Panjagutta', 'position' => 2, 'expected_reach_time' => '00:32'],
                ],
            ],
            [
                'state' => 'Telangana',
                'district' => 'Hyderabad',
                'start' => 'Madhapur',
                'end' => 'Secunderabad',
                'name' => 'Madhapur to Secunderabad',
                'distance' => 18.25,
                'estimated_duration' => '01:05',
                'route_type' => 'Intracity',
                'route_category' => 'Passenger',
                'stops' => [
                    ['name' => 'Jubilee Hills', 'position' => 1, 'expected_reach_time' => '00:18'],
                    ['name' => 'Ameerpet', 'position' => 2, 'expected_reach_time' => '00:42'],
                ],
            ],
            [
                'state' => 'Telangana',
                'district' => 'Rangareddy',
                'start' => 'Gachibowli',
                'end' => 'Shamshabad',
                'name' => 'Gachibowli to Shamshabad',
                'distance' => 28.00,
                'estimated_duration' => '01:15',
                'route_type' => 'Intracity',
                'route_category' => 'Passenger',
                'stops' => [
                    ['name' => 'Nanakramguda', 'position' => 1, 'expected_reach_time' => '00:16'],
                    ['name' => 'Airport Approach', 'position' => 2, 'expected_reach_time' => '00:58'],
                ],
            ],
            [
                'state' => 'Telangana',
                'district' => 'Warangal',
                'start' => 'Hanamkonda',
                'end' => 'Kazipet',
                'name' => 'Hanamkonda to Kazipet',
                'distance' => 8.50,
                'estimated_duration' => '00:30',
                'route_type' => 'Intracity',
                'route_category' => 'Passenger',
                'stops' => [
                    ['name' => 'Nakkalagutta', 'position' => 1, 'expected_reach_time' => '00:10'],
                    ['name' => 'Subedari', 'position' => 2, 'expected_reach_time' => '00:20'],
                ],
            ],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $state = State::where('name', $record['state'])->first();
                $district = District::where('name', $record['district'])->where('state_id', $state?->id)->first();
                $startLocation = Location::where('name', $record['start'])->where('district_id', $district?->id)->first();
                $endLocation = Location::where('name', $record['end'])->where('district_id', $district?->id)->first();

                if (! $state || ! $district || ! $startLocation || ! $endLocation) {
                    continue;
                }

                $route = Route::firstOrCreate(
                    ['state_id' => $state->id, 'route_name' => $record['name']],
                    [
                        'district_id' => $district->id,
                        'start_point_id' => $startLocation->id,
                        'end_point_id' => $endLocation->id,
                        'total_distance_km' => $record['distance'],
                        'estimated_duration' => $record['estimated_duration'],
                        'route_type' => $record['route_type'],
                        'route_category' => $record['route_category'],
                        'status' => 'Active',
                    ]
                );

                if (! $route->route_code) {
                    $route->route_code = generate_code('Route Module', $route->id, 3, 'RT');
                    $route->save();
                }

                foreach ($record['stops'] as $stop) {
                    RouteStop::firstOrCreate(
                        [
                            'route_id' => $route->id,
                            'position' => $stop['position'],
                        ],
                        [
                            'name' => $stop['name'],
                            'expected_reach_time' => $stop['expected_reach_time'],
                        ]
                    );
                }
            }
        });
    }
}
