<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            [
                'name' => 'Employee Transport',
                'description' => 'Daily office commute',
            ],
            [
                'name' => 'School Transport',
                'description' => 'Student pickup/drop',
            ],
            [
                'name' => 'Logistics',
                'description' => 'Goods transport',
            ],
            [
                'name' => 'Shuttle Service',
                'description' => 'Short distance shuttle',
            ],
            [
                'name' => 'Emergency Service',
                'description' => 'Medical/emergency trips',
            ],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $serviceType = ServiceType::firstOrCreate(
                    ['name' => $record['name']],
                    [
                        'description' => $record['description'],
                        'is_active' => true,
                    ]
                );

                if (! $serviceType->code) {
                    $serviceType->code = generate_code('Service Type Module', $serviceType->id, 3, 'SRT');
                    $serviceType->save();
                }
            }
        });
    }
}
