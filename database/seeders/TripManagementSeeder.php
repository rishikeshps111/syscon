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
                'route' => 'Kakkanad to Aluva',
                'title' => 'Kakkanad to Aluva',
                'depot' => 'Kakkanad Main Depot',
                'vehicle_no' => 'KL07EV1001',
                'driver_email' => 'suresh.driver@example.com',
                'from_date' => '2026-06-01',
                'to_date' => '2026-06-10',
                'start_time' => '09:00',
                'end_time' => '13:00',
                'status' => 'Active',
                'notes' => 'Morning intercity employee transport trip.',
            ],
            [
                'service_type' => 'Intercity',
                'route' => 'Aluva to Fort Kochi',
                'title' => 'Aluva to Fort Kochi',
                'depot' => 'Aluva Depot',
                'vehicle_no' => 'KL41D2045',
                'driver_email' => 'ravi.driver@example.com',
                'from_date' => '2026-06-01',
                'to_date' => '2026-06-10',
                'start_time' => '14:00',
                'end_time' => '17:30',
                'status' => 'Active',
                'notes' => 'Afternoon shuttle trip.',
            ],
            [
                'service_type' => 'Intracity',
                'route' => 'T. Nagar to Anna Nagar',
                'title' => 'T. Nagar to Anna Nagar',
                'depot' => 'T. Nagar Depot',
                'vehicle_no' => 'TN09HY4545',
                'driver_email' => 'arif.driver@example.com',
                'from_date' => '2026-06-03',
                'to_date' => '2026-06-12',
                'start_time' => '07:15',
                'end_time' => '08:00',
                'status' => 'Inactive',
                'notes' => 'Completed school transport sample.',
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
                        'title' => $record['title'],
                        'schedule_type' => 'daily',
                        'start_time' => $record['start_time'],
                        'end_time' => $record['end_time'],
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

                foreach ($this->sheetRows($record, $driver->id, $vehicle->id) as $row) {
                    $trip->sheetEntries()->updateOrCreate(
                        ['trip_date' => $row['trip_date']],
                        $row
                    );
                }
            }
        });
    }

    private function sheetRows(array $record, int $driverId, int $vehicleId): array
    {
        return collect(range(0, 2))->map(function (int $offset) use ($record, $driverId, $vehicleId) {
            $date = Carbon::parse($record['from_date'])->addDays($offset)->toDateString();

            return [
                'trip_date' => $date,
                'departure_time' => $record['start_time'],
                'arrival_time' => $record['end_time'],
                'actual_start_time' => $record['start_time'],
                'actual_reach_time' => $record['end_time'],
                'verified_by' => 'Controller',
                'approved_by' => 'Supervisor',
                'shift' => $offset % 2 === 0 ? '1' : '2',
                'driver_profile_id' => $driverId,
                'vehicle_id' => $vehicleId,
                'notes' => 'Seeded trip sheet entry.',
            ];
        })->all();
    }
}
