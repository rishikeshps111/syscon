<?php

namespace Database\Seeders;

use App\Models\BranchLocation;
use App\Models\Depot;
use App\Models\Oem;
use App\Models\State;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::whereHas('roles', fn($query) => $query->where('name', 'Super Admin'))->first()
            ?? User::first();

        $records = [
            [
                'state' => 'Telangana',
                'oem' => 'Metro Mobility Services',
                'depot' => 'Secunderabad Depot',
                'branch' => 'Secunderabad Branch',
                'vehicle_no' => 'TS10EV3303',
                'vehicle_type' => 'VAN',
                'fuel_type' => 'ELECTRIC',
                'vehicle_category' => 'Passenger',
                'make' => 'Mahindra',
                'model' => 'e-Supro',
                'variant' => 'Staff Shuttle',
                'capacity_seating' => 10,
                'capacity_load' => null,
                'battery_capacity' => 90,
                'range_km' => 140,
                'engine_no' => null,
                'chassis_no' => 'MAHVENTS10EV3303',
                'registration_date' => now()->subMonths(14)->toDateString(),
                'registration_valid_upto' => now()->addYears(8)->toDateString(),
                'fitness_expiry' => now()->addMonths(4)->toDateString(),
                'permit_expiry' => now()->addMonths(4)->toDateString(),
                'insurance_expiry' => now()->addDays(35)->toDateString(),
                'pollution_expiry' => now()->addMonths(5)->toDateString(),
                'gps_enabled' => true,
                'gps_imei' => '865667040001007',
                'status' => 'Active',
                'is_verified' => true,
                'remarks' => 'Hyderabad staff shuttle sample.',
            ],
            [
                'state' => 'Telangana',
                'oem' => 'Alpha Motors Private Limited',
                'depot' => 'Madhapur Depot',
                'branch' => 'Madhapur Branch',
                'vehicle_no' => 'TS08EV2202',
                'vehicle_type' => 'BUS',
                'fuel_type' => 'ELECTRIC',
                'vehicle_category' => 'Passenger',
                'make' => 'Olectra',
                'model' => 'CX2',
                'variant' => 'City EV',
                'capacity_seating' => 34,
                'capacity_load' => null,
                'battery_capacity' => 220,
                'range_km' => 190,
                'engine_no' => null,
                'chassis_no' => 'OLEBUSTS08EV2202',
                'registration_date' => now()->subMonths(10)->toDateString(),
                'registration_valid_upto' => now()->addYears(9)->toDateString(),
                'fitness_expiry' => now()->addMonths(8)->toDateString(),
                'permit_expiry' => now()->addMonths(9)->toDateString(),
                'insurance_expiry' => now()->addMonths(7)->toDateString(),
                'pollution_expiry' => now()->addMonths(6)->toDateString(),
                'gps_enabled' => true,
                'gps_imei' => '865667040001006',
                'status' => 'Active',
                'is_verified' => true,
                'remarks' => 'Electric bus for Hyderabad technology corridor.',
            ],
            [
                'state' => 'Telangana',
                'oem' => 'Transit Parts Dealers',
                'depot' => 'Hanamkonda Depot',
                'branch' => 'Hanamkonda Branch',
                'vehicle_no' => 'TS03MB6606',
                'vehicle_type' => 'BUS',
                'fuel_type' => 'DIESEL',
                'vehicle_category' => 'Passenger',
                'make' => 'Force',
                'model' => 'Traveller',
                'variant' => 'Mini Bus',
                'capacity_seating' => 24,
                'capacity_load' => null,
                'battery_capacity' => null,
                'range_km' => null,
                'engine_no' => 'ENGTS03MB6606',
                'chassis_no' => 'FORBUSTS03MB6606',
                'registration_date' => now()->subYears(2)->toDateString(),
                'registration_valid_upto' => now()->addYears(7)->toDateString(),
                'fitness_expiry' => now()->addDays(60)->toDateString(),
                'permit_expiry' => now()->addMonths(6)->toDateString(),
                'insurance_expiry' => now()->addMonths(3)->toDateString(),
                'pollution_expiry' => now()->addDays(45)->toDateString(),
                'gps_enabled' => false,
                'gps_imei' => null,
                'status' => 'Active',
                'is_verified' => true,
                'remarks' => 'Mini bus for Warangal feeder route.',
            ],
            [
                'state' => 'Telangana',
                'oem' => 'Alpha Motors Private Limited',
                'depot' => 'Gachibowli Depot',
                'branch' => 'Gachibowli Branch',
                'vehicle_no' => 'TS09EV4545',
                'vehicle_type' => 'CAR',
                'fuel_type' => 'ELECTRIC',
                'vehicle_category' => 'Passenger',
                'make' => 'MG',
                'model' => 'ZS EV',
                'variant' => 'Executive',
                'capacity_seating' => 5,
                'capacity_load' => null,
                'battery_capacity' => 50,
                'range_km' => 320,
                'engine_no' => null,
                'chassis_no' => 'MGCARTS09EV4545',
                'registration_date' => now()->subMonths(5)->toDateString(),
                'registration_valid_upto' => now()->addYears(9)->toDateString(),
                'fitness_expiry' => now()->addYear()->toDateString(),
                'permit_expiry' => now()->addMonths(11)->toDateString(),
                'insurance_expiry' => now()->addMonths(10)->toDateString(),
                'pollution_expiry' => now()->addMonths(10)->toDateString(),
                'gps_enabled' => true,
                'gps_imei' => '865667040001008',
                'status' => 'Active',
                'is_verified' => true,
                'remarks' => 'Airport corridor executive movement EV.',
            ],
        ];

        DB::transaction(function () use ($records, $user) {
            foreach ($records as $record) {
                $state = State::where('name', $record['state'])->first();
                $oem = Oem::where('oem_name', $record['oem'])->first();
                $depot = Depot::where('name', $record['depot'])->first();
                $branch = BranchLocation::where('name', $record['branch'])->first();

                if (! $state || ! $oem || ! $depot || ! $branch) {
                    continue;
                }

                $vehicle = Vehicle::updateOrCreate(
                    ['vehicle_no' => $record['vehicle_no']],
                    collect($record)->except(['state', 'oem', 'depot', 'branch'])->all() + [
                        'state_id' => $state->id,
                        'oem_id' => $oem->id,
                        'depot_id' => $depot->id,
                        'branch_id' => $branch->id,
                        'created_by' => $user?->id,
                        'updated_by' => $user?->id,
                    ]
                );

                if (! $vehicle->vehicle_code) {
                    $vehicle->vehicle_code = generate_code('Vehicle Module', $vehicle->id, 3, 'VEH');
                    $vehicle->save();
                }
            }
        });
    }
}
