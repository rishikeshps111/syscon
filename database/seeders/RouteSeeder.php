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
                'state' => 'Kerala',
                'district' => 'Ernakulam',
                'start' => 'Kakkanad',
                'end' => 'Aluva',
                'name' => 'Kakkanad to Aluva',
                'distance' => 28.00,
                'estimated_duration' => '01:10',
                'route_type' => 'Intracity',
                'route_category' => 'Passenger',
                'stops' => [
                    ['name' => 'Palarivattom', 'position' => 1, 'expected_reach_time' => '00:20'],
                    ['name' => 'Edappally', 'position' => 2, 'expected_reach_time' => '00:35'],
                    ['name' => 'Kalamassery', 'position' => 3, 'expected_reach_time' => '00:50'],
                ],
            ],
            [
                'state' => 'Kerala',
                'district' => 'Ernakulam',
                'start' => 'Aluva',
                'end' => 'Fort Kochi',
                'name' => 'Aluva to Fort Kochi',
                'distance' => 34.00,
                'estimated_duration' => '01:35',
                'route_type' => 'Intracity',
                'route_category' => 'Passenger',
                'stops' => [
                    ['name' => 'Kalamassery', 'position' => 1, 'expected_reach_time' => '00:25'],
                    ['name' => 'Vyttila', 'position' => 2, 'expected_reach_time' => '00:55'],
                    ['name' => 'Mattancherry', 'position' => 3, 'expected_reach_time' => '01:20'],
                ],
            ],
            [
                'state' => 'Tamil Nadu',
                'district' => 'Chennai',
                'start' => 'T. Nagar',
                'end' => 'Anna Nagar',
                'name' => 'T. Nagar to Anna Nagar',
                'distance' => 12.00,
                'estimated_duration' => '00:45',
                'route_type' => 'Intracity',
                'route_category' => 'Passenger',
                'stops' => [
                    ['name' => 'Kodambakkam', 'position' => 1, 'expected_reach_time' => '00:15'],
                    ['name' => 'Nungambakkam', 'position' => 2, 'expected_reach_time' => '00:30'],
                ],
            ],
            [
                'state' => 'Karnataka',
                'district' => 'Bengaluru Urban',
                'start' => 'Koramangala',
                'end' => 'Whitefield',
                'name' => 'Koramangala to Whitefield',
                'distance' => 19.00,
                'estimated_duration' => '01:20',
                'route_type' => 'Intracity',
                'route_category' => 'Passenger',
                'stops' => [
                    ['name' => 'Indiranagar', 'position' => 1, 'expected_reach_time' => '00:25'],
                    ['name' => 'Marathahalli', 'position' => 2, 'expected_reach_time' => '00:55'],
                ],
            ],
            [
                'state' => 'Maharashtra',
                'district' => 'Mumbai City',
                'start' => 'Colaba',
                'end' => 'Dadar',
                'name' => 'Colaba to Dadar',
                'distance' => 14.00,
                'estimated_duration' => '00:55',
                'route_type' => 'Intracity',
                'route_category' => 'Cargo',
                'stops' => [
                    ['name' => 'Marine Lines', 'position' => 1, 'expected_reach_time' => '00:15'],
                    ['name' => 'Worli', 'position' => 2, 'expected_reach_time' => '00:35'],
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
