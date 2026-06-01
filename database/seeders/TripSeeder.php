<?php

namespace Database\Seeders;

use App\Models\Route;
use App\Models\ServiceType;
use App\Models\Trip;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TripSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            [
                'service_type' => 'Intracity',
                'route' => 'Kakkanad to Aluva',
                'schedule_type' => 'daily',
                'start_time' => '08:00',
                'end_time' => '09:10',
                'halt_time' => 20,
                'trip_side' => 'up',
            ],
            [
                'service_type' => 'Intercity',
                'route' => 'Aluva to Fort Kochi',
                'schedule_type' => 'daily',
                'start_time' => '09:30',
                'end_time' => '11:05',
                'halt_time' => 30,
                'trip_side' => 'both',
            ],
            [
                'service_type' => 'Intracity',
                'route' => 'T. Nagar to Anna Nagar',
                'schedule_type' => 'weekly',
                'start_time' => '07:15',
                'end_time' => '08:00',
                'halt_time' => 15,
                'trip_side' => 'down',
            ],
            [
                'service_type' => 'Intracity',
                'route' => 'Koramangala to Whitefield',
                'schedule_type' => 'daily',
                'start_time' => '06:30',
                'end_time' => '07:50',
                'halt_time' => 25,
                'trip_side' => 'up',
            ],
            [
                'service_type' => 'Intercity',
                'route' => 'Colaba to Dadar',
                'schedule_type' => 'monthly',
                'start_time' => '10:00',
                'end_time' => '10:55',
                'halt_time' => 20,
                'trip_side' => 'both',
            ],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $serviceType = ServiceType::where('name', $record['service_type'])->first();
                $route = Route::where('route_name', $record['route'])->first();

                if (! $serviceType || ! $route) {
                    continue;
                }

                $trip = Trip::firstOrCreate(
                    [
                        'service_type_id' => $serviceType->id,
                        'route_id' => $route->id,
                        'schedule_type' => $record['schedule_type'],
                    ],
                    [
                        'start_time' => $record['start_time'],
                        'end_time' => $record['end_time'],
                        'halt_time' => $this->minutesToTime($record['halt_time']),
                        'trip_side' => $record['trip_side'],
                        'is_active' => true,
                    ]
                );

                if (! $trip->code) {
                    $trip->code = generate_code(Trip::PREFIX_MODULE, $trip->id, 4);
                    $trip->save();
                }
            }
        });
    }

    private function minutesToTime(?int $minutes): ?string
    {
        return $minutes === null
            ? null
            : sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
