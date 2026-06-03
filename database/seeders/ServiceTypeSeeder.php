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
                'name' => 'Intercity',
                'description' => 'Trips operated between cities.',
            ],
            [
                'name' => 'Intracity',
                'description' => 'Trips operated within a city.',
            ],
            [
                'name' => 'Airport Shuttle',
                'description' => 'Scheduled pickup and drop trips between depots, hotels, offices and airports.',
            ],
            [
                'name' => 'School Transport',
                'description' => 'Fixed route student and staff transport services.',
            ],
            [
                'name' => 'Employee Shuttle',
                'description' => 'Corporate commute trips for shift-based employee movement.',
            ],
            [
                'name' => 'Emergency Replacement',
                'description' => 'Short-notice replacement trips for breakdown, absenteeism or route disruption.',
            ],
        ];

        DB::transaction(function () use ($records) {
            ServiceType::whereNotIn('name', collect($records)->pluck('name'))->delete();

            foreach ($records as $record) {
                $serviceType = ServiceType::updateOrCreate(
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
