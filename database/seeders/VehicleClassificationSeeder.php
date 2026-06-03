<?php

namespace Database\Seeders;

use App\Models\VehicleClassification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            [
                'name' => 'Two Wheeler',
                'capacity' => 2,
                'fuel_type' => 'petrol',
                'description' => 'Motorcycles and scooters for small deliveries.',
            ],
            [
                'name' => 'Light Commercial Vehicle',
                'capacity' => 1000,
                'fuel_type' => 'diesel',
                'description' => 'Small cargo vehicles for city and regional transport.',
            ],
            [
                'name' => 'Electric Van',
                'capacity' => 750,
                'fuel_type' => 'ev',
                'description' => 'Electric cargo van for low-emission delivery routes.',
            ],
            [
                'name' => 'Heavy Truck',
                'capacity' => 12000,
                'fuel_type' => 'diesel',
                'description' => 'High-capacity truck for long-haul transport.',
            ],
            [
                'name' => 'City Bus',
                'capacity' => 42,
                'fuel_type' => 'diesel',
                'description' => 'Passenger buses for dense urban shuttle routes.',
            ],
            [
                'name' => 'Electric City Bus',
                'capacity' => 36,
                'fuel_type' => 'ev',
                'description' => 'Electric passenger buses for zero-emission city routes.',
            ],
            [
                'name' => 'Staff Car',
                'capacity' => 7,
                'fuel_type' => 'hybrid',
                'description' => 'Cars and MPVs for staff and supervisor movement.',
            ],
            [
                'name' => 'Mini Bus',
                'capacity' => 24,
                'fuel_type' => 'diesel',
                'description' => 'Compact passenger buses for feeder and branch routes.',
            ],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $vehicleClassification = VehicleClassification::firstOrCreate(
                    ['name' => $record['name']],
                    [
                        'capacity' => $record['capacity'],
                        'fuel_type' => $record['fuel_type'],
                        'description' => $record['description'],
                        'is_active' => true,
                    ]
                );

                if (! $vehicleClassification->code) {
                    $vehicleClassification->code = generate_code('Vehicle Classification Module', $vehicleClassification->id, 3, 'VC');
                    $vehicleClassification->save();
                }
            }
        });
    }
}
