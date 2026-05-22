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
        $user = User::role('Super Admin')->first() ?? User::first();

        $records = [
            [
                'state' => 'Kerala',
                'oem' => 'Alpha Motors Private Limited',
                'depot' => 'Kakkanad Main Depot',
                'branch' => 'Kakkanad Operations Branch',
                'vehicle_no' => 'KL07EV1001',
                'vehicle_type' => 'BUS',
                'fuel_type' => 'ELECTRIC',
                'vehicle_category' => 'Passenger',
                'make' => 'Tata',
                'model' => 'Starbus EV',
                'variant' => 'Urban 12m',
                'capacity_seating' => 36,
                'capacity_load' => null,
                'battery_capacity' => 250,
                'range_km' => 220,
                'engine_no' => null,
                'chassis_no' => 'MATBUSKL07EV1001',
                'registration_date' => now()->subMonths(18)->toDateString(),
                'registration_valid_upto' => now()->addYears(8)->toDateString(),
                'fitness_expiry' => now()->addMonths(9)->toDateString(),
                'permit_expiry' => now()->addMonths(7)->toDateString(),
                'insurance_expiry' => now()->addMonths(4)->toDateString(),
                'pollution_expiry' => now()->addMonths(6)->toDateString(),
                'gps_enabled' => true,
                'gps_imei' => '865667040001001',
                'status' => 'Active',
                'is_verified' => true,
                'remarks' => 'Primary EV bus for Kakkanad operations.',
            ],
            [
                'state' => 'Kerala',
                'oem' => 'Alpha Motors Private Limited',
                'depot' => 'Aluva Depot',
                'branch' => 'Aluva Branch',
                'vehicle_no' => 'KL41D2045',
                'vehicle_type' => 'VAN',
                'fuel_type' => 'DIESEL',
                'vehicle_category' => 'Cargo',
                'make' => 'Ashok Leyland',
                'model' => 'Dost Plus',
                'variant' => 'LX',
                'capacity_seating' => 2,
                'capacity_load' => 1500,
                'battery_capacity' => null,
                'range_km' => null,
                'engine_no' => 'ENGKL41D2045',
                'chassis_no' => 'MB1VANKL41D2045',
                'registration_date' => now()->subYears(3)->toDateString(),
                'registration_valid_upto' => now()->addYears(6)->toDateString(),
                'fitness_expiry' => now()->addDays(20)->toDateString(),
                'permit_expiry' => now()->addMonths(3)->toDateString(),
                'insurance_expiry' => now()->addDays(12)->toDateString(),
                'pollution_expiry' => now()->addDays(28)->toDateString(),
                'gps_enabled' => true,
                'gps_imei' => '865667040001002',
                'status' => 'Active',
                'is_verified' => true,
                'remarks' => 'Document expiry warning sample.',
            ],
            [
                'state' => 'Karnataka',
                'oem' => 'Metro Mobility Services',
                'depot' => 'Whitefield Depot',
                'branch' => 'Whitefield Branch',
                'vehicle_no' => 'KA53C7788',
                'vehicle_type' => 'BUS',
                'fuel_type' => 'CNG',
                'vehicle_category' => 'Passenger',
                'make' => 'Eicher',
                'model' => 'Skyline Pro',
                'variant' => 'CNG City',
                'capacity_seating' => 42,
                'capacity_load' => null,
                'battery_capacity' => null,
                'range_km' => null,
                'engine_no' => 'ENGKA53C7788',
                'chassis_no' => 'MC2BUSKA53C7788',
                'registration_date' => now()->subYears(5)->toDateString(),
                'registration_valid_upto' => now()->addYears(4)->toDateString(),
                'fitness_expiry' => now()->subDays(5)->toDateString(),
                'permit_expiry' => now()->addMonths(2)->toDateString(),
                'insurance_expiry' => now()->subDays(15)->toDateString(),
                'pollution_expiry' => now()->addDays(10)->toDateString(),
                'gps_enabled' => false,
                'gps_imei' => null,
                'status' => 'Under Maintenance',
                'is_verified' => false,
                'remarks' => 'Expired document highlight sample.',
            ],
            [
                'state' => 'Maharashtra',
                'oem' => 'Transit Parts Dealers',
                'depot' => 'Colaba Depot',
                'branch' => 'Colaba Branch',
                'vehicle_no' => 'MH01TR9090',
                'vehicle_type' => 'TRUCK',
                'fuel_type' => 'DIESEL',
                'vehicle_category' => 'Cargo',
                'make' => 'BharatBenz',
                'model' => '1217C',
                'variant' => 'Cargo',
                'capacity_seating' => 2,
                'capacity_load' => 10500,
                'battery_capacity' => null,
                'range_km' => null,
                'engine_no' => 'ENGMH01TR9090',
                'chassis_no' => 'MECTRKMH01TR9090',
                'registration_date' => now()->subYears(7)->toDateString(),
                'registration_valid_upto' => now()->addYears(2)->toDateString(),
                'fitness_expiry' => now()->addMonths(5)->toDateString(),
                'permit_expiry' => now()->addMonths(5)->toDateString(),
                'insurance_expiry' => now()->addMonths(11)->toDateString(),
                'pollution_expiry' => now()->addMonths(2)->toDateString(),
                'gps_enabled' => true,
                'gps_imei' => '865667040001004',
                'status' => 'Inactive',
                'is_verified' => false,
                'remarks' => 'Inactive cargo vehicle sample.',
            ],
            [
                'state' => 'Tamil Nadu',
                'oem' => 'Alpha Motors Private Limited',
                'depot' => 'T. Nagar Depot',
                'branch' => 'T. Nagar Branch',
                'vehicle_no' => 'TN09HY4545',
                'vehicle_type' => 'CAR',
                'fuel_type' => 'HYBRID',
                'vehicle_category' => 'Passenger',
                'make' => 'Toyota',
                'model' => 'Innova Hycross',
                'variant' => 'ZX',
                'capacity_seating' => 7,
                'capacity_load' => null,
                'battery_capacity' => null,
                'range_km' => null,
                'engine_no' => 'ENGTN09HY4545',
                'chassis_no' => 'MBJCARTRTN09HY4545',
                'registration_date' => now()->subMonths(8)->toDateString(),
                'registration_valid_upto' => now()->addYears(9)->toDateString(),
                'fitness_expiry' => now()->addYear()->toDateString(),
                'permit_expiry' => now()->addMonths(10)->toDateString(),
                'insurance_expiry' => now()->addMonths(8)->toDateString(),
                'pollution_expiry' => now()->addMonths(4)->toDateString(),
                'gps_enabled' => true,
                'gps_imei' => '865667040001005',
                'status' => 'Active',
                'is_verified' => true,
                'remarks' => 'Hybrid staff movement vehicle.',
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
