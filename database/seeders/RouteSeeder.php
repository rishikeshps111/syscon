<?php

namespace Database\Seeders;

use App\Models\Depot;
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
                'start' => 'Kakkanad Main Depot',
                'end' => 'Aluva Depot',
                'name' => 'Kakkanad to Aluva',
                'distance' => 28,
                'estimated_duration' => '01:10',
                'route_type' => 'Intracity',
                'stops' => [
                    ['name' => 'Palarivattom', 'position' => 1, 'expected_reach_time' => '00:20'],
                    ['name' => 'Edappally', 'position' => 2, 'expected_reach_time' => '00:35'],
                    ['name' => 'Kalamassery', 'position' => 3, 'expected_reach_time' => '00:50'],
                ],
            ],
            [
                'state' => 'Kerala',
                'start' => 'Aluva Depot',
                'end' => 'Fort Kochi Depot',
                'name' => 'Aluva to Fort Kochi',
                'distance' => 34,
                'estimated_duration' => '01:35',
                'route_type' => 'Intracity',
                'stops' => [
                    ['name' => 'Kalamassery', 'position' => 1, 'expected_reach_time' => '00:25'],
                    ['name' => 'Vyttila', 'position' => 2, 'expected_reach_time' => '00:55'],
                    ['name' => 'Mattancherry', 'position' => 3, 'expected_reach_time' => '01:20'],
                ],
            ],
            [
                'state' => 'Tamil Nadu',
                'start' => 'T. Nagar Depot',
                'end' => 'Anna Nagar Depot',
                'name' => 'T. Nagar to Anna Nagar',
                'distance' => 12,
                'estimated_duration' => '00:45',
                'route_type' => 'Intracity',
                'stops' => [
                    ['name' => 'Kodambakkam', 'position' => 1, 'expected_reach_time' => '00:15'],
                    ['name' => 'Nungambakkam', 'position' => 2, 'expected_reach_time' => '00:30'],
                ],
            ],
            [
                'state' => 'Karnataka',
                'start' => 'Koramangala Depot',
                'end' => 'Whitefield Depot',
                'name' => 'Koramangala to Whitefield',
                'distance' => 19,
                'estimated_duration' => '01:20',
                'route_type' => 'Intracity',
                'stops' => [
                    ['name' => 'Indiranagar', 'position' => 1, 'expected_reach_time' => '00:25'],
                    ['name' => 'Marathahalli', 'position' => 2, 'expected_reach_time' => '00:55'],
                ],
            ],
            [
                'state' => 'Maharashtra',
                'start' => 'Colaba Depot',
                'end' => 'Dadar Depot',
                'name' => 'Colaba to Dadar',
                'distance' => 14,
                'estimated_duration' => '00:55',
                'route_type' => 'Intracity',
                'stops' => [
                    ['name' => 'Marine Lines', 'position' => 1, 'expected_reach_time' => '00:15'],
                    ['name' => 'Worli', 'position' => 2, 'expected_reach_time' => '00:35'],
                ],
            ],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $state = State::where('name', $record['state'])->first();
                $startDepot = Depot::where('name', $record['start'])->first();
                $endDepot = Depot::where('name', $record['end'])->first();

                if (! $state || ! $startDepot || ! $endDepot) {
                    continue;
                }

                $route = Route::firstOrCreate(
                    ['name' => $record['name']],
                    [
                        'state_id' => $state->id,
                        'start_point_id' => $startDepot->id,
                        'end_point_id' => $endDepot->id,
                        'distance' => $record['distance'],
                        'estimated_duration' => $record['estimated_duration'],
                        'route_type' => $record['route_type'],
                        'is_active' => true,
                    ]
                );

                if (! $route->code) {
                    $route->code = generate_code('Route Module', $route->id, 3, 'RT');
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
