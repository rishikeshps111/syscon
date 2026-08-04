<?php

namespace Database\Seeders;

use App\Models\Depot;
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
                'route' => 'Secunderabad to Banjara Hills',
                'schedule_type' => 'daily',
                'start_time' => '08:00',
                'end_time' => '08:50',
                'halt_time' => 20,
                'trip_side' => 'up',
            ],
            [
                'service_type' => 'Intracity',
                'route' => 'Madhapur to Secunderabad',
                'schedule_type' => 'daily',
                'start_time' => '09:30',
                'end_time' => '10:35',
                'halt_time' => 30,
                'trip_side' => 'both',
            ],
            [
                'service_type' => 'Intracity',
                'route' => 'Gachibowli to Shamshabad',
                'schedule_type' => 'weekly',
                'start_time' => '07:00',
                'end_time' => '08:15',
                'halt_time' => 15,
                'trip_side' => 'down',
            ],
            [
                'service_type' => 'Intracity',
                'route' => 'Hanamkonda to Kazipet',
                'schedule_type' => 'daily',
                'start_time' => '06:30',
                'end_time' => '07:00',
                'halt_time' => 25,
                'trip_side' => 'up',
            ],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $serviceType = ServiceType::where('name', $record['service_type'])->first();
                $route = Route::where('route_name', $record['route'])->first();

                if (! $serviceType || ! $route) {
                    continue;
                }

                $usesSingleDepot = $record['trip_side'] === 'both';
                $fromDepot = Depot::find($route->start_point_id);
                $toDepot = Depot::find($route->end_point_id);

                if (! $fromDepot || (! $usesSingleDepot && ! $toDepot)) {
                    continue;
                }

                $trip = Trip::updateOrCreate(
                    [
                        'service_type_id' => $serviceType->id,
                        'route_id' => $route->id,
                        'schedule_type' => $record['schedule_type'],
                    ],
                    [
                        'depot_id' => $usesSingleDepot ? $fromDepot->id : null,
                        'from_depot_id' => $usesSingleDepot ? null : $fromDepot->id,
                        'to_depot_id' => $usesSingleDepot ? null : $toDepot->id,
                        'start_time' => $record['start_time'],
                        'end_time' => $record['end_time'],
                        'halt_time' => $this->minutesToTime($record['halt_time']),
                        'trip_side' => $record['trip_side'],
                        'state_id' => $route->state_id,
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
