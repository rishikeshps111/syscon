<?php

namespace Database\Seeders;

use App\Models\Depot;
use App\Models\DriverProfile;
use App\Models\Route;
use App\Models\ServiceType;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TripManagementSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            [
                'service_type' => 'Intracity',
                'route' => 'Secunderabad to Banjara Hills',
                'title' => 'Secunderabad to Banjara Hills',
                'depot' => 'Secunderabad Depot',
                'vehicle_no' => 'TS10EV3303',
                'driver_email' => 'suresh.driver@example.com',
                'from_date' => '2026-06-01',
                'to_date' => '2026-06-10',
                'start_time' => '09:00',
                'end_time' => '13:00',
                'halt_time' => 30,
                'trip_side' => 'both',
                'status' => 'Active',
                'notes' => 'Morning Hyderabad employee transport trip.',
            ],
            [
                'service_type' => 'Airport Shuttle',
                'route' => 'Gachibowli to Shamshabad',
                'title' => 'Gachibowli to Shamshabad Airport Shuttle',
                'depot' => 'Gachibowli Depot',
                'vehicle_no' => 'TS09EV4545',
                'driver_email' => 'ravi.driver@example.com',
                'from_date' => '2026-06-01',
                'to_date' => '2026-06-10',
                'start_time' => '14:00',
                'end_time' => '17:30',
                'halt_time' => 20,
                'trip_side' => 'up',
                'status' => 'Active',
                'notes' => 'Afternoon shuttle trip.',
            ],
            [
                'service_type' => 'Intracity',
                'route' => 'Hanamkonda to Kazipet',
                'title' => 'Hanamkonda to Kazipet',
                'depot' => 'Hanamkonda Depot',
                'vehicle_no' => 'TS03MB6606',
                'driver_email' => 'arif.driver@example.com',
                'from_date' => '2026-06-03',
                'to_date' => '2026-06-12',
                'start_time' => '07:15',
                'end_time' => '08:00',
                'halt_time' => 15,
                'trip_side' => 'down',
                'status' => 'Inactive',
                'notes' => 'Completed Warangal transport sample.',
            ],
            [
                'service_type' => 'Employee Shuttle',
                'route' => 'Madhapur to Secunderabad',
                'title' => 'Madhapur to Secunderabad Employee Shuttle',
                'depot' => 'Madhapur Depot',
                'vehicle_no' => 'TS08EV2202',
                'driver_email' => 'manoj.driver@example.com',
                'from_date' => '2026-06-04',
                'to_date' => '2026-06-16',
                'start_time' => '06:30',
                'end_time' => '07:40',
                'halt_time' => 15,
                'trip_side' => 'up',
                'status' => 'Active',
                'notes' => 'Hyderabad technology corridor shuttle sample.',
            ],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $serviceType = ServiceType::where('name', $record['service_type'])->first();
                $route = Route::where('route_name', $record['route'])->first();
                $depot = Depot::where('name', $record['depot'])->first();
                $vehicle = Vehicle::where('vehicle_no', $record['vehicle_no'])->first();
                $driver = DriverProfile::whereHas('user', fn ($query) => $query->where('email', $record['driver_email']))->first();

                if (! $serviceType || ! $route || ! $vehicle || ! $driver) {
                    continue;
                }

                $trip = Trip::updateOrCreate(
                    [
                        'service_type_id' => $serviceType->id,
                        'route_id' => $route->id,
                    ],
                    [
                        'depot_id' => $depot?->id,
                        'state_id' => $route->state_id,
                        'title' => $record['title'],
                        'schedule_type' => 'daily',
                        'start_time' => $record['start_time'],
                        'end_time' => $record['end_time'],
                        'halt_time' => $this->minutesToTime($record['halt_time']),
                        'trip_side' => $record['trip_side'],
                        'from_date' => $record['from_date'],
                        'to_date' => $record['to_date'],
                        'status' => $record['status'],
                        'notes' => $record['notes'],
                        'is_active' => $record['status'] !== 'Cancelled',
                    ]
                );

                if (! $trip->code) {
                    $trip->code = generate_code(Trip::PREFIX_MODULE, $trip->id, 4);
                    $trip->save();
                }

                $trip->assignments()->updateOrCreate(
                    [
                        'from_date' => $record['from_date'],
                        'to_date' => $record['to_date'],
                    ],
                    [
                        'vehicle_id' => $vehicle->id,
                        'driver_profile_id' => $driver->id,
                        'notes' => $record['notes'],
                    ]
                );

                $controllerName = DB::table('users')->where('email', 'vishnu.controller@example.com')->value('name') ?: 'Vishnu Controller';
                $supervisorName = DB::table('users')->where('email', 'nithin.supervisor@example.com')->value('name') ?: 'Nithin Supervisor';

                foreach ($this->sheetRows($record, $controllerName, $supervisorName, $driver, $vehicle) as $row) {
                    $sheet = $trip->sheets()->updateOrCreate(
                        ['date' => $row['date']],
                        [
                            'code' => ($trip->code ?: 'TRIP-' . $trip->id) . '-' . str_replace('-', '', $row['date']),
                            'status' => $row['status'],
                        ]
                    );

                    $sheet->entries()->updateOrCreate(
                        ['side' => $row['side']],
                        $row['entry']
                    );
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

    private function sheetRows(array $record, string $controllerName, string $supervisorName, DriverProfile $driver, Vehicle $vehicle): array
    {
        return collect(range(0, 2))->flatMap(function (int $offset) use ($record, $controllerName, $supervisorName, $driver, $vehicle) {
            $date = Carbon::parse($record['from_date'])->addDays($offset)->toDateString();
            $sides = $record['trip_side'] === 'both' ? ['up', 'down'] : [$record['trip_side']];
            $status = $offset < 2 ? 'completed' : 'pending';

            return collect($sides)->map(fn (string $side) => [
                'date' => $date,
                'status' => $status,
                'side' => $side,
                'entry' => [
                    'side' => $side,
                    'departure_time' => $record['start_time'],
                    'arrival_time' => $record['end_time'],
                    'actual_start_time' => $side === 'up' ? $record['start_time'] : null,
                    'actual_reach_time' => $side === 'down' ? $record['end_time'] : null,
                    'starting_km' => 1200 + $offset,
                    'driver_profile_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                    'starting_electric_charge' => 85,
                    'vehicle_condition' => 'Good',
                    'is_vehicle_verified' => true,
                    'vehicle_verified_by' => $controllerName,
                    'vehicle_verified_at' => now(),
                    'is_driver_verified' => true,
                    'driver_verified_by' => $supervisorName,
                    'driver_verified_at' => now(),
                    'is_verified_by_supervisor' => $status === 'completed',
                    'verified_by_supervisor' => $status === 'completed' ? $supervisorName : null,
                    'verified_by_supervisor_at' => $status === 'completed' ? now() : null,
                    'is_verified_by_controller' => $status === 'completed',
                    'verified_by_controller' => $status === 'completed' ? $controllerName : null,
                    'verified_by_controller_at' => $status === 'completed' ? now() : null,
                    'notes' => 'Seeded trip sheet entry.',
                ],
            ]);
        })->all();
    }
}
